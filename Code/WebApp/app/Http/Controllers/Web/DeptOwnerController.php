<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DeptOwnerController extends Controller
{
    /**
     * Guard: only dept-owner role allowed.
     */
    private function guard()
    {
        if (Auth::user()->role !== 'dept-owner') {
            abort(403, 'Forbidden — Department Owner access only.');
        }
    }

    /**
     * Dashboard: stats overview for self-registered workers.
     */
    public function index()
    {
        $this->guard();
        $deptCode = Auth::user()->department_code;

        $pendingCount = DB::table('workers')
            ->where('registration_source', 'self')
            ->where('status', 'pending')
            ->when($deptCode, fn($q) => $q->where(fn($sq) => $sq->where('department_code', $deptCode)->orWhere('created_by_dept_code', $deptCode)))
            ->count();

        $verifiedCount = DB::table('workers')
            ->where('registration_source', 'self')
            ->where('status', 'verified')
            ->when($deptCode, fn($q) => $q->where(fn($sq) => $sq->where('department_code', $deptCode)->orWhere('created_by_dept_code', $deptCode)))
            ->count();

        $approvedCount = DB::table('workers')
            ->where('registration_source', 'self')
            ->where('status', 'approved')
            ->when($deptCode, fn($q) => $q->where(fn($sq) => $sq->where('department_code', $deptCode)->orWhere('created_by_dept_code', $deptCode)))
            ->count();

        $rejectedCount = DB::table('workers')
            ->where('registration_source', 'self')
            ->where('status', 'rejected')
            ->when($deptCode, fn($q) => $q->where(fn($sq) => $sq->where('department_code', $deptCode)->orWhere('created_by_dept_code', $deptCode)))
            ->count();

        return view('admin.dept_owner', compact('pendingCount', 'verifiedCount', 'approvedCount', 'rejectedCount'));
    }

    /**
     * List self-registered workers for approval/verification.
     */
    public function selfRegistrations(Request $request)
    {
        $this->guard();
        $deptCode = Auth::user()->department_code;

        $status = $request->query('status', 'pending');
        $search = $request->query('search', '');

        $query = DB::table('workers')->where('registration_source', 'self');

        if ($deptCode) {
            $query->where(fn($q) => $q->where('department_code', $deptCode)->orWhere('created_by_dept_code', $deptCode));
        }

        if ($status === 'approved') {
            $query->where('status', 'approved');
        } elseif ($status === 'verified') {
            $query->where('status', 'verified');
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        } else {
            $query->where('status', 'pending');
        }

        if (!empty($search)) {
            $query->where('full_name', 'like', '%' . $search . '%');
        }

        $workers = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.dept_owner_registrations', compact('workers', 'status', 'search'));
    }

    /**
     * Review a single self-registered worker profile.
     */
    public function review($id)
    {
        $this->guard();

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return redirect()->route('dept-owner.self-registrations')
                ->with('error', 'Worker record not found.');
        }

        $worker = (array) $worker;

        // Ensure it is self-registered
        if (($worker['registration_source'] ?? '') !== 'self') {
            return redirect()->route('dept-owner.self-registrations')
                ->with('error', 'This worker was not self-registered. You can only review self-registered workers.');
        }

        return view('admin.dept_owner_review', compact('worker'));
    }

    /**
     * Verify (first step) a self-registered worker.
     */
    public function verify(Request $request, $id)
    {
        $this->guard();

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return redirect()->route('dept-owner.self-registrations')
                ->with('error', 'Worker record not found.');
        }

        DB::table('workers')->where('_id', $id)->update([
            'status'           => 'verified',
            'is_verified'      => true,
            'verified_by'      => Auth::id(),
            'verified_at'      => now()->toDateTimeString(),
            'verifier_remarks' => $request->input('remarks', 'Profile verified by Department Owner.'),
        ]);

        return redirect()->route('dept-owner.self-registrations', ['status' => 'pending'])
            ->with('success', 'Self-registration verified successfully.');
    }

    /**
     * Approve (second step) a self-registered worker.
     */
    public function approve(Request $request, $id)
    {
        $this->guard();

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return redirect()->route('dept-owner.self-registrations')
                ->with('error', 'Worker record not found.');
        }

        DB::table('workers')->where('_id', $id)->update([
            'status'           => 'approved',
            'approved_by'      => Auth::id(),
            'approved_at'      => now()->toDateTimeString(),
            'approver_remarks' => $request->input('remarks', 'Profile approved by Department Owner.'),
        ]);

        return redirect()->route('dept-owner.self-registrations', ['status' => 'verified'])
            ->with('success', 'Self-registration approved successfully.');
    }

    /**
     * Reject a self-registered worker at any stage.
     */
    public function reject(Request $request, $id)
    {
        $this->guard();

        $request->validate(['remarks' => 'required|string|min:3']);

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return redirect()->route('dept-owner.self-registrations')
                ->with('error', 'Worker record not found.');
        }

        DB::table('workers')->where('_id', $id)->update([
            'status'           => 'rejected',
            'is_verified'      => false,
            'verified_by'      => Auth::id(),
            'verified_at'      => now()->toDateTimeString(),
            'verifier_remarks' => $request->remarks,
        ]);

        return redirect()->route('dept-owner.self-registrations', ['status' => 'pending'])
            ->with('success', 'Self-registration rejected and returned to worker for correction.');
    }
}
