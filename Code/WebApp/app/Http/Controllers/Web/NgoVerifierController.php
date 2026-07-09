<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\AuditLogger;

class NgoVerifierController extends Controller
{
    private function guard()
    {
        if (Auth::user()->role !== 'ngo-verifier') {
            abort(403, 'Forbidden — NGO Verifier access only.');
        }
    }

    private function getNgoUserIds($ngoId)
    {
        $ngo = \App\Models\Ngo::find($ngoId);
        if (!$ngo) {
            return [];
        }

        $ngoAdmin = User::where('email', $ngo->email)->first();
        if (!$ngoAdmin) {
            return [];
        }

        $ngoAdminId = $ngoAdmin->id;

        $employerUserIds = User::where('role', 'employer')->where('created_by', $ngoAdminId)->pluck('id')->toArray();
        $contractorUserIds = User::where('role', 'contractor')
            ->where(function($q) use ($ngoAdminId, $employerUserIds) {
                $q->where('created_by', $ngoAdminId)->orWhereIn('created_by', $employerUserIds);
            })
            ->pluck('id')
            ->toArray();

        return array_merge([$ngoAdminId], $employerUserIds, $contractorUserIds);
    }

    public function index()
    {
        $this->guard();

        $ngoId = Auth::user()->ngo_id;
        $ngoUserIds = $this->getNgoUserIds($ngoId);
        
        // Fetch worker ids belonging to this NGO
        $workerIds = DB::table('workers')->whereIn('created_by', $ngoUserIds)->pluck('_id')->toArray();

        $pendingCount = DB::table('scheme_applications')->whereIn('worker_id', $workerIds)->where('status', 'pending')->count();
        $verifiedCount = DB::table('scheme_applications')->whereIn('worker_id', $workerIds)->where('status', 'verified')->count();
        $approvedCount = DB::table('scheme_applications')->whereIn('worker_id', $workerIds)->where('status', 'approved')->count();
        $rejectedCount = DB::table('scheme_applications')->whereIn('worker_id', $workerIds)->where('status', 'rejected')->count();

        $stats = [
            'pending' => $pendingCount,
            'verified' => $verifiedCount,
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
        ];

        return view('admin.ngo_verifier_dashboard', compact('stats'));
    }

    public function schemeApplications(Request $request)
    {
        $this->guard();

        $status = $request->query('status', 'pending');
        $search = $request->query('search', '');
        $ngoId = Auth::user()->ngo_id;
        $ngoUserIds = $this->getNgoUserIds($ngoId);

        $workerIds = DB::table('workers')->whereIn('created_by', $ngoUserIds)->pluck('_id')->toArray();

        $query = DB::table('scheme_applications')
            ->whereIn('worker_id', $workerIds)
            ->where('status', $status);

        if (!empty($search)) {
            // Fetch workers matching search
            $matchingWorkerIds = DB::table('workers')
                ->whereIn('created_by', $ngoUserIds)
                ->where('full_name', 'like', '%' . $search . '%')
                ->pluck('_id')
                ->toArray();

            $query->where(function($q) use ($matchingWorkerIds, $search) {
                $q->whereIn('worker_id', $matchingWorkerIds)
                  ->orWhere('scheme_title', 'like', '%' . $search . '%');
            });
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.ngo_verifier_scheme_applications', compact('applications', 'status', 'search'));
    }

    public function review($id)
    {
        $this->guard();

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('ngo-verifier.scheme-applications')->with('error', 'Scheme application not found.');
        }

        $application = (array)$application;

        // Security check: ensure worker belongs to this NGO
        $ngoId = Auth::user()->ngo_id;
        $ngoUserIds = $this->getNgoUserIds($ngoId);
        $worker = DB::table('workers')->where('_id', $application['worker_id'])->first();
        if (!$worker || !in_array($worker->created_by ?? null, $ngoUserIds)) {
            abort(403, 'Unauthorized crossover to another NGO.');
        }

        $worker = (array)$worker;
        $scheme = DB::table('schemes')->where('_id', $application['scheme_id'])->first();
        $scheme = $scheme ? (array)$scheme : null;

        return view('admin.ngo_verifier_review_scheme', compact('application', 'worker', 'scheme'));
    }

    public function verify(Request $request, $id)
    {
        $this->guard();

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('ngo-verifier.scheme-applications')->with('error', 'Scheme application not found.');
        }

        $application = (array)$application;

        // Security check
        $ngoId = Auth::user()->ngo_id;
        $ngoUserIds = $this->getNgoUserIds($ngoId);
        $worker = DB::table('workers')->where('_id', $application['worker_id'])->first();
        if (!$worker || !in_array($worker->created_by ?? null, $ngoUserIds)) {
            abort(403, 'Unauthorized crossover to another NGO.');
        }

        DB::table('scheme_applications')->where('_id', $id)->update([
            'status' => 'verified',
            'verified_by' => Auth::id(),
            'verified_by_ngo_id' => $ngoId,
            'verified_at' => now()->toDateTimeString(),
            'verifier_remarks' => $request->input('remarks', 'Scheme application verified successfully.'),
        ]);

        AuditLogger::log('VERIFY', 'scheme_application', $id, $application['scheme_title'] ?? 'Scheme App', ['remarks' => $request->input('remarks')]);

        return redirect()->route('ngo-verifier.scheme-applications', ['status' => 'pending'])
            ->with('success', 'Scheme application verified successfully.');
    }

    public function reject(Request $request, $id)
    {
        $this->guard();

        $request->validate([
            'remarks' => 'required|string|min:3',
        ]);

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('ngo-verifier.scheme-applications')->with('error', 'Scheme application not found.');
        }

        $application = (array)$application;

        // Security check
        $ngoId = Auth::user()->ngo_id;
        $ngoUserIds = $this->getNgoUserIds($ngoId);
        $worker = DB::table('workers')->where('_id', $application['worker_id'])->first();
        if (!$worker || !in_array($worker->created_by ?? null, $ngoUserIds)) {
            abort(403, 'Unauthorized crossover to another NGO.');
        }

        DB::table('scheme_applications')->where('_id', $id)->update([
            'status' => 'rejected',
            'verified_by' => Auth::id(),
            'verified_by_ngo_id' => $ngoId,
            'verified_at' => now()->toDateTimeString(),
            'verifier_remarks' => $request->remarks,
        ]);

        AuditLogger::log('REJECT', 'scheme_application', $id, $application['scheme_title'] ?? 'Scheme App', ['remarks' => $request->remarks]);

        return redirect()->route('ngo-verifier.scheme-applications', ['status' => 'pending'])
            ->with('success', 'Scheme application rejected and returned to applicant.');
    }
}
