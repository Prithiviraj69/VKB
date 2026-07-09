<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Ngo;
use App\Models\Employer;
use App\Models\Contractor;
use Illuminate\Support\Facades\Hash;
use App\Services\AuditLogger;

class ApproverDashboardController extends Controller
{
    public function index()
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403, 'Forbidden');
        }

        $stats = $this->getStats();
        return view('admin.approver_dashboard', compact('stats'));
    }

    public function beneficiaries(Request $request)
    {
        abort(403, 'Worker registration approvals are handled directly by the Owner Department Admin.');
    }

    public function schemes(Request $request)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }
        return view('admin.schemes');
    }

    public function schemeApplications(Request $request)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
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
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
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
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('dept-approver.scheme-applications')->with('error', 'Scheme application not found.');
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

        return view('admin.approver_review', compact('application', 'worker', 'scheme'));
    }

    public function approve(Request $request, $id)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('dept-approver.scheme-applications')->with('error', 'Scheme application not found.');
        }

        $application = (array)$application;
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = $this->isOwnerDept();

        if (!$isOwnerDept && $deptCode && ($application['department_code'] ?? null) != $deptCode) {
            abort(403, 'Unauthorized crossover to another department.');
        }

        DB::table('scheme_applications')->where('_id', $id)->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_by_dept_code' => $deptCode,
            'approved_at' => now()->toDateTimeString(),
            'approver_remarks' => $request->input('remarks', 'Scheme application approved.'),
        ]);

        AuditLogger::log('APPROVE', 'scheme_application', $id, $application['scheme_title'] ?? 'Scheme App', [
            'remarks' => $request->input('remarks')
        ]);

        $route = Auth::user()->role === 'approver' ? 'approver.scheme-applications' : 'dept-approver.scheme-applications';
        return redirect()->route($route, ['status' => 'pending'])->with('success', 'Scheme application approved successfully.');
    }

    public function reject(Request $request, $id)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $request->validate([
            'remarks' => 'required|string|min:3',
        ]);

        $application = DB::table('scheme_applications')->where('_id', $id)->first();
        if (!$application) {
            return redirect()->route('dept-approver.scheme-applications')->with('error', 'Scheme application not found.');
        }

        $application = (array)$application;
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = $this->isOwnerDept();

        if (!$isOwnerDept && $deptCode && ($application['department_code'] ?? null) != $deptCode) {
            abort(403, 'Unauthorized crossover to another department.');
        }

        DB::table('scheme_applications')->where('_id', $id)->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_by_dept_code' => $deptCode,
            'rejected_at' => now()->toDateTimeString(),
            'approver_remarks' => $request->input('remarks'),
        ]);

        AuditLogger::log('REJECT', 'scheme_application', $id, $application['scheme_title'] ?? 'Scheme App', [
            'remarks' => $request->input('remarks')
        ]);

        $route = Auth::user()->role === 'approver' ? 'approver.scheme-applications' : 'dept-approver.scheme-applications';
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

    public function ngos(Request $request)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $search = $request->query('search', '');
        $userId = Auth::id();

        $query = Ngo::where('created_by', $userId);
        if (!empty($search)) {
            $query->where('ngo_name', 'like', '%' . $search . '%');
        }

        $ngos = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.approver_ngos', compact('ngos', 'search'));
    }

    public function createNgo(Request $request)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $request->validate([
            'ngo_name' => 'required|string|max:255',
            'registration_no' => 'required|string|max:255',
            'office_address' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'contact_person' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
        ]);

        Ngo::create([
            'ngo_name' => $request->ngo_name,
            'registration_no' => $request->registration_no,
            'office_address' => $request->office_address,
            'email' => $request->email,
            'contact_person' => $request->contact_person,
            'mobile' => $request->mobile,
            'created_by' => Auth::id(),
        ]);

        $u = User::create([
            'name' => $request->ngo_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'ngo',
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        AuditLogger::log('CREATE', 'ngo', $u->id, $request->ngo_name, ['email' => $request->email]);

        return redirect()->route('approver.ngos')->with('success', 'NGO onboarded successfully.');
    }

    public function deleteNgo($id)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $ngo = Ngo::findOrFail($id);
        $user = User::where('email', $ngo->email)->first();
        $ngoName = $ngo->ngo_name;
        $ngoEmail = $ngo->email;

        if ($user) {
            $hasEmployers = Employer::where('created_by', $user->id)->exists();
            $hasContractors = Contractor::where('created_by', $user->id)->exists();
            $hasWorkers = DB::table('workers')->where('created_by', $user->id)->exists();

            if ($hasEmployers || $hasContractors || $hasWorkers) {
                return back()->with('error', 'User transaction are recorded, so user cannot be deleted. Please deactive the user instead of deletion.');
            }
            $user->delete();
        }

        $ngo->delete();

        AuditLogger::log('DELETE', 'ngo', $user->id ?? $id, $ngoName, ['email' => $ngoEmail]);

        return redirect()->route('approver.ngos')->with('success', 'NGO deleted successfully.');
    }

    public function toggleNgoStatus($id)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $ngo = Ngo::findOrFail($id);
        $user = User::where('email', $ngo->email)->first();
        if ($user) {
            $user->is_active = !$user->is_active;
            $user->save();

            AuditLogger::log('TOGGLE_STATUS', 'ngo', $user->id ?? $ngo->id, $ngo->ngo_name, ['new_status' => $user->is_active ? 'active' : 'inactive']);

            return back()->with('success', 'NGO account status updated successfully.');
        }

        return back()->with('error', 'Associated login account not found.');
    }

    public function employers(Request $request)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $search = $request->query('search', '');
        $userId = Auth::id();

        $ngoUserIds = User::where('role', 'ngo')->where('created_by', $userId)->pluck('id')->toArray();

        $query = Employer::where(function($q) use ($userId, $ngoUserIds) {
            $q->where('created_by', $userId)->orWhereIn('created_by', $ngoUserIds);
        });

        if (!empty($search)) {
            $query->where('employer_name', 'like', '%' . $search . '%');
        }

        $employers = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.approver_employers', compact('employers', 'search'));
    }

    public function createEmployer(Request $request)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $request->validate([
            'employer_name' => 'required|string|max:255',
            'registration_no' => 'required|string|max:255',
            'office_address' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'contact_person' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
        ]);

        Employer::create([
            'employer_name' => $request->employer_name,
            'registration_no' => $request->registration_no,
            'office_address' => $request->office_address,
            'email' => $request->email,
            'contact_person' => $request->contact_person,
            'mobile' => $request->mobile,
            'created_by' => Auth::id(),
        ]);

        $u = User::create([
            'name' => $request->employer_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'employer',
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        AuditLogger::log('CREATE', 'employer', $u->id, $request->employer_name, ['email' => $request->email]);

        return redirect()->route('approver.employers')->with('success', 'Employer onboarded successfully.');
    }

    public function deleteEmployer($id)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $employer = Employer::findOrFail($id);
        $user = User::where('email', $employer->email)->first();
        $empName = $employer->employer_name;
        $empEmail = $employer->email;

        if ($user) {
            $hasContractors = Contractor::where('created_by', $user->id)->exists();
            $hasWorkers = DB::table('workers')->where('created_by', $user->id)->exists();

            if ($hasContractors || $hasWorkers) {
                return back()->with('error', 'User transaction are recorded, so user cannot be deleted. Please deactive the user instead of deletion.');
            }
            $user->delete();
        }

        $employer->delete();

        AuditLogger::log('DELETE', 'employer', $user->id ?? $id, $empName, ['email' => $empEmail]);

        return redirect()->route('approver.employers')->with('success', 'Employer deleted successfully.');
    }

    public function toggleEmployerStatus($id)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $employer = Employer::findOrFail($id);
        $user = User::where('email', $employer->email)->first();
        if ($user) {
            $user->is_active = !$user->is_active;
            $user->save();

            AuditLogger::log('TOGGLE_STATUS', 'employer', $user->id ?? $employer->id, $employer->employer_name, ['new_status' => $user->is_active ? 'active' : 'inactive']);

            return back()->with('success', 'Employer account status updated successfully.');
        }

        return back()->with('error', 'Associated login account not found.');
    }

    public function contractors(Request $request)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $search = $request->query('search', '');
        $userId = Auth::id();

        $ngoUserIds = User::where('role', 'ngo')->where('created_by', $userId)->pluck('id')->toArray();
        $employerUserIds = User::where('role', 'employer')
            ->where(function($q) use ($userId, $ngoUserIds) {
                $q->where('created_by', $userId)->orWhereIn('created_by', $ngoUserIds);
            })
            ->pluck('id')
            ->toArray();

        $query = Contractor::where(function($q) use ($userId, $ngoUserIds, $employerUserIds) {
            $q->where('created_by', $userId)
              ->orWhereIn('created_by', $ngoUserIds)
              ->orWhereIn('created_by', $employerUserIds);
        });

        if (!empty($search)) {
            $query->where('contractor_name', 'like', '%' . $search . '%');
        }

        $contractors = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.approver_contractors', compact('contractors', 'search'));
    }

    public function createContractor(Request $request)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $request->validate([
            'contractor_name' => 'required|string|max:255',
            'license_no' => 'required|string|max:255',
            'office_address' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'mobile' => 'required|string|max:20',
        ]);

        Contractor::create([
            'contractor_name' => $request->contractor_name,
            'license_no' => $request->license_no,
            'office_address' => $request->office_address,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'created_by' => Auth::id(),
        ]);

        $u = User::create([
            'name' => $request->contractor_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'contractor',
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        AuditLogger::log('CREATE', 'contractor', $u->id, $request->contractor_name, ['email' => $request->email]);

        return redirect()->route('approver.contractors')->with('success', 'Contractor onboarded successfully.');
    }

    public function deleteContractor($id)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $contractor = Contractor::findOrFail($id);
        $user = User::where('email', $contractor->email)->first();
        $cntName = $contractor->contractor_name;
        $cntEmail = $contractor->email;

        if ($user) {
            $hasWorkers = DB::table('workers')->where('created_by', $user->id)->exists();
            if ($hasWorkers) {
                return back()->with('error', 'User transaction are recorded, so user cannot be deleted. Please deactive the user instead of deletion.');
            }
            $user->delete();
        }

        $contractor->delete();

        AuditLogger::log('DELETE', 'contractor', $user->id ?? $id, $cntName, ['email' => $cntEmail]);

        return redirect()->route('approver.contractors')->with('success', 'Contractor deleted successfully.');
    }

    public function toggleContractorStatus($id)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $contractor = Contractor::findOrFail($id);
        $user = User::where('email', $contractor->email)->first();
        if ($user) {
            $user->is_active = !$user->is_active;
            $user->save();

            AuditLogger::log('TOGGLE_STATUS', 'contractor', $user->id ?? $contractor->id, $contractor->contractor_name, ['new_status' => $user->is_active ? 'active' : 'inactive']);

            return back()->with('success', 'Contractor account status updated successfully.');
        }

        return back()->with('error', 'Associated login account not found.');
    }

    public function createBeneficiary(Request $request)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'father_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'required|string',
            'marital_status' => 'required|string',
            'aadhaar_number' => 'required|string|max:16',
            'aadhaar_address' => 'required|string',
            'job_profile' => 'required|string',
            'bank_account_number' => 'required|string',
            'bank_name_branch' => 'required|string',
            'bank_ifsc' => 'required|string',
        ]);

        $user = User::create([
            'mobile' => $request->mobile,
            'name' => $request->full_name,
            'address' => $request->aadhaar_address,
            'job_profile' => $request->job_profile,
            'trade' => $request->job_profile,
            'role' => 'beneficiary',
            'is_active' => true,
            'is_verified' => false,
            'created_by' => Auth::id(),
            'created_by_dept_code' => Auth::user()->department_code,
        ]);

        DB::table('workers')->insert([
            'user_id' => $user->id,
            'full_name' => $request->full_name,
            'father_name' => $request->father_name,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'aadhaar_number' => $request->aadhaar_number,
            'aadhaar_address' => $request->aadhaar_address,
            'job_profile' => $request->job_profile,
            'bank_account_number' => $request->bank_account_number,
            'bank_name_branch' => $request->bank_name_branch,
            'bank_ifsc' => $request->bank_ifsc,
            'is_verified' => true,
            'status' => 'approved',
            'registration_no' => 'REG-' . date('Y') . '-' . rand(100000, 999999),
            'department_code' => Auth::user()->department_code,
            'created_by' => Auth::id(),
            'created_by_dept_code' => Auth::user()->department_code,
            'approved_by' => Auth::id(),
            'approved_at' => now()->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
            
            // Mobile app synchronization fields
            'worker_code' => 'WK' . strtoupper(uniqid()),
            'profile_score' => 0,
            'date_first_subscription' => null,
            'date_last_subscription' => null,
            'installments_paid_count' => 0,
            'installments_paid_total' => 0,
            'is_membership_revived' => false,
        ]);

        AuditLogger::log('CREATE', 'worker', $user->id, $request->full_name, ['email' => $request->email]);

        return redirect()->route('approver.beneficiaries')->with('success', 'Beneficiary registered and approved successfully.');
    }

    public function deleteBeneficiary($id)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $worker = DB::table('workers')->where('_id', $id)->orWhere('id', $id)->first();
        if (!$worker) {
            return redirect()->route('approver.beneficiaries')->with('error', 'Worker not found.');
        }

        $worker = (array) $worker;
        $workerId = $worker['id'] ?? $worker['_id'] ?? null;
        $userId = $worker['user_id'] ?? null;
        $wName = $worker['full_name'] ?? 'Worker';

        $hasSchemes = DB::table('scheme_applications')->where('worker_id', $workerId)->exists();
        $hasGrievances = DB::table('grievances')->where('worker_id', $workerId)->exists();

        if ($hasSchemes || $hasGrievances) {
            return back()->with('error', 'User transaction are recorded, so user cannot be deleted. Please deactive the user instead of deletion.');
        }

        if ($workerId) {
            DB::table('worker_family')->where('worker_id', $workerId)->delete();
            DB::table('worker_documents')->where('worker_id', $workerId)->delete();
            DB::table('worker_skills')->where('worker_id', $workerId)->delete();
        }

        DB::table('workers')->where('_id', $workerId)->orWhere('id', $workerId)->delete();

        if ($userId) {
            User::where('id', $userId)->delete();
        }

        AuditLogger::log('DELETE', 'worker', $userId ?? $id, $wName);

        return redirect()->route('approver.beneficiaries')->with('success', 'Beneficiary deleted successfully.');
    }

    public function toggleBeneficiaryStatus($id)
    {
        if (!in_array(Auth::user()->role, ['approver', 'dept-approver'])) {
            abort(403);
        }

        $worker = DB::table('workers')->where('_id', $id)->orWhere('id', $id)->first();
        if ($worker) {
            $worker = (array) $worker;
            $userId = $worker['user_id'] ?? null;
            $wName = $worker['full_name'] ?? 'Worker';
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->is_active = !$user->is_active;
                    $user->save();

                    AuditLogger::log('TOGGLE_STATUS', 'worker', $userId, $wName, ['new_status' => $user->is_active ? 'active' : 'inactive']);

                    return back()->with('success', 'Beneficiary account status updated successfully.');
                }
            }
        }

        return back()->with('error', 'Associated login account not found.');
    }
}
