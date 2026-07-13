<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Worker;
use App\Models\Scheme;
use App\Models\SchemeApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InstitutionController extends Controller
{
    // GET /api/institution/registered-users
    public function getRegisteredUsers(Request $request)
    {
        $user = $request->user();

        if (in_array($user->role, ['super-admin', 'owner-dept-admin', 'ngo', 'employer', 'contractor'])) {
            // These roles see all registered users globally (except themselves)
            $users = User::where('id', '!=', $user->id)->latest('created_at')->get();
        } else {
            // Other institutions see users they registered, newest first
            $users = User::where('registered_by', $user->id)->latest('created_at')->get();
        }

        // Include worker details if role is beneficiary
        $users = $users->map(function ($u) {
            if ($u->role === 'beneficiary') {
                $u->worker_details = Worker::where('user_id', $u->id)->first();
            }
            return $u;
        });

        return response()->json([
            'success' => true,
            'data'    => $users
        ]);
    }

    // GET /api/institution/check-mobile/{mobile}
    public function checkMobile($mobile)
    {
        $exists = User::where('mobile', $mobile)->exists();
        return response()->json([
            'success' => true,
            'exists'  => $exists,
            'message' => $exists ? 'Mobile number is already registered' : 'Mobile number is available'
        ]);
    }

    // POST /api/institution/register-beneficiary-complete
    public function registerBeneficiaryComplete(Request $request)
    {
        $creator = $request->user();
        
        $validator = Validator::make($request->all(), [
            'full_name'       => 'required|string|max:255',
            'mobile'          => 'required|digits:10|unique:users,mobile',
            'dob'             => 'required|date_format:Y-m-d',
            'gender'          => 'required|string|in:male,female,other',
            'aadhaar_address' => 'required|string',
            'ration_card_number' => 'nullable|string|max:100',
            'job_profile'     => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        // ── Ration Card uniqueness check ───────────────────────────────────
        if (!empty($request->ration_card_number)) {
            $rationExists = \App\Models\Worker::where('ration_card_number', trim($request->ration_card_number))->exists();
            if ($rationExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Ration Card Number is already registered in the system. Duplicate registration is not allowed.',
                ], 422);
            }
        }

        $isDeptAdmin = in_array($creator->role, ['owner-dept-admin', 'govt-dept-admin']);
        $status = $isDeptAdmin ? 'approved' : 'pending';

        // Create User
        $newUser = User::create([
            'name'          => $request->full_name,
            'mobile'        => $request->mobile,
            'role'          => 'beneficiary',
            'is_active'     => true,
            'is_verified'   => $isDeptAdmin,
            'registered_by' => $creator->id,
            'ngo_id'        => $creator->ngo_id ?? null,
        ]);

        $familyData = [];
        if ($request->has('family')) {
            $familyInput = $request->input('family');
            if (is_string($familyInput)) {
                $familyData = json_decode($familyInput, true) ?? [];
            } else if (is_array($familyInput)) {
                $familyData = $familyInput;
            }
            $fullName = strtolower(trim($request->full_name));
            $familyData = array_values(array_filter($familyData, function($m) use ($fullName) {
                return strtolower(trim($m['name'] ?? '')) !== $fullName;
            }));
        }

        // Create Worker profile
        $worker = Worker::create([
            'user_id'            => $newUser->id,
            'worker_code'        => 'WK' . strtoupper(uniqid()),
            'profile_score'      => 40,
            'full_name'          => $request->full_name,
            'dob'                => $request->dob,
            'gender'             => $request->gender,
            'aadhaar_address'    => $request->aadhaar_address,
            'ration_card_number' => $request->ration_card_number,
            'job_profile'        => $request->job_profile,
            'is_active'          => true,
            'registration_source'=> $isDeptAdmin ? 'department' : $creator->role,
            'department_code'    => $creator->department_code,
            'is_verified'        => $isDeptAdmin ? true : false,
            'verified_by'        => $isDeptAdmin ? $creator->id : null,
            'verified_at'        => $isDeptAdmin ? now()->toDateTimeString() : null,
            'verified_by_dept'   => $isDeptAdmin ? $creator->department_code : null,
            'status'             => $status,
            'approved_by'        => $isDeptAdmin ? $creator->id : null,
            'approved_at'        => $isDeptAdmin ? now()->toDateTimeString() : null,
            'registration_no'    => $isDeptAdmin ? 'REG-' . date('Y') . '-' . rand(100000, 999999) : null,
            'family_members'     => $familyData,
            'created_by'         => $creator->id,
            'ngo_id'             => $creator->ngo_id ?? null,
            'created_by_ngo_id'  => $creator->ngo_id ?? null,
        ]);

        // Save family members to relations collection
        if (!empty($familyData)) {
            foreach ($familyData as $member) {
                $worker->familyMembers()->create([
                    'name'     => $member['name'] ?? '',
                    'relation' => $member['relation'] ?? '',
                    'gender'   => $member['gender'] ?? '',
                    'age'      => isset($member['age']) ? (int) $member['age'] : null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary worker registered successfully with family details.',
            'user'    => $newUser,
            'worker'  => $worker
        ]);
    }

    // POST /api/institution/register-user
    public function registerUser(Request $request)
    {
        $creator = $request->user();
        $creatorRole = $creator->role;

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:verifier,approver,ngo,employer,contractor,beneficiary,dept-verifier,dept-approver,govt-dept-admin,ngo-verifier,ngo-approver',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $targetRole = $request->role;

        // Hierarchy check
        if (!$this->checkHierarchy($creatorRole, $targetRole)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to register a user with role: ' . $targetRole], 403);
        }

        if ($targetRole === 'beneficiary') {
            // Worker registration fields
            $validator = Validator::make($request->all(), [
                'full_name'       => 'required|string|max:255',
                'mobile'          => 'required|digits:10|unique:users,mobile',
                'dob'             => 'required|date_format:Y-m-d',
                'gender'          => 'required|string|in:male,female,other',
                'aadhaar_address' => 'required|string',
                'job_profile'     => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            // Create User — auto-verified since registered by owner dept admin
            $newUser = User::create([
                'name'          => $request->full_name,
                'mobile'        => $request->mobile,
                'role'          => 'beneficiary',
                'is_active'     => true,
                'is_verified'   => true,
                'registered_by' => $creator->id,
            ]);

            $isDeptAdmin = in_array($creator->role, ['owner-dept-admin', 'govt-dept-admin']);
            $status = $isDeptAdmin ? 'approved' : 'verified';

            // Create Worker profile — auto-verified since registered by owner dept admin
            $worker = Worker::create([
                'user_id'            => $newUser->id,
                'worker_code'        => 'WK' . strtoupper(uniqid()),
                'full_name'          => $request->full_name,
                'dob'                => $request->dob,
                'gender'             => $request->gender,
                'aadhaar_address'    => $request->aadhaar_address,
                'job_profile'        => $request->job_profile,
                'is_active'          => true,
                'registration_source'=> 'department',
                'department_code'    => $creator->department_code,
                // Auto-verify: owner dept admin vouch for this beneficiary
                'is_verified'        => true,
                'verified_by'        => $creator->id,
                'verified_at'        => now()->toDateTimeString(),
                'verified_by_dept'   => $creator->department_code,
                'status'             => $status,
                'approved_by'        => $isDeptAdmin ? $creator->id : null,
                'approved_at'        => $isDeptAdmin ? now()->toDateTimeString() : null,
                'registration_no'    => $isDeptAdmin ? 'REG-' . date('Y') . '-' . rand(100000, 999999) : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Beneficiary registered successfully',
                'user'    => $newUser,
                'worker'  => $worker
            ]);

        } else {
            // Institution registration fields
            $validator = Validator::make($request->all(), [
                'name'        => 'required|string|max:255',
                'email'       => 'required|email|unique:users,email',
                'password'    => 'required|string|min:6',
                'designation' => 'nullable|string|max:255',
                'mobile'      => 'nullable|string|max:15',
                'address'     => 'nullable|string',
                'reg_no'      => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            $ngoId = null;
            if ($creator->role === 'ngo') {
                $ngo = DB::table('ngos')->where('email', $creator->email)->first();
                if ($ngo) {
                    $ngoId = $ngo->_id ?? $ngo->id ?? null;
                }
            } else if (!empty($creator->ngo_id)) {
                $ngoId = $creator->ngo_id;
            }

            if (in_array($targetRole, ['ngo-verifier', 'ngo-approver']) && $ngoId) {
                $exists = User::where('ngo_id', $ngoId)
                    ->where('role', $targetRole)
                    ->exists();
                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A ' . ($targetRole === 'ngo-verifier' ? 'Verifier' : 'Approver') . ' already exists for this NGO. Only one of each is allowed.'
                    ], 422);
                }
            }

            // Create User
            $newUser = User::create([
                'name'            => $request->name,
                'email'           => $request->email,
                'password'        => Hash::make($request->password),
                'role'            => $targetRole,
                'is_active'       => true,
                'registered_by'   => $creator->id,
                'designation'     => $request->designation,
                'mobile'          => $request->mobile,
                'department_code' => $creator->department_code,
                'address'         => $request->address,
                'reg_no'          => $request->reg_no,
                'ngo_id'          => $ngoId,
            ]);

            return response()->json([
                'success' => true,
                'message' => ucfirst($targetRole) . ' registered successfully',
                'user'    => $newUser
            ]);
        }
    }

    // POST /api/institution/bulk-register
    public function bulkRegister(Request $request)
    {
        $creator = $request->user();
        $creatorRole = $creator->role;

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:4096',
            'role' => 'required|string|in:verifier,approver,ngo,employer,contractor,beneficiary',
        ]);

        $targetRole = $request->role;

        // Hierarchy check
        if (!$this->checkHierarchy($creatorRole, $targetRole)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to register a user with role: ' . $targetRole], 403);
        }

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return response()->json(['success' => false, 'message' => 'Unable to read the uploaded CSV file'], 422);
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return response()->json(['success' => false, 'message' => 'Empty CSV file'], 422);
        }

        // Clean headers (remove BOM/spaces/lowercase)
        $headers = array_map(function($h) {
            return strtolower(trim(preg_replace('/[\x{FEFF}\x{200B}-\x{200D}]/u', '', $h)));
        }, $headers);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $rowNum = 1; // Row 1 is header

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty($row) || (count($row) === 1 && $row[0] === null)) {
                continue;
            }

            // Combine headers and values
            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            } else if (count($row) > count($headers)) {
                $row = array_slice($row, 0, count($headers));
            }

            $data = array_combine($headers, $row);

            if ($targetRole === 'beneficiary') {
                // Beneficiary headers: full_name, mobile, dob, gender, aadhaar_address
                $val = Validator::make($data, [
                    'full_name'       => 'required|string|max:255',
                    'mobile'          => 'required|digits:10',
                    'dob'             => 'required|date_format:Y-m-d',
                    'gender'          => 'required|string|in:male,female,other',
                    'aadhaar_address' => 'required|string',
                    'job_profile'     => 'nullable|string|max:255',
                ]);

                if ($val->fails()) {
                    $failedCount++;
                    $errors[] = "Row {$rowNum}: Validation failed: " . $val->errors()->first();
                    continue;
                }

                // Check mobile unique
                if (User::where('mobile', $data['mobile'])->exists()) {
                    $failedCount++;
                    $errors[] = "Row {$rowNum}: Mobile number {$data['mobile']} is already registered.";
                    continue;
                }

                // Create User — auto-verified since registered by owner dept admin
                $newUser = User::create([
                    'name'          => $data['full_name'],
                    'mobile'        => $data['mobile'],
                    'role'          => 'beneficiary',
                    'is_active'     => true,
                    'is_verified'   => true,
                    'registered_by' => $creator->id,
                ]);

                $isDeptAdmin = in_array($creator->role, ['owner-dept-admin', 'govt-dept-admin']);
                $status = $isDeptAdmin ? 'approved' : 'verified';

                // Create Worker profile — auto-verified since registered by owner dept admin
                Worker::create([
                    'user_id'            => $newUser->id,
                    'worker_code'        => 'WK' . strtoupper(uniqid()),
                    'full_name'          => $data['full_name'],
                    'dob'                => $data['dob'],
                    'gender'             => $data['gender'],
                    'aadhaar_address'    => $data['aadhaar_address'],
                    'job_profile'        => $data['job_profile'] ?? 'Carpenter',
                    'is_active'          => true,
                    'registration_source'=> 'department',
                    'department_code'    => $creator->department_code,
                    // Auto-verify: owner dept admin vouch for this beneficiary
                    'is_verified'        => true,
                    'verified_by'        => $creator->id,
                    'verified_at'        => now()->toDateTimeString(),
                    'verified_by_dept'   => $creator->department_code,
                    'status'             => $status,
                    'approved_by'        => $isDeptAdmin ? $creator->id : null,
                    'approved_at'        => $isDeptAdmin ? now()->toDateTimeString() : null,
                    'registration_no'    => $isDeptAdmin ? 'REG-' . date('Y') . '-' . rand(100000, 999999) : null,
                ]);

                $successCount++;

            } else {
                // Institution headers: name, email, password
                $val = Validator::make($data, [
                    'name'     => 'required|string|max:255',
                    'email'    => 'required|email',
                    'password' => 'required|string|min:6',
                ]);

                if ($val->fails()) {
                    $failedCount++;
                    $errors[] = "Row {$rowNum}: Validation failed: " . $val->errors()->first();
                    continue;
                }

                if (User::where('email', $data['email'])->exists()) {
                    $failedCount++;
                    $errors[] = "Row {$rowNum}: Email {$data['email']} is already registered.";
                    continue;
                }

                // Create User
                User::create([
                    'name'          => $data['name'],
                    'email'         => $data['email'],
                    'password'      => Hash::make($data['password']),
                    'role'          => $targetRole,
                    'is_active'     => true,
                    'registered_by' => $creator->id,
                ]);

                $successCount++;
            }
        }

        fclose($handle);

        return response()->json([
            'success'       => true,
            'message'       => "Bulk registration completed. Success: {$successCount}, Failed: {$failedCount}",
            'success_count' => $successCount,
            'failed_count'  => $failedCount,
            'errors'        => $errors,
        ]);
    }

    private function checkHierarchy($creatorRole, $targetRole)
    {
        if ($creatorRole === 'super-admin') {
            return true;
        }

        if (in_array($creatorRole, ['owner-dept-admin', 'govt-dept-admin'])) {
            return in_array($targetRole, ['dept-verifier', 'dept-approver', 'govt-dept-admin', 'ngo', 'employer', 'contractor']);
        }

        if (in_array($creatorRole, ['approver', 'verifier'])) {
            return in_array($targetRole, ['ngo', 'employer', 'contractor', 'beneficiary']);
        }

        if ($creatorRole === 'ngo') {
            return in_array($targetRole, ['employer', 'contractor', 'beneficiary', 'ngo-verifier', 'ngo-approver']);
        }

        if ($creatorRole === 'employer') {
            return in_array($targetRole, ['contractor', 'beneficiary']);
        }

        if ($creatorRole === 'contractor') {
            return $targetRole === 'beneficiary';
        }

        return false;
    }

    // POST /api/institution/toggle-user-status/{id}
    public function toggleUserStatus(Request $request, $id)
    {
        $creator = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Only super-admin or the user who registered them can toggle status
        if ($creator->role !== 'super-admin' && $user->registered_by !== $creator->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to modify this user'], 403);
        }

        $user->is_active = !($user->is_active ?? true);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'is_active' => $user->is_active
        ]);
    }

    // GET /api/institution/dashboard-stats
    public function getDashboardStats(Request $request)
    {
        $user = $request->user();
        $deptCode = $user->department_code ?? null;

        if (in_array($user->role, ['owner-dept-admin', 'govt-dept-admin', 'super-admin', 'ngo', 'employer', 'contractor'])) {
            // Count verifiers/approvers registered BY this admin (already scoped via registered_by)
            $verifiersCount = User::where('registered_by', $user->id)
                ->whereIn('role', ['dept-verifier', 'verifier', 'ngo-verifier'])
                ->count();

            $approversCount = User::where('registered_by', $user->id)
                ->whereIn('role', ['dept-approver', 'approver', 'ngo-approver'])
                ->count();

            // Scope worker counts by department_code when available
            $workerBase = Worker::query();
            if ($deptCode && $user->role !== 'super-admin') {
                $workerBase->whereIn('department_code', [$deptCode, (string) $deptCode, (int) $deptCode]);
            }

            $pendingWorkersCount = 0;
            $verifiedWorkersCount = 0;
            $approvedWorkersCount = 0;

            if ($user->role !== 'ngo' && $user->role !== 'employer' && $user->role !== 'contractor') {
                $pendingWorkersCount = (clone $workerBase)
                    ->where(function($q) { $q->where('is_verified', '!=', true)->orWhereNull('is_verified'); })
                    ->where(function($q) { $q->where('status', '!=', 'approved')->orWhereNull('status'); })
                    ->count();

                $verifiedWorkersCount = (clone $workerBase)
                    ->where('is_verified', true)
                    ->where(function($q) { $q->where('status', '!=', 'approved')->orWhereNull('status'); })
                    ->count();

                $approvedWorkersCount = (clone $workerBase)
                    ->where('status', 'approved')
                    ->count();
            }

            // Count schemes added by this user
            $pendingSchemes = Scheme::where('created_by', $user->id)->where(fn($q) => $q->where('status', 'pending')->orWhereNull('status'))->count();
            $verifiedSchemes = Scheme::where('created_by', $user->id)->where('status', 'verified')->count();
            $approvedSchemes = Scheme::where('created_by', $user->id)->where('status', 'approved')->count();

            // Total statistics (for owner-dept-admin only)
            $activeSchemesGlobal = 0;
            $inactiveSchemesGlobal = 0;
            $totalApplicationsGlobal = 0;
            $govtDeptsCount = 0;
            $ngosCount = 0;
            $employersCount = 0;
            $contractorsCount = 0;
            $beneficiariesCount = 0;
            $schemesCount = 0;

            if ($user->role === 'owner-dept-admin') {
                // MongoDB-safe active/inactive scheme counts
                $activeSchemesGlobal   = Scheme::where('is_active', true)->count();
                $allSchemesCount       = Scheme::count();
                $inactiveSchemesGlobal = $allSchemesCount - $activeSchemesGlobal;
                $totalApplicationsGlobal = SchemeApplication::count();

                // Institution counts (all users globally by role)
                $govtDeptsCount  = User::whereIn('role', ['govt-dept-admin', 'dept-owner', 'owner-dept-admin'])->count();
                $ngosCount       = User::where('role', 'ngo')->count();
                $employersCount  = User::where('role', 'employer')->count();
                $contractorsCount = User::where('role', 'contractor')->count();
                $beneficiariesCount = User::where('role', 'beneficiary')->count();
                $schemesCount = Scheme::count();
            } else if ($user->role === 'ngo') {
                $employersCount = User::where('role', 'employer')->count();
                $contractorsCount = User::where('role', 'contractor')->count();
                $beneficiariesCount = User::where('role', 'beneficiary')->count();
                $schemesCount = Scheme::count();
            } else if ($user->role === 'employer') {
                $contractorsCount = User::where('role', 'contractor')->count();
                $beneficiariesCount = User::where('role', 'beneficiary')->count();
                $schemesCount = Scheme::count();
            } else if ($user->role === 'contractor') {
                $beneficiariesCount = User::where('role', 'beneficiary')->count();
                $schemesCount = Scheme::count();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'verifiers'        => $verifiersCount,
                    'approvers'        => $approversCount,
                    'pending_workers'  => $pendingWorkersCount,
                    'verified_workers' => $verifiedWorkersCount,
                    'approved_workers' => $approvedWorkersCount,
                    'pending_schemes'  => $pendingSchemes,
                    'verified_schemes' => $verifiedSchemes,
                    'approved_schemes' => $approvedSchemes,
                    'active_schemes_global'   => $activeSchemesGlobal,
                    'inactive_schemes_global' => $inactiveSchemesGlobal,
                    'total_applications_global' => $totalApplicationsGlobal,
                    'govt_depts_count'  => $govtDeptsCount,
                    'ngos_count'        => $ngosCount,
                    'employers_count'   => $employersCount,
                    'contractors_count' => $contractorsCount,
                    'beneficiaries_count' => $beneficiariesCount,
                    'schemes_count'     => $schemesCount,
                    'department_code'  => $deptCode,
                ]
            ]);

        } else if ($user->role === 'dept-owner') {
            $pendingSelf = Worker::where('registration_source', 'self')
                ->where(fn($q) => $q->where('status', 'pending')->orWhereNull('status'))
                ->when($deptCode, fn($q) => $q->where('department_code', $deptCode))
                ->count();

            $verifiedSelf = Worker::where('registration_source', 'self')
                ->where('status', 'verified')
                ->when($deptCode, fn($q) => $q->where('department_code', $deptCode))
                ->count();

            $approvedSelf = Worker::where('registration_source', 'self')
                ->where('status', 'approved')
                ->when($deptCode, fn($q) => $q->where('department_code', $deptCode))
                ->count();

            $rejectedSelf = Worker::where('registration_source', 'self')
                ->where('status', 'rejected')
                ->when($deptCode, fn($q) => $q->where('department_code', $deptCode))
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_workers'  => $pendingSelf,
                    'verified_workers' => $verifiedSelf,
                    'approved_workers' => $approvedSelf,
                    'rejected_workers' => $rejectedSelf,
                    'department_code'  => $deptCode,
                ]
            ]);

        } else if (in_array($user->role, ['dept-verifier', 'verifier', 'ngo-verifier'])) {
            // Verifier stats
            $pendingCount = 0;
            $verifiedCount = 0;
            $approvedCount = 0;

            if ($user->role !== 'ngo-verifier') {
                $pendingCount = Worker::where(function($q) {
                        $q->where('is_verified', '!=', true)->orWhereNull('is_verified');
                    })->where(function($q) {
                        $q->where('status', '!=', 'approved')->orWhereNull('status');
                    })
                    ->where(function($q) use ($deptCode) {
                        $q->whereNull('department_code');
                        if ($deptCode) $q->orWhereIn('department_code', [$deptCode, (string) $deptCode, (int) $deptCode]);
                    })
                    ->count();

                $verifiedCount = Worker::where('is_verified', true)
                    ->where('verified_by', $user->id)
                    ->where(function($q) { $q->where('status', '!=', 'approved')->orWhereNull('status'); })
                    ->count();

                $approvedCount = Worker::where('status', 'approved')
                    ->where('verified_by', $user->id)
                    ->count();
            }

            // Schemes stats for verifier
            $schemeQuery = Scheme::query();
            if ($user->role === 'ngo-verifier') {
                $schemeQuery->where('created_by', $user->registered_by);
            } else if ($deptCode) {
                $schemeQuery->whereIn('department_code', [$deptCode, (string)$deptCode, (int)$deptCode]);
            }

            $pendingSchemes = (clone $schemeQuery)->where(fn($q) => $q->where('status', 'pending')->orWhereNull('status'))->count();
            $verifiedSchemes = (clone $schemeQuery)->where('status', 'verified')->where('verified_by', $user->id)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_verifications' => $pendingCount,
                    'verified_by_me'        => $verifiedCount,
                    'approved_by_me'        => $approvedCount,
                    'pending_schemes'       => $pendingSchemes,
                    'verified_schemes'      => $verifiedSchemes,
                    'department_code'       => $deptCode,
                ]
            ]);

        } else if (in_array($user->role, ['dept-approver', 'approver', 'ngo-approver'])) {
            // Approver stats
            $pendingCount = 0;
            $approvedCount = 0;

            if ($user->role !== 'ngo-approver') {
                $pendingCount = Worker::where('is_verified', true)
                    ->where(function($q) { $q->where('status', '!=', 'approved')->orWhereNull('status'); })
                    ->where(function($q) use ($deptCode) {
                        if ($deptCode) $q->whereIn('department_code', [$deptCode, (string) $deptCode, (int) $deptCode]);
                    })
                    ->count();

                $approvedCount = Worker::where('status', 'approved')
                    ->where('approved_by', $user->id)
                    ->count();
            }

            // Schemes stats for approver
            $schemeQuery = Scheme::query();
            if ($user->role === 'ngo-approver') {
                $schemeQuery->where('created_by', $user->registered_by);
            } else if ($deptCode) {
                $schemeQuery->whereIn('department_code', [$deptCode, (string)$deptCode, (int)$deptCode]);
            }

            $pendingApproveSchemes = (clone $schemeQuery)->where('status', 'verified')->count();
            $approvedSchemes = (clone $schemeQuery)->where('status', 'approved')->where('approved_by', $user->id)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_approvals'     => $pendingCount,
                    'approved_by_me'        => $approvedCount,
                    'pending_schemes'       => $pendingApproveSchemes,
                    'approved_schemes'      => $approvedSchemes,
                    'department_code'       => $deptCode,
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Role stats not supported'], 400);
    }

    // GET /api/institution/department-details
    public function getDepartmentDetails(Request $request)
    {
        $user = $request->user();
        
        $dept = null;
        if (!empty($user->department_code)) {
            $dept = \Illuminate\Support\Facades\DB::table('departments')
                ->where('department_code', (int) $user->department_code)
                ->first();
            
            // Fallback for string matches if int fails
            if (!$dept) {
                $dept = \Illuminate\Support\Facades\DB::table('departments')
                    ->where('department_code', $user->department_code)
                    ->first();
            }
        }

        return response()->json([
            'success' => true,
            'data' => $dept
        ]);
    }

    // GET /api/institution/workers
    public function getWorkers(Request $request)
    {
        $user     = $request->user();
        $type     = $request->query('type', 'pending');
        $source   = $request->query('source'); // e.g. 'self' or 'ngo' or 'employer' or 'contractor'
        $deptCode = $user->department_code ?? null;

        $query = Worker::query();

        if ($user->role === 'dept-owner') {
            $query->where('registration_source', 'self');
        } elseif (!empty($source)) {
            $query->where('registration_source', $source);
        }

        // ── Department scoping ──────────────────────────────────────────────
        // owner-dept-admin sees ALL workers globally (no scoping)
        // Verifiers/approvers/dept-owners only see workers of their dept.
        // Unassigned workers (null dept) are visible only to verifiers so
        // they can claim them by verifying.
        if ($user->role !== 'super-admin' && $user->role !== 'owner-dept-admin') {
            if ($deptCode) {
                if (in_array($user->role, ['dept-verifier', 'verifier', 'dept-owner'])) {
                    // Pending: show unassigned OR same-dept; verified/approved/rejected: same-dept only
                    if ($type === 'pending') {
                        $query->where(function($q) use ($deptCode) {
                            $q->whereNull('department_code')->orWhereIn('department_code', [$deptCode, (string) $deptCode, (int) $deptCode]);
                        });
                    } else {
                        $query->whereIn('department_code', [$deptCode, (string) $deptCode, (int) $deptCode]);
                    }
                } else {
                    // Approvers only see their dept
                    $query->whereIn('department_code', [$deptCode, (string) $deptCode, (int) $deptCode]);
                }
            }
        }
        // ────────────────────────────────────────────────────────────────────

        if ($type === 'pending') {
            $query->where(function($q) {
                $q->where('is_verified', '!=', true)->orWhereNull('is_verified');
            })->where(function($q) {
                $q->where('status', '!=', 'approved')->orWhereNull('status');
            })->where(function($q) {
                $q->where('status', '!=', 'rejected')->orWhereNull('status');
            });
        } elseif ($type === 'verified') {
            $query->where('is_verified', true)
                  ->where(function($q) {
                      $q->where('status', '!=', 'approved')->orWhereNull('status');
                  });
            if (in_array($user->role, ['dept-verifier', 'verifier'])) {
                $query->where('verified_by', $user->id);
            }
        } elseif ($type === 'approved') {
            $query->where('status', 'approved');
            // owner-dept-admin and super-admin see all approved workers
            if (!in_array($user->role, ['super-admin', 'owner-dept-admin'])) {
                if (in_array($user->role, ['dept-verifier', 'verifier'])) {
                    $query->where('verified_by', $user->id);
                } elseif (in_array($user->role, ['dept-approver', 'approver'])) {
                    $query->where('approved_by', $user->id);
                }
            }
        } elseif ($type === 'rejected') {
            $query->where('status', 'rejected');
        }

        $workers = $query->latest('updated_at')->get();
        $workers = $workers->map(function ($w) {
            $w->user_details = User::find($w->user_id);
            return $w;
        });

        return response()->json(['success' => true, 'data' => $workers]);
    }

    // POST /api/institution/workers/{id}/verify
    public function verifyWorker(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['dept-verifier', 'verifier', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized role'], 403);
        }

        $worker = Worker::find($id);
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker not found'], 404);
        }

        $deptCode = $user->department_code ?? null;

        $worker->status           = 'verified';
        $worker->is_verified      = true;
        $worker->verified_by      = $user->id;
        $worker->verified_at      = now()->toDateTimeString();
        $worker->verifier_remarks = $request->input('remarks');

        // Assign this worker to the verifier's department (if not already assigned)
        if ($deptCode && empty($worker->department_code)) {
            $worker->department_code = $deptCode;
        }
        // Always record which dept verified
        if ($deptCode) {
            $worker->verified_by_dept = $deptCode;
        }

        $worker->save();

        return response()->json([
            'success' => true,
            'message' => 'Worker verified successfully',
            'worker'  => $worker
        ]);
    }

    // POST /api/institution/workers/{id}/approve
    public function approveWorker(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['dept-approver', 'approver', 'ngo-approver', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized role'], 403);
        }

        $worker = Worker::find($id);
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker not found'], 404);
        }

        // Only allow approving workers of the same department (safety check)
        $deptCode = $user->department_code ?? null;
        if ($deptCode && $worker->department_code && (string) $worker->department_code !== (string) $deptCode) {
            return response()->json([
                'success' => false,
                'message' => 'You can only approve workers belonging to your department.'
            ], 403);
        }

        $worker->status           = 'approved';
        $worker->approved_by      = $user->id;
        $worker->approved_at      = now()->toDateTimeString();
        $worker->approver_remarks = $request->input('remarks');
        if (empty($worker->registration_no)) {
            $prefix = $user->role === 'ngo-approver' ? 'REG-NGO-' : 'REG-';
            $worker->registration_no = $prefix . date('Y') . '-' . rand(100000, 999999);
        }
        if ($deptCode) {
            $worker->approved_by_dept = $deptCode;
        }
        if ($user->ngo_id) {
            $worker->approved_by_ngo_id = $user->ngo_id;
        }
        $worker->save();

        // Mark User as verified so they get the green tick in the app
        $workerUser = User::find($worker->user_id);
        if ($workerUser) {
            $workerUser->is_verified = true;
            $workerUser->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Worker approved successfully',
            'worker'  => $worker
        ]);
    }

    // POST /api/institution/workers/{id}/reject
    public function rejectWorker(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['dept-verifier', 'verifier', 'dept-approver', 'approver', 'dept-owner', 'ngo-verifier', 'ngo-approver', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized role'], 403);
        }

        $worker = Worker::find($id);
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker not found'], 404);
        }

        $request->validate(['remarks' => 'required|string|min:3']);

        $worker->status           = 'rejected';
        $worker->is_verified      = false;
        $worker->verified_by      = $user->id;
        $worker->verified_at      = now()->toDateTimeString();
        $worker->verifier_remarks = $request->input('remarks');
        $worker->save();

        return response()->json([
            'success' => true,
            'message' => 'Worker rejected successfully',
            'worker'  => $worker
        ]);
    }

    // ── Helper: fetch department name from dept code ──
    private function getDeptName(?string $code): ?string
    {
        if (!$code) return null;
        $dept = \Illuminate\Support\Facades\DB::table('departments')
            ->where('department_code', $code)
            ->first();
        return $dept ? ($dept->name ?? $dept->department_name ?? $code) : $code;
    }
}
