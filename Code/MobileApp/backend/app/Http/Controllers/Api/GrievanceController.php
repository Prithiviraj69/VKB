<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grievance;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GrievanceController extends Controller
{
    // GET /api/grievances
    public function index(Request $request)
    {
        $user = $request->user();

        if (in_array($user->role, ['beneficiary', 'worker'])) {
            $worker = $user->worker;
            if (!$worker) {
                return response()->json(['success' => true, 'data' => ['data' => []]]);
            }
            $grievances = $worker->grievances()
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->latest()
                ->paginate(15);
        } else {
            $deptCode = $user->department_code ?? null;
            $query = Grievance::query();

            if ($user->role === 'dept-owner') {
                $query->whereHas('worker', function($q) {
                    $q->where('registration_source', 'self');
                });
            }

            if ($deptCode && $user->role !== 'super-admin') {
                $query->whereHas('worker', function($q) use ($deptCode) {
                    $q->whereIn('department_code', [$deptCode, (string)$deptCode, (int)$deptCode]);
                });
            }

            if ($request->status) {
                $statusVal = $request->status === 'new' ? 'open' : $request->status;
                $query->where('status', $statusVal);
            }

            $grievances = $query->with('worker')->latest()->paginate(15);
        }

        return response()->json(['success' => true, 'data' => $grievances]);
    }

    // POST /api/grievances/{id}/action
    public function takeAction(Request $request, $id)
    {
        $user = $request->user();
        if (in_array($user->role, ['beneficiary', 'worker'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized role'], 403);
        }

        $request->validate([
            'status' => 'required|in:open,pending,disposed',
            'resolution_note' => 'nullable|string',
        ]);

        $grievance = Grievance::findOrFail($id);

        $grievance->status = $request->status;
        if ($request->status === 'disposed') {
            $grievance->resolved_at = now();
        }
        if ($request->has('resolution_note')) {
            $grievance->resolution_note = $request->resolution_note;
        }
        $grievance->save();

        return response()->json([
            'success' => true,
            'message' => 'Grievance updated successfully',
            'data' => $grievance
        ]);
    }

    // POST /api/grievances
    public function store(Request $request)
    {
        $worker = $request->user()->worker;

        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Complete your profile first'], 422);
        }

        $validated = $request->validate([
            'category'    => 'required|string',
            'subject'     => 'required|string|max:255',
            'description' => 'required|string',
            'priority'    => 'nullable|in:low,medium,high',
        ]);

        $grievance = Grievance::create([
            'ticket_number' => 'GRV-' . strtoupper(Str::random(8)),
            'worker_id'     => $worker->id,
            'category'      => $validated['category'],
            'subject'       => $validated['subject'],
            'description'   => $validated['description'],
            'priority'      => $validated['priority'] ?? 'medium',
            'status'        => 'open',
        ]);

        return response()->json(['success' => true, 'message' => 'Grievance filed successfully', 'data' => $grievance], 201);
    }

    // GET /api/grievances/{id}
    public function show(Request $request, $id)
    {
        $worker    = $request->user()->worker;
        $grievance = Grievance::with('comments')
            ->where('worker_id', $worker?->id)
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $grievance]);
    }
}
