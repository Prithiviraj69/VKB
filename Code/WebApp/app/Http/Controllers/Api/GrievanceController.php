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
        $worker = $request->user()->worker;

        if (!$worker) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $grievances = $worker->grievances()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $grievances]);
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
