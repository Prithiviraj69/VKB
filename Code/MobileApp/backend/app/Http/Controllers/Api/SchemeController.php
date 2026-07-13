<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scheme;
use App\Models\SchemeApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SchemeController extends Controller
{
    // GET /api/schemes
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            // Guest / Public gets approved schemes
            $schemes = Scheme::where('status', 'approved')->orWhere('is_active', true)->latest('created_at')->get();
            return response()->json(['success' => true, 'data' => ['data' => $schemes]]);
        }

        if (in_array($user->role, ['beneficiary', 'worker'])) {
            // Beneficiary gets approved schemes
            $schemes = Scheme::where('status', 'approved')->orWhere('is_active', true)->latest('created_at')->get();
            
            // Map whether they applied and status
            $worker = $user->worker;
            $applications = [];
            if ($worker) {
                $applications = SchemeApplication::where('worker_id', $worker->id)->get()->keyBy('scheme_id')->toArray();
            }

            $departments = \Illuminate\Support\Facades\DB::table('departments')->get()->keyBy('department_code')->toArray();
            
            $schemes = $schemes->map(function ($s) use ($applications, $departments) {
                // MongoDB ObjectID can be stringified
                $app = $applications[(string)$s->id] ?? null;
                $s->has_applied = ($app !== null);
                $s->application_status = $app ? ($app['status'] ?? null) : null;

                // Lookup department name
                $deptCode = $s->department_code ?? $s->created_by_dept_code ?? null;
                $dept = null;
                if ($deptCode !== null) {
                    $dept = $departments[$deptCode] ?? $departments[(string)$deptCode] ?? $departments[(int)$deptCode] ?? null;
                }
                $s->department_name = $dept ? ($dept->department_name ?? $dept['department_name'] ?? null) : null;
                return $s;
            });

            return response()->json(['success' => true, 'data' => ['data' => $schemes]]);
        }

        // Officials get scoped schemes
        $query = Scheme::query();
        $type = $request->query('type', 'pending');

        if (in_array($user->role, ['ngo', 'owner-dept-admin', 'govt-dept-admin'])) {
            // These roles see all schemes globally
        } elseif ($user->role === 'ngo-verifier' || $user->role === 'ngo-approver') {
            // NGO staff see schemes globally
        } elseif (in_array($user->role, ['dept-verifier', 'verifier', 'dept-approver', 'approver'])) {
            // Dept verifiers/approvers see schemes of their department
            $deptCode = $user->department_code;
            if ($deptCode) {
                $query->whereIn('department_code', [$deptCode, (string)$deptCode, (int)$deptCode]);
            }
        }

        if ($type === 'pending') {
            $query->where(function($q) {
                $q->where('status', 'pending')->orWhereNull('status');
            });
        } elseif ($type === 'verified') {
            $query->where('status', 'verified');
        } elseif ($type === 'approved') {
            $query->where('status', 'approved');
        }

        $schemes = $query->latest('created_at')->get();
        return response()->json(['success' => true, 'data' => ['data' => $schemes]]);
    }

    // GET /api/schemes/{id}
    public function show($id)
    {
        $scheme = Scheme::findOrFail($id);
        return response()->json(['success' => true, 'data' => $scheme]);
    }

    // POST /api/schemes
    public function store(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['ngo', 'owner-dept-admin', 'govt-dept-admin', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized role to add schemes'], 403);
        }

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'url'         => 'required|string|url',
        ]);

        $scheme = Scheme::create([
            'name'               => $request->title,
            'title'              => $request->title,
            'description'        => $request->description,
            'apply_url'          => $request->url,
            'url'                => $request->url,
            'created_by'         => $user->id,
            'creator_role'       => $user->role,
            'creator_name'       => $user->name,
            'department_code'    => $user->department_code,
            'status'             => 'pending',
            'is_active'          => false,
            'ministry'           => $user->role === 'ngo' ? 'NGO' : ($user->department_code ? 'Government Department' : 'Central Ministry'),
            'level'              => $user->role === 'ngo' ? 'NGO' : 'state',
            'eligibility_rules'  => [],
            'required_documents' => [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Scheme created successfully and sent for verification',
            'data'    => $scheme
        ], 201);
    }

    // POST /api/schemes/{id}/verify
    public function verify(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['dept-verifier', 'verifier', 'ngo-verifier', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized role to verify schemes'], 403);
        }

        $scheme = Scheme::findOrFail($id);

        // Scoping checks
        if ($user->role === 'ngo-verifier' && $scheme->created_by !== $user->registered_by) {
            return response()->json(['success' => false, 'message' => 'You can only verify schemes of your own NGO'], 403);
        }
        if (in_array($user->role, ['dept-verifier', 'verifier']) && $user->department_code) {
            if ((string)$scheme->department_code !== (string)$user->department_code) {
                return response()->json(['success' => false, 'message' => 'You can only verify schemes of your own department'], 403);
            }
        }

        $scheme->status = 'verified';
        $scheme->is_verified = true;
        $scheme->verified_by = $user->id;
        $scheme->verified_at = now()->toDateTimeString();
        $scheme->verifier_remarks = $request->input('remarks');
        $scheme->save();

        return response()->json(['success' => true, 'message' => 'Scheme verified and sent to approver', 'data' => $scheme]);
    }

    // POST /api/schemes/{id}/approve
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['dept-approver', 'approver', 'ngo-approver', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized role to approve schemes'], 403);
        }

        $scheme = Scheme::findOrFail($id);

        // Scoping checks
        if ($user->role === 'ngo-approver' && $scheme->created_by !== $user->registered_by) {
            return response()->json(['success' => false, 'message' => 'You can only approve schemes of your own NGO'], 403);
        }
        if (in_array($user->role, ['dept-approver', 'approver']) && $user->department_code) {
            if ((string)$scheme->department_code !== (string)$user->department_code) {
                return response()->json(['success' => false, 'message' => 'You can only approve schemes of your own department'], 403);
            }
        }

        $scheme->status = 'approved';
        $scheme->is_approved = true;
        $scheme->is_active = true;
        $scheme->approved_by = $user->id;
        $scheme->approved_at = now()->toDateTimeString();
        $scheme->approver_remarks = $request->input('remarks');
        $scheme->save();

        return response()->json(['success' => true, 'message' => 'Scheme approved and published successfully', 'data' => $scheme]);
    }

    // POST /api/schemes/{id}/apply
    public function apply(Request $request, $id)
    {
        $scheme = Scheme::findOrFail($id);
        $worker = $request->user()->worker;

        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Complete your profile first'], 422);
        }

        $existing = SchemeApplication::where('worker_id', $worker->id)
            ->where('scheme_id', $scheme->id)
            ->whereNotIn('status', ['rejected'])
            ->first();

        if ($existing) {
            return response()->json(['success' => true, 'message' => 'Already applied', 'redirect_url' => $scheme->apply_url]);
        }

        $application = SchemeApplication::create([
            'application_number' => 'APP' . strtoupper(Str::random(8)),
            'worker_id'          => $worker->id,
            'scheme_id'          => $scheme->id,
            'status'             => 'applied',
            'applied_at'         => now()->toDateTimeString(),
            'submitted_data'     => $request->input('data', []),
        ]);

        return response()->json([
            'success'      => true,
            'message'      => 'Application recorded successfully',
            'redirect_url' => $scheme->apply_url,
            'data'         => $application
        ], 201);
    }

    // GET /api/schemes/my-applications
    public function myApplications(Request $request)
    {
        $worker = $request->user()->worker;

        if (!$worker) {
            return response()->json(['success' => true, 'data' => ['data' => []]]);
        }

        $applications = $worker->schemeApplications()
            ->with('scheme')
            ->latest('applied_at')
            ->get();

        $departments = \Illuminate\Support\Facades\DB::table('departments')->get()->keyBy('department_code')->toArray();
        
        $applications->each(function ($app) use ($departments) {
            if ($app->scheme) {
                $s = $app->scheme;
                $deptCode = $s->department_code ?? $s->created_by_dept_code ?? null;
                $dept = null;
                if ($deptCode !== null) {
                    $dept = $departments[$deptCode] ?? $departments[(string)$deptCode] ?? $departments[(int)$deptCode] ?? null;
                }
                $s->department_name = $dept ? ($dept->department_name ?? $dept['department_name'] ?? null) : null;
            }
        });

        return response()->json(['success' => true, 'data' => ['data' => $applications]]);
    }

    // POST /api/schemes/{id}/receive-benefit
    public function receiveBenefit(Request $request, $id)
    {
        $worker = $request->user()->worker;
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Complete your profile first'], 422);
        }

        $application = SchemeApplication::where('worker_id', $worker->id)
            ->where('scheme_id', $id)
            ->first();

        if (!$application) {
            return response()->json(['success' => false, 'message' => 'Application not found for this scheme'], 404);
        }

        $application->status = 'received_benefit';
        $application->save();

        return response()->json([
            'success' => true,
            'message' => 'Benefit received status recorded successfully',
            'data'    => $application
        ]);
    }

    // GET /api/scheme-applications
    public function listApplications(Request $request)
    {
        $user = $request->user();
        if (in_array($user->role, ['beneficiary', 'worker'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $type = $request->query('status', 'pending');
        $deptCode = $user->department_code ?? null;

        $query = SchemeApplication::query()->with(['scheme', 'worker']);

        // NGO / Dept Scoping
        if ($user->role === 'ngo-verifier' || $user->role === 'ngo-approver') {
            $query->whereHas('scheme', function($q) use ($user) {
                $q->where('created_by', $user->registered_by);
            });
        } else if ($deptCode && $user->role !== 'super-admin') {
            $query->whereHas('scheme', function($q) use ($deptCode) {
                $q->whereIn('department_code', [$deptCode, (string)$deptCode, (int)$deptCode]);
            });
        }

        if ($type === 'pending') {
            $query->where('status', 'applied');
        } elseif ($type === 'verified') {
            $query->where('status', 'verified');
        } elseif ($type === 'approved') {
            $query->where('status', 'approved');
        } elseif ($type === 'rejected') {
            $query->where('status', 'rejected');
        }

        $applications = $query->latest('applied_at')->get();

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }

    // POST /api/scheme-applications/{id}/verify
    public function verifyApplication(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['dept-verifier', 'verifier', 'ngo-verifier', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $application = SchemeApplication::findOrFail($id);
        $application->status = 'verified';
        $application->reviewed_by = $user->id;
        $application->reviewed_at = now()->toDateTimeString();
        $application->remarks = $request->input('remarks', 'Verified');
        $application->save();

        return response()->json([
            'success' => true,
            'message' => 'Scheme application verified successfully',
            'data' => $application
        ]);
    }

    // POST /api/scheme-applications/{id}/approve
    public function approveApplication(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['dept-approver', 'approver', 'ngo-approver', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $application = SchemeApplication::findOrFail($id);
        $application->status = 'approved';
        $application->reviewed_by = $user->id;
        $application->approved_at = now()->toDateTimeString();
        $application->remarks = $request->input('remarks', 'Approved');
        $application->save();

        return response()->json([
            'success' => true,
            'message' => 'Scheme application approved successfully',
            'data' => $application
        ]);
    }

    // POST /api/scheme-applications/{id}/reject
    public function rejectApplication(Request $request, $id)
    {
        $user = $request->user();
        if (in_array($user->role, ['beneficiary', 'worker'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $application = SchemeApplication::findOrFail($id);
        $application->status = 'rejected';
        $application->reviewed_by = $user->id;
        $application->reviewed_at = now()->toDateTimeString();
        $application->remarks = $request->input('remarks', 'Rejected');
        $application->save();

        return response()->json([
            'success' => true,
            'message' => 'Scheme application rejected successfully',
            'data' => $application
        ]);
    }

    // PUT /api/schemes/{id}
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (in_array($user->role, ['beneficiary', 'worker'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $scheme = Scheme::findOrFail($id);

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'url'         => 'required|string|url',
        ]);

        $scheme->update([
            'name'        => $request->title,
            'title'       => $request->title,
            'description' => $request->description,
            'apply_url'   => $request->url,
            'url'         => $request->url,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Scheme updated successfully',
            'data'    => $scheme
        ]);
    }

    // DELETE /api/schemes/{id}
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (in_array($user->role, ['beneficiary', 'worker'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $scheme = Scheme::findOrFail($id);
        $scheme->delete();

        return response()->json([
            'success' => true,
            'message' => 'Scheme deleted successfully'
        ]);
    }

    // POST /api/schemes/{id}/toggle-active
    public function toggleActive(Request $request, $id)
    {
        $user = $request->user();
        if (in_array($user->role, ['beneficiary', 'worker'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $scheme = Scheme::findOrFail($id);
        $scheme->is_active = !$scheme->is_active;
        $scheme->save();

        return response()->json([
            'success' => true,
            'message' => 'Scheme active state toggled successfully',
            'is_active' => $scheme->is_active
        ]);
    }
}
