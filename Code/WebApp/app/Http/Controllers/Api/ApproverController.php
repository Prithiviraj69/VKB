<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApproverController extends Controller
{
    // GET /api/approver/verified-workers
    public function getVerifiedWorkers(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'approver') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $search = $request->query('search');

        $query = DB::table('workers')->where('status', 'verified');

        if ($search) {
            $query->where('full_name', 'like', '%' . $search . '%');
        }

        $query->orderBy('verified_at', 'desc');
        $workers = $query->paginate(15);

        // Transform results
        $workers->getCollection()->transform(function($worker) {
            return $this->formatWorker($worker);
        });

        return response()->json([
            'success' => true,
            'data' => $workers
        ]);
    }

    // POST /api/approver/worker/{id}/approve
    public function approveWorker(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'approver') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker record not found'], 404);
        }

        $regNo = 'REG-' . date('Y') . '-' . rand(100000, 999999);

        DB::table('workers')->where('_id', $id)->update([
            'status' => 'approved',
            'is_verified' => true,
            'registration_no' => $regNo,
            'approved_by' => $user->id,
            'approved_at' => now()->toDateTimeString(),
            'approver_remarks' => $request->input('remarks', 'Profile approved and registration number issued.'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Worker profile approved. Registration number issued: ' . $regNo,
            'registration_no' => $regNo
        ]);
    }

    // POST /api/approver/worker/{id}/reject
    public function rejectWorker(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'approver') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'remarks' => 'required|string|min:3'
        ]);

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker record not found'], 404);
        }

        DB::table('workers')->where('_id', $id)->update([
            'status' => 'rejected',
            'is_verified' => false,
            'approved_by' => $user->id,
            'approved_at' => now()->toDateTimeString(),
            'approver_remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Worker profile rejected/sent back for corrections'
        ]);
    }

    private function formatWorker($worker)
    {
        $worker = (array) $worker;
        return [
            'id' => $worker['id'] ?? $worker['_id'] ?? '',
            'name' => $worker['full_name'] ?? 'N/A',
            'dob' => $this->formatDate($worker['dob'] ?? null),
            'gender' => $worker['gender'] ?? 'N/A',
            'marital_status' => $worker['marital_status'] ?? 'N/A',
            'job_profile' => $worker['job_profile'] ?? 'N/A',
            'status' => $worker['status'] ?? 'verified',
            'address' => $worker['aadhaar_address'] ?? $worker['local_address'] ?? 'N/A',
            'bank_account_number' => $worker['bank_account_number'] ?? 'N/A',
            'bank_name_branch' => $worker['bank_name_branch'] ?? 'N/A',
            'bank_ifsc' => $worker['bank_ifsc'] ?? 'N/A',
            'photo_url' => $worker['photo_url'] ?? '',
            'signature_url' => $worker['signature_url'] ?? '',
            'aadhaar_number' => $worker['aadhaar_number'] ?? 'N/A',
            'father_name' => $worker['father_name'] ?? 'N/A',
            'verifier_remarks' => $worker['verifier_remarks'] ?? 'N/A',
            'verified_at' => $this->formatDate($worker['verified_at'] ?? null),
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
}
