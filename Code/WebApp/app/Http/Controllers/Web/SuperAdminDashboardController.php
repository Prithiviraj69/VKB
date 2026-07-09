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

class SuperAdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403, 'Forbidden');
        }

        // Load all Government Department Admin accounts
        $ownerDeptUser = User::where('role', 'owner-dept-admin')
            ->with('department')
            ->first();

        $hasOwnerDept = ($ownerDeptUser !== null);

        $deptUsers = User::where('role', 'govt-dept-admin')
            ->with('department')
            ->orderBy('created_at', 'desc')
            ->get();

        $departments = Department::orderBy('created_at', 'desc')->get();

        $ngos = \App\Models\Ngo::orderBy('created_at', 'desc')->get();
        $employers = \App\Models\Employer::orderBy('created_at', 'desc')->get();
        $contractors = \App\Models\Contractor::orderBy('created_at', 'desc')->get();

        return view('admin.super_admin', compact(
            'deptUsers', 'ownerDeptUser', 'hasOwnerDept', 'departments', 'ngos', 'employers', 'contractors'
        ));
    }

    public function createNgo(Request $request)
    {
        if (Auth::user()->role !== 'super-admin') {
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

        $ngo = \App\Models\Ngo::create([
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
            'ngo_id' => $ngo->id,
            'created_by' => Auth::id(),
        ]);

        AuditLogger::log('CREATE', 'ngo', $ngo->id ?? $u->id, $request->ngo_name, ['email' => $request->email]);

        return redirect()->route('super-admin.index')->with('success', 'NGO onboarded successfully.');
    }

    public function createEmployer(Request $request)
    {
        if (Auth::user()->role !== 'super-admin') {
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

        $emp = \App\Models\Employer::create([
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

        AuditLogger::log('CREATE', 'employer', $emp->id ?? $u->id, $request->employer_name, ['email' => $request->email]);

        return redirect()->route('super-admin.index')->with('success', 'Employer onboarded successfully.');
    }

    public function createContractor(Request $request)
    {
        if (Auth::user()->role !== 'super-admin') {
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

        $cnt = \App\Models\Contractor::create([
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

        AuditLogger::log('CREATE', 'contractor', $cnt->id ?? $u->id, $request->contractor_name, ['email' => $request->email]);

        return redirect()->route('super-admin.index')->with('success', 'Contractor onboarded successfully.');
    }



    public function createDepartment(Request $request)
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403);
        }

        $request->merge([
            'department_code' => $request->has('department_code') ? (int) $request->department_code : null,
        ]);

        $request->validate([
            // Department info
            'department_code'        => 'required|integer|unique:departments,department_code',
            'department_name'        => 'required|string|max:255',
            'parent_department_name' => 'nullable|string|max:255',
            'office_address'         => 'required|string',
            'department_email'       => 'required|email|unique:departments,email',
            'logo'                   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            // Department Admin credentials
            'admin_name'        => 'required|string|max:255',
            'admin_designation' => 'required|string|max:255',
            'admin_mobile'      => 'required|string|max:20',
            'admin_email'       => 'required|email|unique:users,email',
            'admin_password'    => 'required|string|min:6',
        ], [
            'department_code.unique' => 'The Department Code has already been registered.',
            'department_email.unique' => 'The Department Email has already been registered.',
            'admin_email.unique' => 'The Admin Email address has already been registered.',
        ]);

        $deptCode = (int) $request->department_code;

        $logoPath = null;
        if ($request->hasFile('logo')) {
            if (!file_exists(public_path('uploads/logos'))) {
                mkdir(public_path('uploads/logos'), 0777, true);
            }
            $file = $request->file('logo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/logos'), $filename);
            $logoPath = 'uploads/logos/' . $filename;
        }

        // 1. Create the Department record
        $dept = Department::create([
            'department_code'        => $deptCode,
            'department_name'        => $request->department_name,
            'parent_department_name' => $request->parent_department_name,
            'office_address'         => $request->office_address,
            'email'                  => $request->department_email,
            'logo_path'              => $logoPath,
        ]);

        $u = User::create([
            'name'            => $request->admin_name,
            'email'           => $request->admin_email,
            'password'        => Hash::make($request->admin_password),
            'mobile'          => $request->admin_mobile,
            'designation'     => $request->admin_designation,
            'role'            => 'govt-dept-admin',
            'is_active'       => true,
            'department_code' => $deptCode,
            'created_by'      => Auth::id(),
        ]);

        AuditLogger::log('CREATE', 'department', $dept->id ?? $u->id, $request->department_name, ['admin_email' => $request->admin_email]);

        return redirect()->route('super-admin.index')
            ->with('success', 'Government Department onboarded. Admin can now log in and create Verifiers & Approvers.');
    }

    public function createOwnerDepartment(Request $request)
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403);
        }

        $request->merge([
            'department_code' => $request->has('department_code') ? (int) $request->department_code : null,
        ]);

        // DB Constraint check: Only 1 owner-dept-admin in whole system
        $existing = User::where('role', 'owner-dept-admin')->first();
        if ($existing) {
            return back()->withInput()->withErrors(['error' => 'Owner Department already exists']);
        }

        $request->validate([
            // Department info
            'department_code'        => 'required|integer|unique:departments,department_code',
            'department_name'        => 'required|string|max:255',
            'parent_department_name' => 'nullable|string|max:255',
            'office_address'         => 'required|string',
            'department_email'       => 'required|email|unique:departments,email',
            'logo'                   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            // Department Admin credentials
            'admin_name'        => 'required|string|max:255',
            'admin_designation' => 'required|string|max:255',
            'admin_mobile'      => 'required|string|max:20',
            'admin_email'       => 'required|email|unique:users,email',
            'admin_password'    => 'required|string|min:6',
        ], [
            'department_code.unique' => 'The Department Code has already been registered.',
            'department_email.unique' => 'The Department Email has already been registered.',
            'admin_email.unique' => 'The Admin Email address has already been registered.',
        ]);

        $deptCode = (int) $request->department_code;

        $logoPath = null;
        if ($request->hasFile('logo')) {
            if (!file_exists(public_path('uploads/logos'))) {
                mkdir(public_path('uploads/logos'), 0777, true);
            }
            $file = $request->file('logo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/logos'), $filename);
            $logoPath = 'uploads/logos/' . $filename;
        }

        // 1. Create the Department record
        $dept = Department::create([
            'department_code'        => $deptCode,
            'department_name'        => $request->department_name,
            'parent_department_name' => $request->parent_department_name,
            'office_address'         => $request->office_address,
            'email'                  => $request->department_email,
            'logo_path'              => $logoPath,
        ]);

        $u = User::create([
            'name'            => $request->admin_name,
            'email'           => $request->admin_email,
            'password'        => Hash::make($request->admin_password),
            'mobile'          => $request->admin_mobile,
            'designation'     => $request->admin_designation,
            'role'            => 'owner-dept-admin',
            'is_active'       => true,
            'department_code' => $deptCode,
            'created_by'      => Auth::id(),
        ]);

        AuditLogger::log('CREATE', 'owner-department', $dept->id ?? $u->id, $request->department_name, ['admin_email' => $request->admin_email]);

        return redirect()->route('super-admin.index')
            ->with('success', 'Owner Department onboarded successfully.');
    }

    public function toggleStatus($id)
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403);
        }

        $user = User::findOrFail($id);
        if ($user->role === 'super-admin') {
            return back()->with('error', 'Cannot disable Super Admin account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        AuditLogger::log('TOGGLE_STATUS', 'user', $user->id, $user->name, ['new_status' => $user->is_active ? 'active' : 'inactive', 'role' => $user->role]);

        return back()->with('success', 'User status updated successfully.');
    }

    public function deleteUser($id)
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403);
        }

        $user = User::find($id);

        if ($user) {
            if ($user->role === 'super-admin') {
                return back()->with('error', 'Cannot delete Super Admin account.');
            }

            // If deleting a Govt Dept Admin or Owner Dept Admin, cascade delete all their verifiers/approvers first
            if (in_array($user->role, ['govt-dept-admin', 'owner-dept-admin'])) {
                if ($user->department_code) {
                    Department::where('department_code', (int) $user->department_code)->delete();
                }

                $subordinates = User::whereIn('role', ['dept-verifier', 'dept-approver'])
                    ->where('created_by', $user->id)
                    ->get();

                foreach ($subordinates as $sub) {
                    $sub->delete();
                }

                $user->delete();
                return redirect()->route('super-admin.index')
                    ->with('success', 'Department Admin, Department profile, and all their Verifiers/Approvers deleted successfully.');
            }

            // Transaction guard for verifier/approver
            if (in_array($user->role, ['dept-verifier', 'dept-approver'])) {
                $hasWorkerTransactions = DB::table('workers')
                    ->where('verified_by', $user->id)
                    ->orWhere('approved_by', $user->id)
                    ->exists();

                $hasSchemeTransactions = DB::table('scheme_applications')
                    ->where('verified_by', $user->id)
                    ->orWhere('approved_by', $user->id)
                    ->exists();

                $hasGrievanceTransactions = DB::table('grievances')
                    ->where('resolved_by', $user->id)
                    ->orWhere('handled_by', $user->id)
                    ->exists();

                if ($hasWorkerTransactions || $hasSchemeTransactions || $hasGrievanceTransactions) {
                    return back()->with('error', 'User transaction are recorded, so user cannot be deleted. Please deactive the user instead of deletion.');
                }
            }

            // Check if NGO has transactions
            if ($user->role === 'ngo') {
                $hasEmployers = \App\Models\Employer::where('created_by', $user->id)->exists();
                $hasContractors = \App\Models\Contractor::where('created_by', $user->id)->exists();
                $hasWorkers = DB::table('workers')->where('created_by', $user->id)->exists();
                if ($hasEmployers || $hasContractors || $hasWorkers) {
                    return back()->with('error', 'User transaction are recorded, so user cannot be deleted. Please deactive the user instead of deletion.');
                }
                \App\Models\Ngo::where('email', $user->email)->delete();
            }

            // Check if Employer has transactions
            if ($user->role === 'employer') {
                $hasContractors = \App\Models\Contractor::where('created_by', $user->id)->exists();
                $hasWorkers = DB::table('workers')->where('created_by', $user->id)->exists();
                if ($hasContractors || $hasWorkers) {
                    return back()->with('error', 'User transaction are recorded, so user cannot be deleted. Please deactive the user instead of deletion.');
                }
                \App\Models\Employer::where('email', $user->email)->delete();
            }

            // Check if Contractor has transactions
            if ($user->role === 'contractor') {
                $hasWorkers = DB::table('workers')->where('created_by', $user->id)->exists();
                if ($hasWorkers) {
                    return back()->with('error', 'User transaction are recorded, so user cannot be deleted. Please deactive the user instead of deletion.');
                }
                \App\Models\Contractor::where('email', $user->email)->delete();
            }

            // Relational deletion for workers (if user is worker)
            if ($user->role === 'worker') {
                $worker = DB::table('workers')->where('user_id', $user->id)->first();
                if ($worker) {
                    $worker = (array) $worker;
                    $workerId = $worker['id'] ?? $worker['_id'] ?? null;
                    if ($workerId) {
                        DB::table('worker_family')->where('worker_id', $workerId)->delete();
                        DB::table('worker_documents')->where('worker_id', $workerId)->delete();
                        DB::table('worker_skills')->where('worker_id', $workerId)->delete();
                        DB::table('scheme_applications')->where('worker_id', $workerId)->delete();
                        DB::table('grievances')->where('worker_id', $workerId)->delete();
                    }
                    DB::table('workers')->where('user_id', $user->id)->delete();
                }
            }

            $uName = $user->name;
            $uRole = $user->role;
            $user->delete();
            AuditLogger::log('DELETE', 'user', $id, $uName, ['role' => $uRole]);
            return back()->with('success', 'User deleted successfully.');
        }

        // If User not found, check if it's an orphaned worker profile using user_id or id
        $worker = DB::table('workers')->where('user_id', $id)->orWhere('id', $id)->orWhere('_id', $id)->first();
        if ($worker) {
            $worker = (array) $worker;
            $workerId = $worker['id'] ?? $worker['_id'] ?? null;
            $userId = $worker['user_id'] ?? null;
            $wName = $worker['full_name'] ?? 'Orphaned Worker';

            if ($workerId) {
                DB::table('worker_family')->where('worker_id', $workerId)->delete();
                DB::table('worker_documents')->where('worker_id', $workerId)->delete();
                DB::table('worker_skills')->where('worker_id', $workerId)->delete();
                DB::table('scheme_applications')->where('worker_id', $workerId)->delete();
                DB::table('grievances')->where('worker_id', $workerId)->delete();
            }

            DB::table('workers')->where('id', $workerId)->orWhere('_id', $workerId)->delete();

            if ($userId) {
                User::where('id', $userId)->delete();
            }

            AuditLogger::log('DELETE', 'worker', $id, $wName, ['orphaned' => true]);

            return back()->with('success', 'Orphaned worker profile and associated data deleted successfully.');
        }

        return back()->with('error', 'User or worker profile not found.');
    }



    public function onboardHub()
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403, 'Forbidden');
        }

        $ownerDeptUser = User::where('role', 'owner-dept-admin')->first();
        $hasOwnerDept = ($ownerDeptUser !== null);

        return view('admin.super_admin_onboard', compact('hasOwnerDept'));
    }

    public function changePassword(Request $request, $id)
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403, 'Forbidden');
        }

        $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'password' => Hash::make($request->new_password),
            'needs_password_change' => true,
        ]);

        AuditLogger::log('CHANGE_PASSWORD', 'user', $user->id, $user->name, ['email' => $user->email, 'role' => $user->role]);

        return back()->with('success', "Password for user {$user->name} ({$user->email}) updated successfully. They will be forced to change it on their next login.");
    }

    public function updateDepartment(Request $request, $id)
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403, 'Forbidden');
        }

        $request->validate([
            'department_name' => 'required|string|max:255',
            'office_address'  => 'required|string',
            'email'           => 'required|email',
            'logo'            => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $dept = Department::findOrFail($id);
        $oldName = $dept->department_name;

        // Manual check for email uniqueness
        $emailExists = Department::where('email', $request->email)->where('_id', '!=', $id)->exists();
        if ($emailExists) {
            return back()->withErrors(['email' => 'The Department Email has already been registered.']);
        }

        $logoPath = $dept->logo_path;
        if ($request->hasFile('logo')) {
            // Delete old logo file if exists
            if ($logoPath && file_exists(public_path($logoPath))) {
                @unlink(public_path($logoPath));
            }
            if (!file_exists(public_path('uploads/logos'))) {
                mkdir(public_path('uploads/logos'), 0777, true);
            }
            $file = $request->file('logo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/logos'), $filename);
            $logoPath = 'uploads/logos/' . $filename;
        }

        $dept->update([
            'department_name'        => $request->department_name,
            'parent_department_name' => $request->parent_department_name,
            'office_address'         => $request->office_address,
            'email'                  => $request->email,
            'logo_path'              => $logoPath,
        ]);

        AuditLogger::log('UPDATE', 'department', $id, $request->department_name, [
            'old_name' => $oldName,
            'email'    => $request->email
        ]);

        return redirect()->route('super-admin.index')->with('success', 'Department details updated successfully.');
    }

    public function tradesIndex(Request $request)
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403, 'Forbidden');
        }

        $search = $request->query('search', '');
        $query = \App\Models\Trade::query();

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orWhere('category', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
        }

        $trades = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();

        return view('admin.super_admin_trades', compact('trades', 'search'));
    }

    public function createTrade(Request $request)
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403, 'Forbidden');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $exists = \App\Models\Trade::where('name', $request->name)->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'This trade name is already configured.'])->withInput();
        }

        $trade = \App\Models\Trade::create([
            'name' => $request->name,
            'category' => $request->category,
            'description' => $request->description,
            'is_active' => true,
        ]);

        AuditLogger::log('CREATE', 'trade', $trade->id, $request->name, [
            'category' => $request->category,
            'description' => $request->description
        ]);

        return redirect()->route('super-admin.trades')->with('success', 'Trade registered successfully.');
    }

    public function updateTrade(Request $request, $id)
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403, 'Forbidden');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|in:0,1',
        ]);

        $exists = \App\Models\Trade::where('name', $request->name)
            ->where(fn($q) => $q->where('_id', '!=', $id)->where('id', '!=', $id)->where('id', '!=', (int)$id))
            ->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'Another trade with this name already exists.'])->withInput();
        }

        $trade = \App\Models\Trade::where('_id', $id)
            ->orWhere('id', $id)
            ->orWhere('id', (int)$id)
            ->orWhere('id', (string)$id)
            ->firstOrFail();

        $oldName = $trade->name;

        $trade->update([
            'name' => $request->name,
            'category' => $request->category,
            'description' => $request->description,
            'is_active' => $request->is_active == '1',
        ]);

        AuditLogger::log('UPDATE', 'trade', $id, $request->name, [
            'old_name' => $oldName,
            'category' => $request->category,
            'is_active' => $request->is_active == '1'
        ]);

        return redirect()->route('super-admin.trades')->with('success', 'Trade details updated successfully.');
    }

    public function deleteTrade($id)
    {
        if (Auth::user()->role !== 'super-admin') {
            abort(403, 'Forbidden');
        }

        $trade = \App\Models\Trade::where('_id', $id)
            ->orWhere('id', $id)
            ->orWhere('id', (int)$id)
            ->orWhere('id', (string)$id)
            ->firstOrFail();

        $tradeName = $trade->name;

        // Check if any worker has this trade/job_profile configured
        $hasWorkers = DB::table('workers')->where('job_profile', $tradeName)->exists();
        if ($hasWorkers) {
            return back()->withErrors(['error' => 'Cannot delete trade as there are beneficiaries registered under this job profile.']);
        }

        $trade->delete();

        AuditLogger::log('DELETE', 'trade', $id, $tradeName);

        return redirect()->route('super-admin.trades')->with('success', 'Trade deleted successfully.');
    }
}

