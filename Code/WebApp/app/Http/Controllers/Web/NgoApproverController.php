<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\AuditLogger;

class NgoApproverController extends Controller
{
    private function guard()
    {
        if (Auth::user()->role !== 'ngo-approver') {
            abort(403, 'Forbidden — NGO Approver access only.');
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

        // Worker stats
        $pendingWorkers = DB::table('workers')->whereIn('created_by', $ngoUserIds)->where(function($q) {
            $q->where('status', 'pending')
              ->orWhereNull('status')
              ->orWhere('status', '');
        })->count();

        $approvedWorkers = DB::table('workers')->whereIn('created_by', $ngoUserIds)->where('status', 'approved')->count();

        // Scheme application stats
        $workerIds = DB::table('workers')->whereIn('created_by', $ngoUserIds)->pluck('_id')->toArray();

        // Pending approval for approver = verified by verifier
        $pendingSchemes = DB::table('scheme_applications')->whereIn('worker_id', $workerIds)->where('status', 'verified')->count();
        $approvedSchemes = DB::table('scheme_applications')->whereIn('worker_id', $workerIds)->where('status', 'approved')->count();
        $rejectedSchemes = DB::table('scheme_applications')->whereIn('worker_id', $workerIds)->where('status', 'rejected')->count();

        $stats = [
            'workers_pending' => $pendingWorkers,
            'workers_approved' => $approvedWorkers,
            'schemes_pending' => $pendingSchemes,
            'schemes_approved' => $approvedSchemes,
            'schemes_rejected' => $rejectedSchemes,
        ];

        return view('admin.ngo_approver_dashboard', compact('stats'));
    }

    public function beneficiaries(Request $request)
    {
        $this->guard();

        $status = $request->query('status', 'pending');
        $search = $request->query('search', '');
        $ngoId = Auth::user()->ngo_id;
        $ngoUserIds = $this->getNgoUserIds($ngoId);

        $query = DB::table('workers')->whereIn('created_by', $ngoUserIds);

        if ($status === 'approved') {
            $query->where('status', 'approved');
        } elseif ($status === 'deactivated') {
            $deactivatedUserIds = User::where('role', 'worker')->where('is_active', false)->pluck('id')->toArray();
            $query->whereIn('user_id', $deactivatedUserIds);
        } else {
            $query->where(function($q) {
                $q->where('status', 'pending')
                  ->orWhereNull('status')
                  ->orWhere('status', '')
                  ->orWhere('status', 'rejected');
            })->where('status', '!=', 'approved');
        }

        if (!empty($search)) {
            $query->where('full_name', 'like', '%' . $search . '%');
        }

        $workers = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        foreach ($workers->items() as $w) {
            $wId = $w->_id ?? $w->id ?? null;
            $w->family_members = \App\Models\Worker::getFamilyMembers($wId, $w->family_members ?? []);
        }

        return view('admin.ngo_approver_beneficiaries', compact('workers', 'status', 'search'));
    }

    public function reviewBeneficiary($id)
    {
        $this->guard();

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return redirect()->route('ngo-approver.beneficiaries')->with('error', 'Worker record not found.');
        }

        $worker = (array)$worker;
        $wId = $worker['_id'] ?? $worker['id'] ?? null;
        $worker['family_members'] = \App\Models\Worker::getFamilyMembers($wId, $worker['family_members'] ?? []);

        // Security check
        $ngoId = Auth::user()->ngo_id;
        $ngoUserIds = $this->getNgoUserIds($ngoId);
        if (!in_array($worker['created_by'] ?? null, $ngoUserIds)) {
            abort(403, 'Unauthorized crossover to another NGO.');
        }

        return view('admin.ngo_approver_review_beneficiary', compact('worker'));
    }

    public function approveBeneficiary(Request $request, $id)
    {
        $this->guard();

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return redirect()->route('ngo-approver.beneficiaries')->with('error', 'Worker record not found.');
        }

        $worker = (array)$worker;

        // Security check
        $ngoId = Auth::user()->ngo_id;
        $ngoUserIds = $this->getNgoUserIds($ngoId);
        if (!in_array($worker['created_by'] ?? null, $ngoUserIds)) {
            abort(403, 'Unauthorized crossover to another NGO.');
        }

        $regNo = 'REG-NGO-' . date('Y') . '-' . rand(100000, 999999);

        DB::table('workers')->where('_id', $id)->update([
            'status' => 'approved',
            'is_verified' => true,
            'registration_no' => $regNo,
            'approved_by' => Auth::id(),
            'approved_by_ngo_id' => $ngoId,
            'approved_at' => now()->toDateTimeString(),
            'approver_remarks' => $request->input('remarks', 'Profile approved and registration number issued by NGO Approver.'),
        ]);

        AuditLogger::log('APPROVE', 'worker', $worker['user_id'] ?? $id, $worker['full_name'] ?? 'Worker', ['remarks' => $request->input('remarks'), 'registration_no' => $regNo]);

        return redirect()->route('ngo-approver.beneficiaries', ['status' => 'pending'])
            ->with('success_reg', 'Worker profile approved successfully. Issued Registration No: ' . $regNo);
    }

    public function rejectBeneficiary(Request $request, $id)
    {
        $this->guard();

        $request->validate([
            'remarks' => 'required|string|min:3',
        ]);

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return redirect()->route('ngo-approver.beneficiaries')->with('error', 'Worker record not found.');
        }

        $worker = (array)$worker;

        // Security check
        $ngoId = Auth::user()->ngo_id;
        $ngoUserIds = $this->getNgoUserIds($ngoId);
        if (!in_array($worker['created_by'] ?? null, $ngoUserIds)) {
            abort(403, 'Unauthorized crossover to another NGO.');
        }

        DB::table('workers')->where('_id', $id)->update([
            'status' => 'rejected',
            'is_verified' => false,
            'approved_by' => Auth::id(),
            'approved_by_ngo_id' => $ngoId,
            'approved_at' => now()->toDateTimeString(),
            'approver_remarks' => $request->remarks,
        ]);

        AuditLogger::log('REJECT', 'worker', $worker['user_id'] ?? $id, $worker['full_name'] ?? 'Worker', ['remarks' => $request->remarks]);

        return redirect()->route('ngo-approver.beneficiaries', ['status' => 'pending'])
            ->with('success', 'Worker profile rejected and returned for corrections.');
    }

    public function schemeApplications(Request $request)
    {
        $this->guard();

        $status = $request->query('status', 'pending');
        $search = $request->query('search', '');
        $ngoId = Auth::user()->ngo_id;
        $ngoUserIds = $this->getNgoUserIds($ngoId);

        $workerIds = DB::table('workers')->whereIn('created_by', $ngoUserIds)->pluck('_id')->toArray();

        // Pending approval for approver = verified by verifier
        $dbStatus = $status === 'pending' ? 'verified' : $status;

        $query = DB::table('scheme_applications')
            ->whereIn('worker_id', $workerIds)
            ->where('status', $dbStatus);

        if (!empty($search)) {
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

        return view('admin.ngo_approver_scheme_applications', compact('applications', 'status', 'search'));
    }

    public function reviewScheme($id)
    {
        $this->guard();

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('ngo-approver.scheme-applications')->with('error', 'Scheme application not found.');
        }

        $application = (array)$application;

        // Security check
        $ngoId = Auth::user()->ngo_id;
        $ngoUserIds = $this->getNgoUserIds($ngoId);
        $worker = DB::table('workers')->where('_id', $application['worker_id'])->first();
        if (!$worker || !in_array($worker->created_by ?? null, $ngoUserIds)) {
            abort(403, 'Unauthorized crossover to another NGO.');
        }

        $worker = (array)$worker;
        $scheme = DB::table('schemes')->where('_id', $application['scheme_id'])->first();
        $scheme = $scheme ? (array)$scheme : null;

        return view('admin.ngo_approver_review_scheme', compact('application', 'worker', 'scheme'));
    }

    public function approveScheme(Request $request, $id)
    {
        $this->guard();

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('ngo-approver.scheme-applications')->with('error', 'Scheme application not found.');
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
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_by_ngo_id' => $ngoId,
            'approved_at' => now()->toDateTimeString(),
            'approver_remarks' => $request->input('remarks', 'Scheme application approved successfully by NGO Approver.'),
        ]);

        AuditLogger::log('APPROVE', 'scheme_application', $id, $application['scheme_title'] ?? 'Scheme App', ['remarks' => $request->input('remarks')]);

        return redirect()->route('ngo-approver.scheme-applications', ['status' => 'pending'])
            ->with('success', 'Scheme application approved successfully.');
    }

    public function rejectScheme(Request $request, $id)
    {
        $this->guard();

        $request->validate([
            'remarks' => 'required|string|min:3',
        ]);

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('ngo-approver.scheme-applications')->with('error', 'Scheme application not found.');
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
            'approved_by' => Auth::id(),
            'approved_by_ngo_id' => $ngoId,
            'approved_at' => now()->toDateTimeString(),
            'approver_remarks' => $request->remarks,
        ]);

        AuditLogger::log('REJECT', 'scheme_application', $id, $application['scheme_title'] ?? 'Scheme App', ['remarks' => $request->remarks]);

        return redirect()->route('ngo-approver.scheme-applications', ['status' => 'pending'])
            ->with('success', 'Scheme application rejected and returned to applicant.');
    }
}
