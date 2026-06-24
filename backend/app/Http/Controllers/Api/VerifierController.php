<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifierController extends Controller
{
    // GET /api/verifier/pending-workers
    public function getPendingWorkers(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'verifier') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $search = $request->query('search');

        $query = DB::table('workers')
            ->where(function($q) {
                $q->where('status', 'pending')
                  ->orWhereNull('status')
                  ->orWhere('status', '')
                  ->orWhere('is_verified', false)
                  ->orWhereNull('is_verified');
            })
            ->where('status', '!=', 'verified')
            ->where('status', '!=', 'approved');

        if ($search) {
            $query->where('full_name', 'like', '%' . $search . '%');
        }

        $query->orderBy('created_at', 'desc');
        $workers = $query->paginate(15);

        // Transform results for uniform output
        $workers->getCollection()->transform(function($worker) {
            return $this->formatWorker($worker);
        });

        return response()->json([
            'success' => true,
            'data' => $workers
        ]);
    }

    // POST /api/verifier/worker/{id}/verify
    public function verifyWorker(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'verifier') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $worker = DB::table('workers')->where('_id', $id)->first();
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker record not found'], 404);
        }

        DB::table('workers')->where('_id', $id)->update([
            'status' => 'verified',
            'is_verified' => true,
            'verified_by' => $user->id,
            'verified_at' => now()->toDateTimeString(),
            'verifier_remarks' => $request->input('remarks', 'Profile details verified successfully.'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Worker profile verified successfully'
        ]);
    }

    // POST /api/verifier/worker/{id}/reject
    public function rejectWorker(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'verifier') {
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
            'verified_by' => $user->id,
            'verified_at' => now()->toDateTimeString(),
            'verifier_remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Worker profile rejected/marked for correction'
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
            'status' => $worker['status'] ?? 'pending',
            'address' => $worker['aadhaar_address'] ?? $worker['local_address'] ?? 'N/A',
            'bank_account_number' => $worker['bank_account_number'] ?? 'N/A',
            'bank_name_branch' => $worker['bank_name_branch'] ?? 'N/A',
            'bank_ifsc' => $worker['bank_ifsc'] ?? 'N/A',
            'photo_url' => $worker['photo_url'] ?? '',
            'signature_url' => $worker['signature_url'] ?? '',
            'aadhaar_number' => $worker['aadhaar_number'] ?? 'N/A',
            'father_name' => $worker['father_name'] ?? 'N/A',
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
