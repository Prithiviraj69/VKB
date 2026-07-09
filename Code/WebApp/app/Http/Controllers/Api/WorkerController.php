<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkerController extends Controller
{
    // POST /api/worker/verify-ration
    // Simulates PDS lookup: returns mock family data for a given ration card number
    public function verifyRation(Request $request)
    {
        $request->validate(['ration_card_number' => 'required|string']);

        $rationNumber = $request->input('ration_card_number');

        // Simulate PDS lookup (in production, integrate with state PDS API)
        $mockFamilyData = [
            ['name' => 'Sunita Devi',   'relation' => 'Spouse',   'gender' => 'female', 'age' => 34],
            ['name' => 'Aman Kumar',    'relation' => 'Son',      'gender' => 'male',   'age' => 12],
            ['name' => 'Priya Kumari', 'relation' => 'Daughter', 'gender' => 'female', 'age' => 9],
        ];

        return response()->json([
            'success'        => true,
            'message'        => 'PDS Data fetched successfully',
            'ration_number'  => $rationNumber,
            'family_members' => $mockFamilyData,
        ]);
    }


    // GET /api/worker/profile
    public function profile(Request $request)
    {
        $user   = $request->user();
        // Only eager-load relations whose models actually exist
        $worker = $user->worker()->with(['documents', 'familyMembers'])->first();

        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Profile not found', 'has_profile' => false], 404);
        }

        return response()->json(['success' => true, 'data' => $worker, 'has_profile' => true]);
    }

    // Calculate profile completeness score in PHP (replaces dropped MySQL trigger)
    private function calculateProfileScore(Worker $worker): int
    {
        $score = 0;
        if ($worker->full_name)      $score += 10;
        if ($worker->dob)            $score += 10;
        if ($worker->gender)         $score += 5;
        if ($worker->category)       $score += 5;
        if ($worker->aadhaar_number) $score += 10;
        if ($worker->photo_url)      $score += 5;
        if ($worker->mobile_alt)     $score += 5;
        if ($worker->email)          $score += 5;
        if ($worker->father_name)    $score += 5;
        if ($worker->local_address)  $score += 5;
        if ($worker->job_profile)    $score += 5;
        if ($worker->bank_account_number && $worker->bank_ifsc) $score += 15;
        if ($worker->ration_card_number) $score += 5;
        if ($worker->familyMembers()->count() > 0) $score += 5;
        if ($worker->signature_url)  $score += 5;
        return min($score, 100);
    }

    // POST /api/worker/profile
    public function createProfile(Request $request)
    {
        $user = $request->user();

        if ($user->worker()->exists()) {
            return response()->json(['success' => false, 'message' => 'Profile already exists'], 422);
        }

        $validated = $request->validate([
            'full_name'           => 'required|string|max:255',
            'dob'                 => 'required|date',
            'gender'              => 'required|string',
            'category'            => 'nullable|string',
            'aadhaar_number'      => 'nullable|string|max:20',
            'pan_number'          => 'nullable|string|max:20',
            'mobile_alt'          => 'nullable|string|max:15',
            'email'               => 'nullable|string|max:255',
            'father_name'         => 'nullable|string|max:255',
            'education'           => 'nullable|string',
            'marital_status'      => 'nullable|string',

            // New fields
            'aadhaar_address'     => 'nullable|string',
            'ration_card_number'  => 'nullable|string|max:20',
            'local_address'       => 'nullable|string',
            'identification_mark' => 'nullable|string',
            'job_profile'         => 'nullable|string',
            'employer_name'       => 'nullable|string',
            'employment_site'     => 'nullable|string',
            'bank_account_number' => 'nullable|string|max:25',
            'bank_name_branch'    => 'nullable|string',
            'bank_ifsc'           => 'nullable|string|max:15',
            'photo_url'           => 'nullable|string',
            'employment_status'   => 'nullable|string|in:Employed,Not Employed',
            'is_bocw_registered'  => 'nullable|boolean',
            'bocw_reg_no'         => 'nullable|string|max:100',
            'bocw_state_board'    => 'nullable|string|max:200',
            'bocw_reg_date'       => 'nullable|date',
            'bocw_valid_upto'     => 'nullable|date',
        ]);

        $validated['user_id']     = $user->id;
        $validated['worker_code'] = 'WK' . strtoupper(uniqid());
        $validated['profile_score'] = 40;

        if (!empty($validated['gender'])) {
            $validated['gender'] = ucfirst(strtolower($validated['gender']));
        }

        // Mock system generated subscription data
        $validated['registration_no'] = 'REG-' . date('Y') . '-' . rand(100000, 999999);
        $validated['date_first_subscription'] = now()->format('Y-m-d');
        $validated['date_last_subscription'] = now()->format('Y-m-d');
        $validated['installments_paid_count'] = 1;
        $validated['installments_paid_total'] = 100;
        $validated['is_membership_revived'] = false;

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $tempId = \Illuminate\Support\Str::uuid()->toString();
            $path   = $request->file('photo')->store('photos/' . $tempId, 'public');
            $validated['photo_url'] = Storage::url($path);
        }

        // Handle signature upload BEFORE creating the worker
        if ($request->hasFile('signature')) {
            $tempId = \Illuminate\Support\Str::uuid()->toString();
            $path   = $request->file('signature')->store('signatures/' . $tempId, 'public');
            $validated['signature_url'] = Storage::url($path);
        }

        $worker = Worker::create($validated);

        // Save family members
        $familyData = $request->input('family');
        if (is_string($familyData)) {
            $familyData = json_decode($familyData, true);
        }
        if (is_array($familyData)) {
            foreach ($familyData as $member) {
                $worker->familyMembers()->create([
                    'name'     => $member['name'] ?? '',
                    'relation' => $member['relation'] ?? '',
                    'gender'   => $member['gender'] ?? '',
                    'age'      => $member['age'] ?? null,
                    'dob'      => $member['dob'] ?? null,
                ]);
            }

            // Sync to workers collection family_members array attribute
            $otherFamily = array_map(fn($f) => [
                'name' => $f['name'] ?? '',
                'relation' => $f['relation'] ?? 'Member',
                'gender' => $f['gender'] ?? 'male',
                'age' => isset($f['age']) ? (int)$f['age'] : null,
            ], $familyData);
            $worker->update(['family_members' => $otherFamily]);
        }

        // Calculate and persist profile score (PHP-side, replaces dropped trigger)
        $worker->load('familyMembers');
        $worker->update(['profile_score' => $this->calculateProfileScore($worker)]);

        $worker = $worker->fresh(['documents', 'familyMembers']);

        return response()->json(['success' => true, 'message' => 'Profile created', 'data' => $worker], 201);
    }

    // PUT /api/worker/profile
    public function updateProfile(Request $request)
    {
        $worker = $request->user()->worker;

        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        $validated = $request->validate([
            'full_name'           => 'sometimes|string|max:255',
            'dob'                 => 'sometimes|date',
            'gender'              => 'sometimes|string',
            'category'            => 'nullable|string',
            'mobile_alt'          => 'nullable|string|max:15',
            'email'               => 'nullable|string|max:255',
            'father_name'         => 'nullable|string|max:255',
            'education'           => 'nullable|string',
            'marital_status'      => 'nullable|string',
            'religion'            => 'nullable|string',

            // New fields
            'aadhaar_address'     => 'nullable|string',
            'ration_card_number'  => 'nullable|string|max:20',
            'local_address'       => 'nullable|string',
            'identification_mark' => 'nullable|string',
            'job_profile'         => 'nullable|string',
            'employer_name'       => 'nullable|string',
            'employment_site'     => 'nullable|string',
            'bank_account_number' => 'nullable|string|max:25',
            'bank_name_branch'    => 'nullable|string',
            'bank_ifsc'           => 'nullable|string|max:15',
            'photo_url'           => 'nullable|string',
            'employment_status'   => 'nullable|string|in:Employed,Not Employed',
            'is_bocw_registered'  => 'nullable|boolean',
            'bocw_reg_no'         => 'nullable|string|max:100',
            'bocw_state_board'    => 'nullable|string|max:200',
            'bocw_reg_date'       => 'nullable|date',
            'bocw_valid_upto'     => 'nullable|date',
        ]);

        if (!empty($validated['gender'])) {
            $validated['gender'] = ucfirst(strtolower($validated['gender']));
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('photos/' . $worker->id, 'public');
            $validated['photo_url'] = Storage::url($path);
        }

        // Handle signature upload BEFORE the update call
        if ($request->hasFile('signature')) {
            $path = $request->file('signature')->store('signatures/' . $worker->id, 'public');
            $validated['signature_url'] = Storage::url($path);
        }

        $worker->update($validated);

        // Update family members
        if ($request->has('family')) {
            $familyData = $request->input('family');
            if (is_string($familyData)) {
                $familyData = json_decode($familyData, true);
            }
            if (is_array($familyData)) {
                $worker->familyMembers()->delete();
                foreach ($familyData as $member) {
                    $worker->familyMembers()->create([
                        'name'     => $member['name'] ?? '',
                        'relation' => $member['relation'] ?? '',
                        'gender'   => $member['gender'] ?? '',
                        'age'      => $member['age'] ?? null,
                        'dob'      => $member['dob'] ?? null,
                    ]);
                }

                // Sync to workers collection family_members array attribute
                $otherFamily = array_map(fn($f) => [
                    'name' => $f['name'] ?? '',
                    'relation' => $f['relation'] ?? 'Member',
                    'gender' => $f['gender'] ?? 'male',
                    'age' => isset($f['age']) ? (int)$f['age'] : null,
                ], $familyData);
                $worker->update(['family_members' => $otherFamily]);
            }
        }

        // Recalculate and persist profile score (PHP-side, replaces dropped trigger)
        $worker->load('familyMembers');
        $worker->update(['profile_score' => $this->calculateProfileScore($worker)]);

        $worker = $worker->fresh(['documents', 'familyMembers']);

        return response()->json(['success' => true, 'message' => 'Profile updated', 'data' => $worker]);
    }

    // GET /api/worker/dashboard
    public function dashboard(Request $request)
    {
        $user   = $request->user();
        $worker = $user->worker;

        return response()->json([
            'success' => true,
            'data'    => [
                'user'              => $user,
                'worker'            => $worker,
                'has_profile'       => (bool) $worker,
                'profile_score'     => $worker?->profile_score ?? 0,
                'pending_schemes'   => $worker?->schemeApplications()->where('status', 'pending')->count() ?? 0,
                'open_grievances'   => $worker?->grievances()->where('status', 'open')->count() ?? 0,
                'total_documents'   => $worker?->documents()->count() ?? 0,
            ],
        ]);
    }
}
