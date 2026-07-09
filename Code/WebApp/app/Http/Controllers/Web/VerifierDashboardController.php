<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\AuditLogger;

class VerifierDashboardController extends Controller
{
    public function index()
    {
        if (!in_array(Auth::user()->role, ['verifier', 'dept-verifier'])) {
            abort(403, 'Forbidden');
        }

        $stats = $this->getStats();
        return view('admin.verifier_dashboard', compact('stats'));
    }

    public function beneficiaries(Request $request)
    {
        abort(403, 'Worker registration approvals are handled directly by the Owner Department Admin.');
    }

    public function schemes(Request $request)
    {
        if (!in_array(Auth::user()->role, ['verifier', 'dept-verifier'])) {
            abort(403);
        }
        return view('admin.schemes');
    }

    public function schemeApplications(Request $request)
    {
        if (!in_array(Auth::user()->role, ['verifier', 'dept-verifier'])) {
            abort(403);
        }

        $status = $request->query('status', 'pending');
        $search = $request->query('search', '');
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = $this->isOwnerDept();

        $query = DB::table('scheme_applications')->where('status', $status);
        if ($isOwnerDept) {
            $query->where(fn($q) => $q->whereNull('department_code')->orWhere('department_code', '')->orWhere('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode));
        } elseif ($deptCode) {
            $query->where(fn($q) => $q->where('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode));
        }

        if (!empty($search)) {
            $query->where('worker_name', 'like', '%' . $search . '%')
                  ->orWhere('scheme_title', 'like', '%' . $search . '%');
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.scheme_applications', compact('applications', 'status', 'search'));
    }

    public function grievances(Request $request)
    {
        if (!in_array(Auth::user()->role, ['verifier', 'dept-verifier'])) {
            abort(403);
        }

        $status = $request->query('status', 'new');
        $search = $request->query('search', '');
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = $this->isOwnerDept();

        $query = DB::table('grievances')->where('status', $status);
        if ($isOwnerDept) {
            $query->where(fn($q) => $q->whereNull('department_code')->orWhere('department_code', '')->orWhere('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode));
        } elseif ($deptCode) {
            $query->where(fn($q) => $q->where('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode));
        }

        if (!empty($search)) {
            $query->where('worker_name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
        }

        $grievances = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.grievances', compact('grievances', 'status', 'search'));
    }

    public function review($id)
    {
        if (!in_array(Auth::user()->role, ['verifier', 'dept-verifier'])) {
            abort(403);
        }

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('dept-verifier.scheme-applications')->with('error', 'Scheme application not found.');
        }

        $application = (array)$application;
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = $this->isOwnerDept();

        // Department check
        if (!$isOwnerDept && $deptCode && ($application['department_code'] ?? null) != $deptCode) {
            abort(403, 'Unauthorized crossover to another department.');
        }

        $worker = DB::table('workers')->where('_id', $application['worker_id'])->first();
        $worker = $worker ? (array)$worker : null;

        $scheme = DB::table('schemes')->where('_id', $application['scheme_id'])->first();
        $scheme = $scheme ? (array)$scheme : null;

        return view('admin.verifier_review', compact('application', 'worker', 'scheme'));
    }

    public function verify(Request $request, $id)
    {
        if (!in_array(Auth::user()->role, ['verifier', 'dept-verifier'])) {
            abort(403);
        }

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('dept-verifier.scheme-applications')->with('error', 'Scheme application not found.');
        }

        $application = (array)$application;
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = $this->isOwnerDept();

        if (!$isOwnerDept && $deptCode && ($application['department_code'] ?? null) != $deptCode) {
            abort(403, 'Unauthorized crossover to another department.');
        }

        DB::table('scheme_applications')->where('_id', $id)->update([
            'status' => 'verified',
            'verified_by' => Auth::id(),
            'verified_by_dept_code' => $deptCode,
            'verified_at' => now()->toDateTimeString(),
            'verifier_remarks' => $request->input('remarks', 'Scheme application verified.'),
        ]);

        AuditLogger::log('VERIFY', 'scheme_application', $id, $application['scheme_title'] ?? 'Scheme App', [
            'remarks' => $request->input('remarks')
        ]);

        $route = Auth::user()->role === 'verifier' ? 'verifier.scheme-applications' : 'dept-verifier.scheme-applications';
        return redirect()->route($route, ['status' => 'pending'])->with('success', 'Scheme application verified successfully.');
    }

    public function reject(Request $request, $id)
    {
        if (!in_array(Auth::user()->role, ['verifier', 'dept-verifier'])) {
            abort(403);
        }

        $request->validate([
            'remarks' => 'required|string|min:3',
        ]);

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('dept-verifier.scheme-applications')->with('error', 'Scheme application not found.');
        }

        $application = (array)$application;
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = $this->isOwnerDept();

        if (!$isOwnerDept && $deptCode && ($application['department_code'] ?? null) != $deptCode) {
            abort(403, 'Unauthorized crossover to another department.');
        }

        DB::table('scheme_applications')->where('_id', $id)->update([
            'status' => 'rejected',
            'verified_by' => Auth::id(),
            'verified_by_dept_code' => $deptCode,
            'rejected_at' => now()->toDateTimeString(),
            'verifier_remarks' => $request->input('remarks'),
        ]);

        AuditLogger::log('REJECT', 'scheme_application', $id, $application['scheme_title'] ?? 'Scheme App', [
            'remarks' => $request->input('remarks')
        ]);

        $route = Auth::user()->role === 'verifier' ? 'verifier.scheme-applications' : 'dept-verifier.scheme-applications';
        return redirect()->route($route, ['status' => 'pending'])->with('success', 'Scheme application rejected and returned.');
    }

    private function isOwnerDept()
    {
        $deptCode = Auth::user()->department_code;
        if (!$deptCode) {
            return false;
        }
        return User::where(function($q) use ($deptCode) {
            $q->where('department_code', (string)$deptCode)
              ->orWhere('department_code', (int)$deptCode);
        })->where('role', 'owner-dept-admin')->exists();
    }

    private function getStats()
    {
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = $this->isOwnerDept();

        // 2. Scheme Applications
        $totalSchemesCount = DB::table('schemes')->count();
        if ($totalSchemesCount === 0) {
            $totalSchemesCount = 14;
        }

        $pendingSchemesQuery = DB::table('scheme_applications')->where('status', 'pending');
        $approvedSchemesQuery = DB::table('scheme_applications')->where('status', 'approved');
        $rejectedSchemesQuery = DB::table('scheme_applications')->where('status', 'rejected');

        if ($isOwnerDept) {
            $selfRegFilter = fn($q) => $q->whereNull('department_code')->orWhere('department_code', '')->orWhere('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode);
            $pendingSchemesQuery->where($selfRegFilter);
            $approvedSchemesQuery->where($selfRegFilter);
            $rejectedSchemesQuery->where($selfRegFilter);
        } elseif ($deptCode) {
            $pendingSchemesQuery->where(fn($q) => $q->where('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode));
            $approvedSchemesQuery->where(fn($q) => $q->where('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode));
            $rejectedSchemesQuery->where(fn($q) => $q->where('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode));
        }

        // 3. Grievances
        $newGrievancesQuery = DB::table('grievances')->where('status', 'new');
        $pendingGrievancesQuery = DB::table('grievances')->where('status', 'pending');
        $disposedGrievancesQuery = DB::table('grievances')->where('status', 'disposed');

        if ($isOwnerDept) {
            $selfRegFilter = fn($q) => $q->whereNull('department_code')->orWhere('department_code', '')->orWhere('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode);
            $newGrievancesQuery->where($selfRegFilter);
            $pendingGrievancesQuery->where($selfRegFilter);
            $disposedGrievancesQuery->where($selfRegFilter);
        } elseif ($deptCode) {
            $newGrievancesQuery->where(fn($q) => $q->where('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode));
            $pendingGrievancesQuery->where(fn($q) => $q->where('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode));
            $disposedGrievancesQuery->where(fn($q) => $q->where('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode));
        }

        return [
            'beneficiaries' => [
                'pending' => 0,
                'approved' => 0,
                'deactivated' => 0,
            ],
            'schemes' => [
                'total' => $totalSchemesCount,
                'pending' => $pendingSchemesQuery->count(),
                'approved' => $approvedSchemesQuery->count(),
                'rejected' => $rejectedSchemesQuery->count(),
            ],
            'grievances' => [
                'new' => $newGrievancesQuery->count(),
                'pending' => $pendingGrievancesQuery->count(),
                'disposed' => $disposedGrievancesQuery->count(),
            ]
        ];
    }
}
