<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['super-admin', 'owner-dept-admin'])) {
            abort(403, 'Unauthorized access to activity logs.');
        }

        $query = ActivityLog::query();

        // 1. Search filter: Operator Name / Email or Target Resource Name
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', '%' . $search . '%')
                  ->orWhere('user_email', 'like', '%' . $search . '%')
                  ->orWhere('resource_name', 'like', '%' . $search . '%');
            });
        }

        // 2. Action Filter
        if ($request->filled('action')) {
            $query->where('action', strtoupper($request->action));
        }

        // 3. Resource Type Filter
        if ($request->filled('resource_type')) {
            $query->where('resource_type', strtolower($request->resource_type));
        }

        // 4. Date Filter
        if ($request->filled('date')) {
            $date = $request->date;
            $query->whereDate('created_at', '=', $date);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $actions = ['CREATE', 'DELETE', 'TOGGLE_STATUS', 'VERIFY', 'APPROVE', 'REJECT', 'IMPORT', 'CHANGE_PASSWORD'];
        $resourceTypes = ['worker', 'scheme', 'ngo', 'employer', 'contractor', 'department', 'owner-department', 'user'];

        return view('admin.activity_logs', compact('logs', 'actions', 'resourceTypes'));
    }
}
