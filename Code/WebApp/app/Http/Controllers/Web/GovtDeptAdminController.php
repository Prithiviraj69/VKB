<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\AuditLogger;

class GovtDeptAdminController extends Controller
{
    private function guard()
    {
        if (!in_array(Auth::user()->role, ['govt-dept-admin', 'owner-dept-admin'])) {
            abort(403, 'Forbidden — Department Admin access only.');
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Dashboard
    // ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $this->guard();

        $adminId  = Auth::id();
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = Auth::user()->role === 'owner-dept-admin';

        $verifierCount  = User::whereIn('role', ['dept-verifier', 'verifier'])
            ->where(fn($q) => $q->where('department_code', $deptCode)->orWhere('created_by_dept_code', $deptCode))
            ->count();
        $approverCount  = User::whereIn('role', ['dept-approver', 'approver'])
            ->where(fn($q) => $q->where('department_code', $deptCode)->orWhere('created_by_dept_code', $deptCode))
            ->count();

        $pendingWorkers  = DB::table('workers')->where(function($q) {
                $q->where('status', 'pending')
                  ->orWhere('status', 'rejected')
                  ->orWhereNull('status')
                  ->orWhere('status', '')
                  ->orWhere('is_verified', false)
                  ->orWhereNull('is_verified');
            })->where('status', '!=', 'verified')->where('status', '!=', 'approved')
            ->when(!$isOwnerDept && $deptCode, fn($q) => $q->where(fn($sq) => $sq->where('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode)->orWhere('created_by_dept_code', (string)$deptCode)->orWhere('created_by_dept_code', (int)$deptCode)))->count();
        $verifiedWorkers = DB::table('workers')->where('status', 'verified')
            ->when(!$isOwnerDept && $deptCode, fn($q) => $q->where(fn($sq) => $sq->where('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode)->orWhere('created_by_dept_code', (string)$deptCode)->orWhere('created_by_dept_code', (int)$deptCode)))->count();
        $approvedWorkers = DB::table('workers')->where('status', 'approved')
            ->when(!$isOwnerDept && $deptCode, fn($q) => $q->where(fn($sq) => $sq->where('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode)->orWhere('created_by_dept_code', (string)$deptCode)->orWhere('created_by_dept_code', (int)$deptCode)))->count();

        $departments = [];
        $ngos = [];
        $employers = [];
        $contractors = [];
        $activeWorkersCount = 0;
        $inactiveWorkersCount = 0;
        $govtDeptsCount = 0;
        $ngosCount = 0;
        $employersCount = 0;
        $contractorsCount = 0;

        $departments = $isOwnerDept
            ? Department::orderBy('created_at', 'desc')->get()
            : Department::where(fn($q) => $q->where('department_code', $deptCode)->orWhere('created_by_dept_code', $deptCode))->orderBy('created_at', 'desc')->get();
        $ngos = $isOwnerDept
            ? \App\Models\Ngo::orderBy('created_at', 'desc')->get()
            : \App\Models\Ngo::where(fn($q) => $q->where('created_by_dept_code', $deptCode)->orWhere('department_code', $deptCode))->orderBy('created_at', 'desc')->get();
        $employers = $isOwnerDept
            ? \App\Models\Employer::orderBy('created_at', 'desc')->get()
            : \App\Models\Employer::where(fn($q) => $q->where('created_by_dept_code', $deptCode)->orWhere('department_code', $deptCode))->orderBy('created_at', 'desc')->get();
        $contractors = $isOwnerDept
            ? \App\Models\Contractor::orderBy('created_at', 'desc')->get()
            : \App\Models\Contractor::where(fn($q) => $q->where('created_by_dept_code', $deptCode)->orWhere('department_code', $deptCode))->orderBy('created_at', 'desc')->get();

        $govtDeptsCount = count($departments);
        $ngosCount = count($ngos);
        $employersCount = count($employers);
        $contractorsCount = count($contractors);

        if ($isOwnerDept) {
            $ngoIds = $ngos->pluck('id')->toArray();

            $deptUserIds = DB::table('workers')
                ->pluck('user_id')
                ->filter()
                ->toArray();

            // Include both 'approved' AND 'verified' workers for active count
            $approvedUserIds = DB::table('workers')
                ->whereIn('status', ['approved', 'verified'])
                ->pluck('user_id')
                ->filter()
                ->toArray();

            $activeWorkersCount = User::whereIn('id', $approvedUserIds)
                ->where(function($q) {
                    $q->where('is_active', true)
                      ->orWhereNull('is_active');
                })
                ->count();

            $inactiveWorkersCount = User::whereIn('id', $approvedUserIds)
                ->where('is_active', false)
                ->count();
        }

        // Get all workers for this department to calculate trade counts
        $tradeCountsQuery = DB::table('workers')
            ->when(!$isOwnerDept && $deptCode, fn($q) => $q->where(fn($sq) => $sq->whereIn('department_code', [(string)$deptCode, (int)$deptCode])->orWhereIn('created_by_dept_code', [(string)$deptCode, (int)$deptCode])));
        
        $tradeRows = $tradeCountsQuery->get(['job_profile']);
        $tradeBreakdown = [];
        foreach ($tradeRows as $row) {
            $row = (array)$row;
            $profile = $row['job_profile'] ?? 'Unspecified';
            if (empty($profile)) {
                $profile = 'Unspecified';
            }
            if (!isset($tradeBreakdown[$profile])) {
                $tradeBreakdown[$profile] = 0;
            }
            $tradeBreakdown[$profile]++;
        }
        arsort($tradeBreakdown);

        $department = Department::where('department_code', $deptCode)->first();

        $workers = [];
        $status = $request->query('status');
        if ($isOwnerDept) {
            $query = DB::table('workers');

            $activeStatus = $status ?: 'pending';

            if ($activeStatus === 'approved') {
                $query->where('status', 'approved');
            } elseif ($activeStatus === 'verified') {
                $query->where('status', 'verified');
            } elseif ($activeStatus === 'pending') {
                $query->where(function($q) {
                    $q->where('status', 'pending')
                      ->orWhere('status', 'rejected')
                      ->orWhereNull('status')
                      ->orWhere('status', '')
                      ->orWhere('is_verified', false)
                      ->orWhereNull('is_verified');
                })->where('status', '!=', 'verified')->where('status', '!=', 'approved');
            } elseif ($activeStatus === 'active') {
                $activeUserIds = User::whereIn('id', $approvedUserIds)
                    ->where(function($q) {
                        $q->where('is_active', true)
                          ->orWhereNull('is_active');
                    })
                    ->pluck('id')->toArray();
                $query->whereIn('user_id', $activeUserIds)->where('status', 'approved');
            } elseif ($activeStatus === 'inactive') {
                $inactiveUserIds = User::whereIn('id', $approvedUserIds)
                    ->where('is_active', false)
                    ->pluck('id')->toArray();
                $query->whereIn('user_id', $inactiveUserIds)->where('status', 'approved');
            }

            $jobProfile = $request->query('job_profile');
            if (!empty($jobProfile)) {
                $query->where('job_profile', $jobProfile);
            }

            $workers = $query->orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();

            // Pre-generate signed review URLs for each worker
            $workers->getCollection()->transform(function($worker) {
                $workerArray = (array) $worker;
                $wId = (string) ($workerArray['_id'] ?? $workerArray['id'] ?? '');
                $workerArray['signed_review_url'] = \Illuminate\Support\Facades\URL::signedRoute(
                    'owner-dept.review-registration', 
                    ['id' => $wId]
                );
                return (object) $workerArray;
            });
        }

        $activeSchemesCount = DB::table('schemes')
            ->where(function($q) {
                $q->whereNull('status')
                  ->orWhere('status', 'active')
                  ->orWhere('is_active', '!=', false);
            })
            ->count();
        $inactiveSchemesCount = DB::table('schemes')
            ->where(function($q) {
                $q->where('status', 'inactive')
                  ->orWhere('is_active', false);
            })
            ->count();
        $totalAppsCount = DB::table('scheme_applications')
            ->when($deptCode, fn($q) => $q->whereIn('department_code', [(string)$deptCode, (int)$deptCode]))
            ->count();

        $schemesList = [];
        if ($isOwnerDept && $status === 'scheme_applications') {
            $rawSchemes = DB::table('schemes')->orderBy('created_at', 'desc')->get();
            foreach ($rawSchemes as $sch) {
                $sch = (array)$sch;
                $schIdStr = (string)($sch['id'] ?? $sch['_id'] ?? '');
                
                // Count applications
                $appCount = DB::table('scheme_applications')
                    ->where('scheme_id', $schIdStr)
                    ->when($deptCode, fn($q) => $q->whereIn('department_code', [(string)$deptCode, (int)$deptCode]))
                    ->count();
                
                $sch['applications_count'] = $appCount;
                $schemesList[] = $sch;
            }
        }

        $selectedScheme = null;
        $beneficiaries = [];
        if ($isOwnerDept && $status === 'scheme_beneficiaries') {
            $schemeId = request()->query('scheme_id');
            $selectedScheme = DB::table('schemes')->where('_id', $schemeId)->first();
            if (!$selectedScheme) {
                $selectedScheme = DB::table('schemes')->where('id', $schemeId)->first();
            }
            $selectedScheme = (array)$selectedScheme;

            $beneficiaries = DB::table('scheme_applications')
                ->where('scheme_id', $schemeId)
                ->when($deptCode, fn($q) => $q->whereIn('department_code', [(string)$deptCode, (int)$deptCode]))
                ->orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();
        }

        $jobProfile = $request->query('job_profile');

        // Total beneficiaries across ALL statuses (accurate overall count)
        $totalBeneficiariesCount = $pendingWorkers + $verifiedWorkers + $approvedWorkers;

        return view('admin.govt_dept_admin', compact(
            'verifierCount', 'approverCount',
            'pendingWorkers', 'verifiedWorkers', 'approvedWorkers', 'totalBeneficiariesCount',
            'department', 'workers', 'isOwnerDept', 'status',
            'departments', 'ngos', 'employers', 'contractors',
            'activeWorkersCount', 'inactiveWorkersCount',
            'govtDeptsCount', 'ngosCount', 'employersCount', 'contractorsCount',
            'activeSchemesCount', 'inactiveSchemesCount', 'totalAppsCount', 'schemesList',
            'selectedScheme', 'beneficiaries', 'tradeBreakdown', 'jobProfile'
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // User Management (Verifiers & Approvers)
    // ─────────────────────────────────────────────────────────────────
    public function users(Request $request)
    {
        $this->guard();

        $adminId  = Auth::id();
        $search   = $request->query('search', '');
        $roleFilter = $request->query('role', '');

        $query = User::whereIn('role', ['dept-verifier', 'dept-approver', 'verifier', 'approver'])
            ->where('department_code', Auth::user()->department_code);

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }
        if ($search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                                       ->orWhere('email', 'like', "%{$search}%"));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.govt_dept_admin_users', compact('users', 'search', 'roleFilter'));
    }

    // ─────────────────────────────────────────────────────────────────
    // Create Verifier
    // ─────────────────────────────────────────────────────────────────
    public function createVerifier(Request $request)
    {
        $this->guard();

        // DB Constraint check: 1 verifier per department
        $exists = User::where('department_code', Auth::user()->department_code)
            ->where('role', 'dept-verifier')
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['email' => 'A Verifier already exists for this department. Only one verifier is allowed.']);
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'mobile'      => 'required|string|max:20',
            'designation' => 'required|string|max:255',
        ]);

        $u = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'mobile'          => $request->mobile,
            'designation'     => $request->designation,
            'role'            => 'dept-verifier',
            'is_active'       => true,
            'department_code' => Auth::user()->department_code,
            'created_by'      => Auth::id(),
        ]);

        AuditLogger::log('CREATE', 'verifier', $u->id, $request->name, ['email' => $request->email]);

        return redirect()->route('govt-dept-admin.users')
            ->with('success', 'Verifier account created successfully.');
    }

    // ─────────────────────────────────────────────────────────────────
    // Create Approver
    // ─────────────────────────────────────────────────────────────────
    public function createApprover(Request $request)
    {
        $this->guard();

        // DB Constraint check: 1 approver per department
        $exists = User::where('department_code', Auth::user()->department_code)
            ->where('role', 'dept-approver')
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['email' => 'An Approver already exists for this department. Only one approver is allowed.']);
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'mobile'      => 'required|string|max:20',
            'designation' => 'required|string|max:255',
        ]);

        $u = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'mobile'          => $request->mobile,
            'designation'     => $request->designation,
            'role'            => 'dept-approver',
            'is_active'       => true,
            'department_code' => Auth::user()->department_code,
            'created_by'      => Auth::id(),
        ]);

        AuditLogger::log('CREATE', 'approver', $u->id, $request->name, ['email' => $request->email]);

        return redirect()->route('govt-dept-admin.users')
            ->with('success', 'Approver account created successfully.');
    }

    // ─────────────────────────────────────────────────────────────────
    // Toggle Verifier / Approver Status
    // ─────────────────────────────────────────────────────────────────
    public function toggleStatus($id)
    {
        $this->guard();

        $user = User::findOrFail($id);

        // Security: only manage users in your department
        if ($user->department_code !== Auth::user()->department_code ||
            !in_array($user->role, ['dept-verifier', 'dept-approver', 'verifier', 'approver'])) {
            return back()->with('error', 'Unauthorized action.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        AuditLogger::log('TOGGLE_STATUS', 'user', $user->id, $user->name, ['new_status' => $user->is_active ? 'active' : 'inactive', 'role' => $user->role]);

        return back()->with('success', 'User status updated successfully.');
    }

    // ─────────────────────────────────────────────────────────────────
    // Delete Verifier / Approver
    // ─────────────────────────────────────────────────────────────────
    public function deleteUser($id)
    {
        $this->guard();

        $user = User::findOrFail($id);

        // Security: only delete users in your department
        if ($user->department_code !== Auth::user()->department_code ||
            !in_array($user->role, ['dept-verifier', 'dept-approver', 'verifier', 'approver'])) {
            return back()->with('error', 'Unauthorized action.');
        }

        // Transaction guard: block deletion if user has touched workers/schemes/grievances
        $hasWorkerTx = DB::table('workers')
            ->where('verified_by', $user->id)
            ->orWhere('approved_by', $user->id)
            ->exists();

        $hasSchemeTx = DB::table('scheme_applications')
            ->where('verified_by', $user->id)
            ->orWhere('approved_by', $user->id)
            ->exists();

        $hasGrievanceTx = DB::table('grievances')
            ->where('verified_by', $user->id)
            ->orWhere('approved_by', $user->id)
            ->exists();

        if ($hasWorkerTx || $hasSchemeTx || $hasGrievanceTx) {
            return back()->with('error',
                'Cannot delete: this user has existing transaction records. Please deactivate instead.');
        }

        $uName = $user->name;
        $uRole = $user->role;
        $user->delete();

        AuditLogger::log('DELETE', 'user', $id, $uName, ['role' => $uRole]);

        return back()->with('success', 'User deleted successfully.');
    }

    public function exportLabourCsv()
    {
        if (Auth::user()->role !== 'owner-dept-admin') {
            abort(403, 'Forbidden');
        }

        $deptCode = Auth::user()->department_code;

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="self_registered_workers_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($deptCode) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'DOB', 'Gender', 'Father Name', 'Aadhaar Number', 'Bank Account', 'Bank Name & Branch', 'IFSC', 'Status', 'Registration No', 'Created At']);
            
            $workers = DB::table('workers')
                ->where(fn($q) => $q->whereNull('department_code')->orWhere('department_code', '')->orWhere('department_code', (string)$deptCode)->orWhere('department_code', (int)$deptCode)->orWhere('created_by_dept_code', (string)$deptCode)->orWhere('created_by_dept_code', (int)$deptCode))
                ->orderBy('created_at', 'desc')->get();
                
            foreach ($workers as $w) {
                $w = (array)$w;
                fputcsv($file, [
                    $w['id'] ?? $w['_id'] ?? '',
                    $w['full_name'] ?? 'N/A',
                    $w['dob'] ?? 'N/A',
                    $w['gender'] ?? 'N/A',
                    $w['father_name'] ?? 'N/A',
                    $w['aadhaar_number'] ?? 'N/A',
                    $w['bank_account_number'] ?? 'N/A',
                    $w['bank_name_branch'] ?? 'N/A',
                    $w['bank_ifsc'] ?? 'N/A',
                    $w['status'] ?? 'pending',
                    $w['registration_no'] ?? 'N/A',
                    $w['created_at'] ?? 'N/A',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function onboardHub()
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
        }
        $deptCode = Auth::user()->department_code;

        $ngos = \App\Models\Ngo::where(fn($q) => $q->where('created_by_dept_code', $deptCode)->orWhere('department_code', $deptCode))->orderBy('created_at', 'desc')->take(10)->get();
        $employers = \App\Models\Employer::where(fn($q) => $q->where('created_by_dept_code', $deptCode)->orWhere('department_code', $deptCode))->orderBy('created_at', 'desc')->take(10)->get();
        $contractors = \App\Models\Contractor::where(fn($q) => $q->where('created_by_dept_code', $deptCode)->orWhere('department_code', $deptCode))->orderBy('created_at', 'desc')->take(10)->get();
        $schemes = DB::table('schemes')->where('created_by_dept_code', $deptCode)->orderBy('created_at', 'desc')->take(10)->get();
        $workers = DB::table('workers')->where('created_by_dept_code', $deptCode)->orderBy('created_at', 'desc')->take(10)->get();

        return view('admin.owner_dept_onboard', compact('ngos', 'employers', 'contractors', 'schemes', 'workers'));
    }

    public function createNgo(Request $request)
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
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

        $ngo = \App\Models\Ngo::create([
            'ngo_name' => $request->ngo_name,
            'registration_no' => $request->registration_no,
            'office_address' => $request->office_address,
            'email' => $request->email,
            'contact_person' => $request->contact_person,
            'mobile' => $request->mobile,
            'created_by' => Auth::id(),
            'created_by_dept_code' => Auth::user()->department_code,
        ]);

        $u = User::create([
            'name' => $request->ngo_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'ngo',
            'is_active' => true,
            'ngo_id' => $ngo->id,
            'created_by' => Auth::id(),
            'created_by_dept_code' => Auth::user()->department_code,
        ]);

        AuditLogger::log('CREATE', 'ngo', $ngo->id ?? $u->id, $request->ngo_name, ['email' => $request->email]);

        return redirect()->route('owner-dept.onboard')->with('success', 'NGO onboarded successfully.');
    }

    public function createEmployer(Request $request)
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
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

        $emp = \App\Models\Employer::create([
            'employer_name' => $request->employer_name,
            'registration_no' => $request->registration_no,
            'office_address' => $request->office_address,
            'email' => $request->email,
            'contact_person' => $request->contact_person,
            'mobile' => $request->mobile,
            'created_by' => Auth::id(),
            'created_by_dept_code' => Auth::user()->department_code,
        ]);

        $u = User::create([
            'name' => $request->employer_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'employer',
            'is_active' => true,
            'created_by' => Auth::id(),
            'created_by_dept_code' => Auth::user()->department_code,
        ]);

        AuditLogger::log('CREATE', 'employer', $emp->id ?? $u->id, $request->employer_name, ['email' => $request->email]);

        return redirect()->route('owner-dept.onboard')->with('success', 'Employer onboarded successfully.');
    }

    public function createContractor(Request $request)
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
        }

        $request->validate([
            'contractor_name' => 'required|string|max:255',
            'license_no' => 'required|string|max:255',
            'office_address' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'mobile' => 'required|string|max:20',
        ]);

        $cnt = \App\Models\Contractor::create([
            'contractor_name' => $request->contractor_name,
            'license_no' => $request->license_no,
            'office_address' => $request->office_address,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'created_by' => Auth::id(),
            'created_by_dept_code' => Auth::user()->department_code,
        ]);

        $u = User::create([
            'name' => $request->contractor_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'contractor',
            'is_active' => true,
            'created_by' => Auth::id(),
            'created_by_dept_code' => Auth::user()->department_code,
        ]);

        AuditLogger::log('CREATE', 'contractor', $cnt->id ?? $u->id, $request->contractor_name, ['email' => $request->email]);

        return redirect()->route('owner-dept.onboard')->with('success', 'Contractor onboarded successfully.');
    }

    public function createBeneficiary(Request $request)
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'job_profile' => 'required|string',
            'job_profile_other' => 'required_if:job_profile,Other|nullable|string',
            'ration_card_number' => 'required|string',
            'gender' => 'required|string',
            'relation' => 'nullable|string',
            'age' => 'nullable|integer',
            
            // Mandatory for mobile login and profile minimums
            'mobile' => 'required|string|max:20',
            'aadhaar_address' => 'required|string',
            
            // All other fields are optional/nullable
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:6',
            'father_name' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'marital_status' => 'nullable|string',
            'aadhaar_number' => 'nullable|string|max:16',
            'bank_account_number' => 'nullable|string',
            'bank_name_branch' => 'nullable|string',
            'bank_ifsc' => 'nullable|string',
            
            // Extra mobile app fields
            'category' => 'nullable|string',
            'district' => 'nullable|string',
            'state' => 'nullable|string',
            'qualification' => 'nullable|string',
            'school_institution' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'daily_wage' => 'nullable|numeric|min:0',
            'bocw_card_number' => 'nullable|string',
            'bocw_reg_date' => 'nullable|date',
            'bocw_valid_upto' => 'nullable|date',
            'signature' => 'nullable|image|max:2048',
        ]);

        $signatureUrl = null;
        if ($request->hasFile('signature')) {
            if (!file_exists(public_path('uploads/signatures'))) {
                mkdir(public_path('uploads/signatures'), 0777, true);
            }
            $file = $request->file('signature');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/signatures'), $filename);
            $signatureUrl = 'uploads/signatures/' . $filename;
        }

        // Job Profile 'Other' check
        $jobProfile = $request->job_profile;
        if ($jobProfile === 'Other') {
            $jobProfile = $request->job_profile_other;
        }

        $user = User::create([
            'mobile' => $request->mobile,
            'name' => $request->full_name,
            'address' => $request->aadhaar_address,
            'job_profile' => $jobProfile,
            'trade' => $jobProfile,
            'role' => 'beneficiary',
            'is_active' => true,
            'is_verified' => false,
            'created_by' => Auth::id(),
            'created_by_dept_code' => Auth::user()->department_code,
        ]);

        // Filter other family members (excluding primary beneficiary)
        $selectedName = $request->full_name;
        $allFamily = session('ration_card_family', []);
        $otherFamily = array_values(array_filter($allFamily, function($m) use ($selectedName) {
            return strtolower(trim($m['name'])) !== strtolower(trim($selectedName));
        }));

        $workerId = DB::table('workers')->insertGetId([
            'user_id' => $user->id,
            'full_name' => $request->full_name,
            'father_name' => $request->father_name,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'aadhaar_number' => $request->aadhaar_number,
            'aadhaar_address' => $request->aadhaar_address,
            'job_profile' => $jobProfile,
            'bank_account_number' => $request->bank_account_number,
            'bank_name_branch' => $request->bank_name_branch,
            'bank_ifsc' => $request->bank_ifsc,
            'ration_card_number' => preg_replace('/[\s\-\/]+/', '', $request->ration_card_number),
            'relation' => $request->relation,
            'age' => $request->age ? (int)$request->age : null,
            'family_members' => $otherFamily,
            'mobile' => $request->mobile,
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
            
            // Extra mobile app fields
            'category' => $request->category,
            'district' => $request->district,
            'state' => $request->state,
            'qualification' => $request->qualification,
            'school_institution' => $request->school_institution,
            'experience_years' => $request->filled('experience_years') ? (int) $request->experience_years : null,
            'daily_wage' => $request->filled('daily_wage') ? (float) $request->daily_wage : null,
            'bocw_card_number' => $request->bocw_card_number,
            'bocw_reg_date' => $request->bocw_reg_date,
            'bocw_valid_upto' => $request->bocw_valid_upto,
            'signature_url' => $signatureUrl,
        ]);

        \App\Models\Worker::syncFamilyMembers($workerId, $otherFamily);

        AuditLogger::log('CREATE', 'worker', $user->id, $request->full_name, ['email' => $request->email]);

        session()->forget(['ration_card_number', 'ration_card_family', 'ration_card_address']);

        return redirect()->route('owner-dept.beneficiaries')->with('success', 'Worker/Beneficiary onboarded successfully.');
    }

    public function viewLogs()
    {
        $this->guard();
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return response('No logs found at ' . $logPath);
        }
        $content = file_get_contents($logPath);
        // Get last 30,000 characters
        if (strlen($content) > 30000) {
            $content = substr($content, -30000);
        }
        return response('<html><head><title>Laravel Log Viewer</title>
        <style>body{background:#111;color:#eee;font-family:monospace;padding:20px;font-size:13px;line-height:1.5;}
        pre{white-space:pre-wrap;word-break:break-all;background:#222;padding:15px;border-radius:5px;border:1px solid #333;color:#ff4d4d;}</style></head>
        <body><h2>📝 Last 30KB of Laravel Log</h2><pre>' . htmlspecialchars($content) . '</pre></body></html>');
    }

    public function verifyRationCard(Request $request)
    {
        $this->guard();

        $request->validate([
            'ration_card_pdf' => 'required|file|mimes:pdf',
        ]);

        try {
            // ── Text Extraction per-page ─────────────────────────────────────────
            $parser    = new \Smalot\PdfParser\Parser();
            $pdf       = $parser->parseFile($request->file('ration_card_pdf')->getRealPath());
            $pages     = $pdf->getPages();
            $pageTexts = array_map(fn($p) => $p->getText(), $pages);
            $fullText  = implode("\n", $pageTexts);
            $page1Text = $pageTexts[0] ?? $fullText;

            // Helper: must contain ≥4 consecutive digits (rejects words like "Number", "Card")
            $isValidId = fn($v) => (bool) preg_match('/\d{4,}/', $v);

            // ── Card Number Extraction ────────────────────────────────────────────
            // Priority order: explicit "Ration Card" label patterns FIRST,
            // then generic fallbacks. This prevents picking up "Fare Price Shop No"
            // or other incidental numbers before the actual Ration Card Number.
            $extractedNumber = null;

            // P1: "Ration Card Number  695325478123" (label + whitespace + digits, NO colon needed)
            //     Matches the exact DDD/Silvassa format seen in the PDF image
            if (!$extractedNumber && preg_match(
                '/ration\s*card\s*number\s*[\r\n\s]+(\d{6,20})/i',
                $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }

            // P2: "Ration Card Number: 12345" (with colon)
            if (!$extractedNumber && preg_match(
                '/ration\s*card\s*number\s*[:\-]\s*([A-Z0-9][\w\-\/]{4,24})/i',
                $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }

            // P3: "Ration Card No: / Ration Card No." (no/num/#  abbreviations)
            if (!$extractedNumber && preg_match(
                '/ration\s*card\s*(?:no\.?|num\.?|#)\s*[:\-]?\s*[\r\n\s]*([A-Z0-9][\w\-\/]{4,24})/i',
                $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }

            // P4: "RC No:" / "R.C. No:" abbreviations
            if (!$extractedNumber && preg_match(
                '/\bR\.?\s*C\.?\s*(?:no\.?|number)?\s*[:\-]\s*([A-Z0-9][\w\-\/]{4,24})/i',
                $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }

            // P5: "Ration Card" label on one line → number on the very next line
            if (!$extractedNumber && preg_match(
                '/ration\s*card[^\n]{0,50}\n\s*([A-Z0-9][\w\-\/]{4,24})/i',
                $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }

            // P6: "Card No." / "Kard No." (state-specific variants)
            if (!$extractedNumber && preg_match(
                '/(?:card|kard)\s*(?:no\.?|number)\s*[:\-]?\s*[\r\n\s]*([A-Z0-9][\w\-\/]{4,24})/i',
                $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }

            // P7: Standalone 10–14 digit number on page 1 ONLY AS FALLBACK
            //     (skip numbers that appear right after "shop no", "price no", "mobile", "phone")
            if (!$extractedNumber) {
                // Remove distracting context before searching for standalone numbers
                $cleanPage1 = preg_replace(
                    '/(fare\s*price\s*shop|shop\s*no|mobile\s*(?:number|no)?|phone|aadhar)[^\n]{0,60}/i',
                    '', $page1Text
                );
                if (preg_match('/\b(\d{10,14})\b/', $cleanPage1, $m)) {
                    $extractedNumber = $m[1];
                }
            }

            // P8: Alphanumeric state-format IDs like "GJ/01/2345678"
            if (!$extractedNumber && preg_match(
                '/\b([A-Z]{2,4}[\s\/\-]?[A-Z0-9]{2,6}[\s\/\-]?\d{4,10})\b/i',
                $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }

            // P9: Any 8–9 digit number (last resort)
            if (!$extractedNumber && preg_match('/\b(\d{8,9})\b/', $fullText, $m)) {
                $extractedNumber = $m[1];
            }

            if (!$extractedNumber) {
                $snippet = substr(preg_replace('/\s+/', ' ', $fullText), 0, 400);
                return back()->withErrors([
                    'ration_card_pdf' => 'Could not extract Ration Card Number. PDF text preview: "' . $snippet . '"'
                ]);
            }

            $normalizedExtracted = preg_replace('/[\s\-\/]+/', '', $extractedNumber);

            if (DB::table('workers')->where('ration_card_number', $normalizedExtracted)->exists()) {
                return back()->withErrors([
                    'ration_card_pdf' => 'This Ration Card (No: ' . $extractedNumber . ') is already registered.'
                ]);
            }

            // ── Family Member Parsing ─────────────────────────────────────────────
            // We parse line-by-line from the consolidated text of all pages.
            // Using a highly robust regex pattern that perfectly matches:
            //   1. Leading serial numbers directly attached to names (e.g. "1Parate")
            //   2. Names directly attached to ages (e.g. "Deshpande53")
            //   3. Optional slash '/' between age and gender
            //   4. Multi-word relations (e.g. "GRAND DAUGHTER", "SON IN LAW")
            //   5. Trailing Aadhar numbers or signatures

            $family = [];

            $relKwPat = 'GRAND\s+DAUGHTER|GREAT\s+GRAND(?:\s+SON|\s+DAUGHTER)?|SON\s+IN\s+LAW|'
                      . 'DAUGHTER\s+IN\s+LAW|GRANDSON|GRANDDAUGHTER|'
                      . 'HEAD|SELF|WIFE|HUSBAND|SON|DAUGHTER|FATHER|MOTHER|'
                      . 'BROTHER|SISTER|SPOUSE|GUARDIAN|OTHER|MUKHIYA|BETI|PUTRA';

            $relMap = [
                'self'               => 'Head',
                'head'               => 'Head',
                'mukhiya'            => 'Head',
                'wife'               => 'Spouse',
                'husband'            => 'Spouse',
                'spouse'             => 'Spouse',
                'son'                => 'Son',
                'putra'              => 'Son',
                'grandson'           => 'Grandson',
                'daughter'           => 'Daughter',
                'beti'               => 'Daughter',
                'granddaughter'      => 'Granddaughter',
                'grand daughter'     => 'Grand Daughter',
                'great grand son'    => 'Great Grand Son',
                'great grand daughter' => 'Great Grand Daughter',
                'father'             => 'Father',
                'mother'             => 'Mother',
                'brother'            => 'Brother',
                'sister'             => 'Sister',
                'son in law'         => 'Son-in-law',
                'daughter in law'    => 'Daughter-in-law',
                'guardian'           => 'Guardian',
            ];

            // Header words to skip when found as first word of a "name"
            $headerFirstWords = ['sr', 'sl', 'name', 'member', 'age', 'gender',
                                  'relation', 'aadhar', 'aadhaar', 'scheme', 'category',
                                  'signature', 'address', 'mobile', 'head', 'fare', 'ration'];

            $allLines = explode("\n", $fullText);

            $primaryRegex = '/^\s*(?:\d+)?\s*([A-Za-z][A-Za-z\s\.]{1,45}?)\s*(\d{1,3})\s*(?:\/)?\s*(M|F)\b\s+(' . $relKwPat . ')(\s+.*)?$/i';
            $fallbackRegex = '/^\s*(?:\d+)?\s*([A-Za-z][A-Za-z\s\.]{1,45}?)\s*(\d{1,3})\s*(?:\/)?\s*(M|F)\b(?:\s+(.*?))?$/i';

            foreach ($allLines as $rawLine) {
                $line = trim($rawLine);
                if (strlen($line) < 6) continue;

                $m = [];
                $matched = false;

                // Try Primary Regex (matches known relation patterns)
                if (preg_match($primaryRegex, $line, $m)) {
                    $name = trim($m[1]);
                    $age = (int)$m[2];
                    $genderChr = strtolower($m[3]);
                    $relRaw = strtolower(trim(preg_replace('/\s+/', ' ', $m[4])));
                    $relation = $relMap[$relRaw] ?? ucwords($relRaw);
                    $matched = true;
                } 
                // Try Fallback Regex (matches name, age, gender, gets relation from remaining text)
                elseif (preg_match($fallbackRegex, $line, $m)) {
                    $name = trim($m[1]);
                    $age = (int)$m[2];
                    $genderChr = strtolower($m[3]);
                    $after = isset($m[4]) ? trim($m[4]) : '';
                    $relRaw = strtolower(strtok($after, " \t\r\n"));
                    $relation = $relMap[$relRaw] ?? (empty($relRaw) ? 'Member' : ucwords($relRaw));
                    $matched = true;
                }

                if (!$matched) {
                    continue;
                }

                // Skip invalid age values
                if ($age === 0 || $age > 110) continue;

                // Validate name structure
                if (strlen($name) < 3) continue;
                if (!preg_match('/^[A-Za-z][A-Za-z\s\.]{1,}$/', $name)) continue;

                // Skip if name starts with header keywords
                $firstWord = strtolower(strtok($name, ' '));
                if (in_array($firstWord, $headerFirstWords)) continue;

                $gender = $genderChr === 'f' ? 'female' : 'male';

                // Deduplicate by name
                $dup = false;
                foreach ($family as $f) {
                    if (strtolower($f['name']) === strtolower($name)) {
                        $dup = true;
                        break;
                    }
                }
                if (!$dup) {
                    $family[] = compact('name', 'age', 'gender', 'relation');
                }
            }

            // Blank placeholder if still no members found (allows form registration to proceed)
            if (empty($family)) {
                $family[] = ['name' => '', 'age' => 0, 'gender' => 'male', 'relation' => 'Head'];
            }

            // Extract Address from PDF text
            $extractedAddress = null;
            $lines = explode("\n", $fullText);
            foreach ($lines as $i => $l) {
                if (preg_match('/^\s*(?:address|residential\s*address|સરનામું|पता)\s*[:\-]?\s*(.+)$/ui', $l, $m)) {
                    $extractedAddress = trim($m[1]);
                    // If next line exists and doesn't contain standard keywords/labels, append it too!
                    $nextLine = isset($lines[$i+1]) ? trim($lines[$i+1]) : '';
                    if (!empty($nextLine) && !preg_match('/(name|card|no|relation|gender|age|fps|shop|uid|aadhaar|mobile|phone)\s*[:\-]/i', $nextLine)) {
                        $extractedAddress .= ' ' . $nextLine;
                    }
                    break;
                }
            }

            session([
                'ration_card_number' => $normalizedExtracted,
                'ration_card_family' => $family,
                'ration_card_address' => $extractedAddress
            ]);

            $cnt = count(array_filter($family, fn($f) => !empty($f['name'])));
            $msg = $cnt > 0
                ? "Ration Card verified (No: {$extractedNumber}). {$cnt} family member(s) found — select one to register."
                : "Ration Card verified (No: {$extractedNumber}). Family members could not be detected — please fill in manually.";

            return back()->with('success', $msg);

        } catch (\Exception $e) {
            return back()->withErrors(['ration_card_pdf' => 'Error reading PDF: ' . $e->getMessage()]);
        }
    }


    public function schemes(Request $request)
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
        }

        $search = $request->query('search', '');
        $deptCode = Auth::user()->department_code;

        $query = DB::table('schemes')
            ->where(function($q) use ($deptCode) {
                $q->where('created_by_dept_code', (string)$deptCode)
                  ->orWhere('created_by_dept_code', (int)$deptCode);
            });

        if (!empty($search)) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $schemes = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.govt_dept_schemes', compact('schemes', 'search'));
    }

    public function createScheme(Request $request)
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'url' => 'required|string',
        ]);

        $schemeId = DB::table('schemes')->insertGetId([
            'title' => $request->title,
            'description' => $request->description,
            'url' => $request->url,
            'is_active' => true,
            'created_by' => Auth::id(),
            'created_by_dept_code' => Auth::user()->department_code,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        AuditLogger::log('CREATE', 'scheme', $schemeId, $request->title, [
            'url' => $request->url,
            'created_by_dept_code' => Auth::user()->department_code
        ]);

        return redirect()->route('govt-dept-admin.schemes')->with('success', 'Scheme created successfully.');
    }

    public function deleteScheme($id)
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
        }

        $scheme = DB::table('schemes')->where('_id', $id)->first();
        if (!$scheme) {
            return back()->with('error', 'Scheme not found.');
        }
        $scheme = (array)$scheme;

        // Security check: dept boundary
        $deptCode = Auth::user()->department_code;
        if (($scheme['created_by_dept_code'] ?? null) != $deptCode) {
            abort(403, 'Unauthorized access to this scheme.');
        }

        // Check if there are active applications for this scheme
        $hasApplications = DB::table('scheme_applications')->where('scheme_id', $id)->exists();
        if ($hasApplications) {
            return back()->with('error', 'Scheme has active applications and cannot be deleted.');
        }

        $schemeTitle = $scheme['title'] ?? 'Scheme';

        DB::table('schemes')->where('_id', $id)->delete();

        AuditLogger::log('DELETE', 'scheme', $id, $schemeTitle);

        return redirect()->route('govt-dept-admin.schemes')->with('success', 'Scheme deleted successfully.');
    }

    public function reviewRegistration($id)
    {
        $this->guard();

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return redirect()->route('govt-dept-admin.index')->with('error', 'Worker record not found.');
        }

        $worker = (array) $worker;

        // Security check: dept boundary
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = Auth::user()->role === 'owner-dept-admin';
        if (!$isOwnerDept && $deptCode && ($worker['department_code'] ?? null) != $deptCode) {
            abort(403, 'Unauthorized crossover to another department.');
        }

        return view('admin.owner_dept_review_registration', compact('worker'));
    }

    public function approveRegistration(Request $request, $id)
    {
        $this->guard();

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return redirect()->route('govt-dept-admin.index')->with('error', 'Worker record not found.');
        }

        $worker = (array) $worker;

        // Security check: dept boundary
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = Auth::user()->role === 'owner-dept-admin';
        if (!$isOwnerDept && $deptCode && ($worker['department_code'] ?? null) != $deptCode) {
            abort(403, 'Unauthorized crossover to another department.');
        }

        // Validate editable fields
        $request->validate([
            'full_name' => 'required|string|max:255',
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

        $regNo = 'REG-' . date('Y') . '-' . rand(100000, 999999);

        // Update worker details in database
        DB::table('workers')->where('_id', $id)->update([
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
            'status' => 'approved',
            'is_verified' => true,
            'registration_no' => $regNo,
            'approved_by' => Auth::id(),
            'approved_by_dept_code' => Auth::user()->department_code,
            'approved_at' => now()->toDateTimeString(),
            'approver_remarks' => $request->input('remarks', 'Profile approved and registration number issued.'),
        ]);

        // Sync name & status to corresponding User account
        if (!empty($worker['user_id'])) {
            User::where('id', $worker['user_id'])->update([
                'name' => $request->full_name,
                'is_active' => true
            ]);
        }

        AuditLogger::log('APPROVE', 'worker', $worker['user_id'] ?? $id, $request->full_name, [
            'remarks' => $request->input('remarks'),
            'registration_no' => $regNo,
            'approved_by_dept_code' => Auth::user()->department_code
        ]);

        return redirect()->route('govt-dept-admin.index', ['status' => 'pending'])->with('success', 'Worker profile approved successfully. Issued Registration No: ' . $regNo);
    }

    public function rejectRegistration(Request $request, $id)
    {
        $this->guard();

        $request->validate([
            'remarks' => 'required|string|min:3',
        ]);

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return redirect()->route('govt-dept-admin.index')->with('error', 'Worker record not found.');
        }

        $worker = (array) $worker;

        // Security check: dept boundary
        $deptCode = Auth::user()->department_code;
        $isOwnerDept = Auth::user()->role === 'owner-dept-admin';
        if (!$isOwnerDept && $deptCode && ($worker['department_code'] ?? null) != $deptCode) {
            abort(403, 'Unauthorized crossover to another department.');
        }

        DB::table('workers')->where('_id', $id)->update([
            'status' => 'rejected',
            'is_verified' => false,
            'approved_by' => Auth::id(),
            'approved_by_dept_code' => Auth::user()->department_code,
            'approved_at' => now()->toDateTimeString(),
            'approver_remarks' => $request->remarks,
        ]);

        AuditLogger::log('REJECT', 'worker', $worker['user_id'] ?? $id, $worker['full_name'] ?? 'Worker', [
            'remarks' => $request->remarks,
            'approved_by_dept_code' => Auth::user()->department_code
        ]);

        return redirect()->route('govt-dept-admin.index', ['status' => 'pending'])->with('success', 'Worker profile rejected.');
    }

    public function updateScheme(Request $request, $id)
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
        }

        $scheme = DB::table('schemes')->where('_id', $id)->first();
        if (!$scheme) {
            return back()->with('error', 'Scheme not found.');
        }
        $scheme = (array)$scheme;

        // Security check: dept boundary
        $deptCode = Auth::user()->department_code;
        if (($scheme['created_by_dept_code'] ?? null) != $deptCode) {
            abort(403, 'Unauthorized access to this scheme.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'url' => 'required|string',
            'is_active' => 'sometimes|in:0,1',
        ]);

        $is_active = $request->input('is_active') === '1';

        DB::table('schemes')->where('_id', $id)->update([
            'title' => $request->title,
            'description' => $request->description,
            'url' => $request->url,
            'is_active' => $is_active,
            'updated_at' => now()->toDateTimeString(),
        ]);

        AuditLogger::log('UPDATE', 'scheme', $id, $request->title, [
            'title' => $request->title
        ]);

        return back()->with('success', 'Scheme details updated successfully.');
    }

    public function exportSchemesCsv()
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="schemes_export_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Scheme ID', 'Scheme Title', 'Direct URL', 'Description', 'Created At']);

            $schemes = DB::table('schemes')->orderBy('created_at', 'desc')->get();
            foreach ($schemes as $s) {
                $s = (array)$s;
                fputcsv($file, [
                    (string)($s['id'] ?? $s['_id'] ?? ''),
                    $s['title'] ?? $s['name'] ?? '',
                    $s['url'] ?? $s['apply_url'] ?? '',
                    $s['description'] ?? '',
                    $s['created_at'] ?? ''
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function beneficiaries(Request $request)
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
        }

        if ($request->has('clear_ration')) {
            session()->forget(['ration_card_number', 'ration_card_family', 'ration_card_address']);
            return redirect()->route('owner-dept.beneficiaries');
        }

        $isOwnerDept = Auth::user()->role === 'owner-dept-admin';
        $deptCode = Auth::user()->department_code;
        $search = $request->query('search', '');
        
        $query = DB::table('workers')
            ->when(!$isOwnerDept && $deptCode, fn($q) => $q->where(fn($sq) => $sq->whereIn('department_code', [(string)$deptCode, (int)$deptCode])->orWhereIn('created_by_dept_code', [(string)$deptCode, (int)$deptCode])));

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                  ->orWhere('mobile', 'like', '%' . $search . '%')
                  ->orWhere('job_profile', 'like', '%' . $search . '%');
            });
        }

        $workers = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        foreach ($workers->items() as $w) {
            $wId = $w->_id ?? $w->id ?? null;
            $w->family_members = \App\Models\Worker::getFamilyMembers($wId, $w->family_members ?? []);
        }
        $activeTrades = \App\Models\Trade::whereIn('is_active', [true, 1])->orderBy('name', 'asc')->get();

        return view('admin.owner_dept_beneficiaries', compact('workers', 'search', 'activeTrades'));
    }

    public function updateBeneficiary(Request $request, $id)
    {
        if (!in_array(Auth::user()->role, ['owner-dept-admin', 'govt-dept-admin'])) {
            abort(403, 'Forbidden');
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'mobile' => 'required|string|max:15',
            'job_profile' => 'required|string|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'category' => 'nullable|string|max:255',
            'marital_status' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'school_institution' => 'nullable|string|max:255',
            'experience_years' => 'nullable|numeric|min:0',
            'daily_wage' => 'nullable|numeric|min:0',
            'aadhaar_number' => 'nullable|string|max:12',
            'ration_card_number' => 'nullable|string|max:255',
            'bocw_card_number' => 'nullable|string|max:255',
            'aadhaar_address' => 'nullable|string',
            'bank_account_number' => 'nullable|string|max:255',
            'bank_name_branch' => 'nullable|string|max:255',
            'bank_ifsc' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
        ]);

        $updateData = [
            'full_name' => $request->full_name,
            'father_name' => $request->father_name,
            'mobile' => $request->mobile,
            'job_profile' => $request->job_profile,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'category' => $request->category,
            'marital_status' => $request->marital_status,
            'qualification' => $request->qualification,
            'school_institution' => $request->school_institution,
            'experience_years' => $request->experience_years !== null ? (int)$request->experience_years : null,
            'daily_wage' => $request->daily_wage !== null ? (float)$request->daily_wage : null,
            'aadhaar_number' => $request->aadhaar_number,
            'ration_card_number' => $request->ration_card_number,
            'bocw_card_number' => $request->bocw_card_number,
            'aadhaar_address' => $request->aadhaar_address,
            'bank_account_number' => $request->bank_account_number,
            'bank_name_branch' => $request->bank_name_branch,
            'bank_ifsc' => $request->bank_ifsc,
            'district' => $request->district,
            'state' => $request->state,
            'updated_at' => now()->toDateTimeString(),
        ];

        DB::table('workers')->where('_id', $id)->update($updateData);

        $worker = DB::table('workers')->where('_id', $id)->first();
        if ($worker && isset($worker->user_id)) {
            DB::table('users')->where('id', $worker->user_id)->update([
                'name' => $request->full_name,
                'mobile' => $request->mobile,
                'address' => $request->aadhaar_address,
                'job_profile' => $request->job_profile,
                'trade' => $request->job_profile,
            ]);
        }

        AuditLogger::log('UPDATE', 'worker', $id, $request->full_name, [
            'full_name' => $request->full_name
        ]);

        return back()->with('success', 'Beneficiary details updated successfully.');
    }
}
