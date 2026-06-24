<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    // POST /api/login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid email or password'], 401);
        }

        if (isset($user->is_active) && !$user->is_active) {
            return response()->json(['success' => false, 'message' => 'Your account is deactivated'], 403);
        }

        $token = JwtService::generateToken([
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    // POST /api/super-admin/create-user
    public function createUser(Request $request)
    {
        $admin = $request->user();
        if ($admin->role !== 'super-admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden: Only Super Admin can perform this action'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:approver,verifier',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ]
        ], 201);
    }

    // GET /api/super-admin/users
    public function getUsers(Request $request)
    {
        $admin = $request->user();
        if ($admin->role !== 'super-admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        // Fetch all users with role 'approver', 'verifier', or 'worker'
        $users = User::whereIn('role', ['approver', 'verifier', 'worker'])->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    // PATCH /api/super-admin/user/{id}/toggle-status
    public function toggleStatus(Request $request, $id)
    {
        $admin = $request->user();
        if ($admin->role !== 'super-admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if ($user->role === 'super-admin') {
            return response()->json(['success' => false, 'message' => 'Cannot modify Super Admin status'], 400);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ]
        ]);
    }

    // GET /api/super-admin/labour-data
    public function getLabourData(Request $request)
    {
        $admin = $request->user();
        if ($admin->role !== 'super-admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $status = $request->query('status');
        $search = $request->query('search');
        $export = $request->query('export'); // 'csv' or boolean

        // Determine if we should read from 'workers' fallback
        $useWorkersFallback = false;
        try {
            $useWorkersFallback = (DB::table('labour_registrations')->count() === 0);
        } catch (\Exception $e) {
            $useWorkersFallback = true;
        }

        if ($useWorkersFallback) {
            $query = DB::table('workers');

            if ($status) {
                if ($status === 'approved') {
                    $query->where('is_verified', true);
                } else if ($status === 'pending') {
                    $query->where(function($q) {
                        $q->where('is_verified', false)->orWhereNull('is_verified')->orWhere('is_verified', 0);
                    });
                }
            }

            if ($search) {
                $query->where('full_name', 'like', '%' . $search . '%');
            }

            $query->orderBy('created_at', 'desc');

            if ($export === 'csv' || $export === 'true') {
                $data = $query->get()->map(function ($item) {
                    return $this->mapWorkerToLabour($item);
                });
                return response()->json([
                    'success' => true,
                    'data' => $data,
                ]);
            }

            $paginated = $query->paginate(15);
            $paginated->getCollection()->transform(function ($item) {
                return $this->mapWorkerToLabour($item);
            });

            return response()->json([
                'success' => true,
                'data' => $paginated,
            ]);

        } else {
            // Standard query on labour_registrations
            $query = DB::table('labour_registrations');

            if ($status) {
                $query->where('status', $status);
            }

            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            $query->orderBy('created_at', 'desc');

            if ($export === 'csv' || $export === 'true') {
                $data = $query->get();
                return response()->json([
                    'success' => true,
                    'data' => $data,
                ]);
            }

            $paginated = $query->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $paginated,
            ]);
        }
    }

    private function mapWorkerToLabour($worker)
    {
        $worker = (array) $worker;
        return [
            'id' => $worker['id'] ?? $worker['_id'] ?? '',
            'name' => $worker['full_name'] ?? 'N/A',
            'dob' => $this->formatDate($worker['dob'] ?? null),
            'gender' => $worker['gender'] ?? 'N/A',
            'marital_status' => $worker['marital_status'] ?? 'N/A',
            'job_profile' => $worker['job_profile'] ?? 'N/A',
            'status' => (isset($worker['is_verified']) && ($worker['is_verified'] == true || $worker['is_verified'] == 1)) ? 'approved' : 'pending',
            'address' => $worker['aadhaar_address'] ?? $worker['local_address'] ?? 'N/A',
            'created_at' => $this->formatDate($worker['created_at'] ?? null),
        ];
    }

    private function formatDate($date)
    {
        if (empty($date)) return 'N/A';
        if (is_string($date)) {
            return explode(' ', $date)[0];
        }
        if (is_object($date)) {
            if (method_exists($date, 'toDateTimeString')) {
                return explode(' ', $date->toDateTimeString())[0];
            }
            if (isset($date->date)) {
                return explode(' ', $date->date)[0];
            }
        }
        if (is_array($date)) {
            if (isset($date['date'])) {
                return explode(' ', $date['date'])[0];
            }
        }
        return 'N/A';
    }

    // DELETE /api/super-admin/user/{id}
    public function deleteUser(Request $request, $id)
    {
        $admin = $request->user();
        if ($admin->role !== 'super-admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            // Check if it's a worker ID and find by associated user
            $worker = DB::table('workers')->where('_id', $id)->first();
            if ($worker && isset($worker['user_id'])) {
                $user = User::find($worker['user_id']);
            }
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if ($user->role === 'super-admin') {
            return response()->json(['success' => false, 'message' => 'Cannot delete Super Admin account'], 400);
        }

        // Relational deletion for workers
        if ($user->role === 'worker') {
            $worker = $user->worker;
            if ($worker) {
                // Delete related records
                $worker->familyMembers()->delete();
                $worker->documents()->delete();
                $worker->grievances()->delete();
                $worker->delete();
            }
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User and associated records deleted successfully',
        ]);
    }
}
