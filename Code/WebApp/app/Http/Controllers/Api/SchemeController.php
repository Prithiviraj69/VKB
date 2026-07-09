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
        $schemes = Scheme::active()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->ministry, fn($q) => $q->where('ministry', $request->ministry))
            ->when($request->level, fn($q) => $q->where('level', $request->level))
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $schemes]);
    }

    // GET /api/schemes/{id}
    public function show($id)
    {
        $scheme = Scheme::findOrFail($id);
        return response()->json(['success' => true, 'data' => $scheme]);
    }

    // POST /api/schemes/{id}/apply
    public function apply(Request $request, $id)
    {
        $scheme = Scheme::active()->findOrFail($id);
        $worker = $request->user()->worker;

        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Complete your profile first'], 422);
        }

        $existing = SchemeApplication::where('worker_id', $worker->id)
            ->where('scheme_id', $scheme->id)
            ->whereNotIn('status', ['rejected'])
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Already applied for this scheme'], 422);
        }

        $application = SchemeApplication::create([
            'application_number' => 'APP' . strtoupper(Str::random(8)),
            'worker_id'          => $worker->id,
            'scheme_id'          => $scheme->id,
            'status'             => 'pending',
            'applied_at'         => now(),
            'submitted_data'     => $request->input('data', []),
        ]);

        return response()->json(['success' => true, 'message' => 'Application submitted', 'data' => $application], 201);
    }

    // GET /api/schemes/my-applications
    public function myApplications(Request $request)
    {
        $worker = $request->user()->worker;

        if (!$worker) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $applications = $worker->schemeApplications()
            ->with('scheme')
            ->latest('applied_at')
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $applications]);
    }
}
