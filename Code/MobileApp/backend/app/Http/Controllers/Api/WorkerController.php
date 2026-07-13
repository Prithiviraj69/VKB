<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WorkerController extends Controller
{
    // GET /api/worker/check-ration-card?number=XXXX
    // Check if ration card number already exists in the system
    public function checkRationCard(Request $request)
    {
        $request->validate(['number' => 'required|string']);
        $number = trim($request->input('number'));

        $exists = Worker::where('ration_card_number', $number)->exists();

        return response()->json([
            'success' => true,
            'exists'  => $exists,
            'message' => $exists
                ? 'This Ration Card Number is already registered in the system. Please contact the helpdesk if this is your card.'
                : 'Ration card number is available.',
        ]);
    }

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
        $worker = $user->worker()->with(['documents', 'familyMembers'])->first();

        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Profile not found', 'has_profile' => false], 404);
        }

        // ── Audit trail enrichment ──────────────────────────────────────────
        $audit = [
            'registered_at' => $worker->created_at ? $worker->created_at->toIso8601String() : null,
        ];

        // Verifier info
        if ($worker->verified_by) {
            $verifier = \App\Models\User::find($worker->verified_by);
            $audit['verified_at']        = $worker->verified_at ? $worker->verified_at->toIso8601String() : null;
            $audit['verified_by_name']   = $verifier ? $verifier->name : null;
            $audit['verified_by_dept']   = $worker->verified_by_dept;
            // Fetch dept name
            if ($worker->verified_by_dept) {
                $dept = \Illuminate\Support\Facades\DB::table('departments')
                    ->where('department_code', $worker->verified_by_dept)
                    ->first();
                $audit['verified_by_dept_name'] = $dept
                    ? ($dept->name ?? $dept->department_name ?? $worker->verified_by_dept)
                    : $worker->verified_by_dept;
            } else {
                $audit['verified_by_dept_name'] = null;
            }
        } else {
            $audit['verified_at']           = null;
            $audit['verified_by_name']      = null;
            $audit['verified_by_dept']      = null;
            $audit['verified_by_dept_name'] = null;
        }

        // Approver info
        if ($worker->approved_by) {
            $approver = \App\Models\User::find($worker->approved_by);
            $audit['approved_at']        = $worker->approved_at ? $worker->approved_at->toIso8601String() : null;
            $audit['approved_by_name']   = $approver ? $approver->name : null;
            $audit['approved_by_dept']   = $worker->approved_by_dept;
            if ($worker->approved_by_dept) {
                $dept = \Illuminate\Support\Facades\DB::table('departments')
                    ->where('department_code', $worker->approved_by_dept)
                    ->first();
                $audit['approved_by_dept_name'] = $dept
                    ? ($dept->name ?? $dept->department_name ?? $worker->approved_by_dept)
                    : $worker->approved_by_dept;
            } else {
                $audit['approved_by_dept_name'] = null;
            }
        } else {
            $audit['approved_at']           = null;
            $audit['approved_by_name']      = null;
            $audit['approved_by_dept']      = null;
            $audit['approved_by_dept_name'] = null;
        }
        // ───────────────────────────────────────────────────────────────────

        return response()->json([
            'success'     => true,
            'data'        => $worker,
            'audit'       => $audit,
            'has_profile' => true,
        ]);
    }


    // Calculate profile completeness score in PHP (replaces dropped MySQL trigger)
    private function calculateProfileScore(Worker $worker): int
    {
        $score = 0;
        // Core personal details (50 points total)
        if ($worker->full_name)      $score += 10;
        if ($worker->dob)            $score += 10;
        if ($worker->gender)         $score += 5;
        if ($worker->father_name)    $score += 5;
        if ($worker->aadhaar_address) $score += 5;
        if ($worker->local_address)  $score += 5;
        if ($worker->mobile_alt)     $score += 5;
        if ($worker->photo_url)      $score += 5;
        if ($worker->marital_status)  $score += 5;

        // Education / Category (5 points)
        if ($worker->education || $worker->category) $score += 5;

        // Employment (10 points)
        if ($worker->employment_status === 'Self-Employed') {
            if ($worker->business_name) $score += 10;
        } else if ($worker->employment_status === 'Employed') {
            if ($worker->job_profile)    $score += 10;
        } else {
            if ($worker->employment_status) $score += 10;
        }

        // Bank (15 points)
        if ($worker->bank_account_number && $worker->bank_ifsc) $score += 15;

        // Family members (5 points)
        if ($worker->familyMembers()->count() > 0) $score += 5;

        // Signature (10 points)
        if ($worker->signature_url)  $score += 10;

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
            'education_status'    => 'nullable|string',
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
            'employment_status'   => 'nullable|string|in:Employed,Self-Employed,Not Employed,Other',
            'business_name'       => 'nullable|string',
            'business_address'    => 'nullable|string',
            'business_category'   => 'nullable|string',
            'is_bocw_registered'  => 'nullable|boolean',
            'bocw_reg_no'         => 'nullable|string|max:100',
            'bocw_state_board'    => 'nullable|string|max:200',
            'bocw_reg_date'       => 'nullable|date',
            'bocw_valid_upto'     => 'nullable|date',
        ]);

        // ── Duplicate Ration Card check ──
        if (!empty($validated['ration_card_number'])) {
            $rationNo = trim($validated['ration_card_number']);
            if (Worker::where('ration_card_number', $rationNo)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Ration Card Number (' . $rationNo . ') is already registered in the system. Each ration card can only be used once.',
                ], 422);
            }
        }

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
            'education_status'    => 'nullable|string',
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
            'employment_status'   => 'nullable|string|in:Employed,Self-Employed,Not Employed,Other',
            'business_name'       => 'nullable|string',
            'business_address'    => 'nullable|string',
            'business_category'   => 'nullable|string',
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

    // POST /api/worker/parse-ration-pdf
    // Upload a ration card PDF and extract family member data via text parsing
    public function parseRationPdf(Request $request)
    {
        $request->validate([
            'ration_pdf'         => 'required|file|mimes:pdf|max:10240',
            'ration_card_number' => 'nullable|string|max:30',
        ]);

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($request->file('ration_pdf')->getRealPath());
            $text   = $pdf->getText();

            // Extract ration card number from PDF text
            $pdfRationNumber = $this->extractRationCardNumber($text);

            // Validate ration card number if provided
            $enteredNumber = trim($request->input('ration_card_number', ''));
            if (!empty($enteredNumber) && !empty($pdfRationNumber)) {
                $normalizedEntered = preg_replace('/[\s\-\/]+/', '', strtoupper($enteredNumber));
                $normalizedPdf     = preg_replace('/[\s\-\/]+/', '', strtoupper($pdfRationNumber));
                if ($normalizedEntered !== $normalizedPdf) {
                    return response()->json([
                        'success'            => false,
                        'mismatch'           => true,
                        'message'            => 'Ration card number mismatch. You entered: "' . $enteredNumber . '", found in PDF: "' . $pdfRationNumber . '"',
                        'entered_number'     => $enteredNumber,
                        'pdf_ration_number'  => $pdfRationNumber,
                    ], 422);
                }
            }

            $members = $this->parseFamilyFromRationText($text);
            $address = $this->extractAddress($text);
            $mobile  = $this->extractMobile($text);

            return response()->json([
                'success'           => true,
                'members'           => $members,
                'ration_number'     => $pdfRationNumber,
                'address'           => $address,
                'mobile'            => $mobile,
                'raw_text_preview'  => substr($text, 0, 300), // debug help
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse PDF: ' . $e->getMessage(),
            ], 422);
        }
    }

    // Extract ration card number from PDF text
    private function extractRationCardNumber(string $text): ?string
    {
        // STEP 1: Try to find number that appears immediately after "Ration Card Number" label
        // This handles the format where label is on one line and number is on the very next line
        // e.g.:  "Ration Card Number\n695325478123"
        if (preg_match(
            '/ration\s*card\s*(?:no\.?|number|num\.?)\s*[:\-]?\s*[\r\n\s]+(\d{6,15})/i',
            $text, $m
        )) {
            return trim($m[1]);
        }

        // STEP 2: Try to find number on same line after label
        // e.g.: "Ration Card No: 695325478123"
        if (preg_match(
            '/ration\s*card\s*(?:no\.?|number|num\.?)\s*[:\-]\s*([A-Z0-9\-\/ ]{6,25})/i',
            $text, $m
        )) {
            $candidate = trim($m[1]);
            // Make sure we did not accidentally grab "Signature" or similar text
            if (preg_match('/\d{6,15}/', $candidate)) {
                return $candidate;
            }
        }

        // STEP 3: Remove lines that are clearly NOT ration card number lines
        // (Fare Price Shop No, phone, Aadhaar, etc.) and then search for a labeled number
        $cleanedLines = [];
        foreach (preg_split('/[\r\n]+/', $text) as $line) {
            // Skip lines that have these known non-ration-card patterns
            if (preg_match('/(?:fare\s*price|shop\s*no|fps|retailer|dealer|aadhaar|pan\s*no|mobile|phone|tel|fax)/i', $line)) {
                continue;
            }
            $cleanedLines[] = $line;
        }
        $cleanedText = implode("\n", $cleanedLines);

        // STEP 4: Try state-specific format like "GJ-22-123-4567890"
        if (preg_match('/\b([A-Z]{2}[\-\/\s]?\d{2}[\-\/\s]?\d{3}[\-\/\s]?\d{7,10})\b/', $cleanedText, $m)) {
            return trim($m[1]);
        }

        // STEP 5: RC No format
        if (preg_match('/(?:RC\s*(?:no\.?|number|num\.?)\s*[:\-]?\s*)([A-Z0-9\-\/\s]{6,25})/i', $cleanedText, $m)) {
            return trim($m[1]);
        }

        // STEP 6: Look for prominent standalone 10-14 digit number in cleaned text
        // avoiding known noise
        if (preg_match_all('/\b(\d{10,14})\b/', $cleanedText, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $matchInfo) {
                $candidate = trim($matchInfo[0]);
                $offset = $matchInfo[1];

                // Skip 12-digit pure numbers that appear near Aadhaar/phone context
                $start = max(0, $offset - 80);
                $context = strtolower(substr($cleanedText, $start, 80));
                if (preg_match('/(?:aadhaar|aadhar|mobile|phone|pan\s*no)/i', $context)) {
                    continue;
                }

                return $candidate;
            }
        }

        return null;
    }

    // Parse family members from extracted ration card PDF text
    private function parseFamilyFromRationText(string $text): array
    {
        $members = [];

        $relationMap = [
            'head' => 'Head', 'self' => 'Head',
            'wife' => 'Spouse', 'husband' => 'Spouse', 'spouse' => 'Spouse',
            'son' => 'Son', 'daughter' => 'Daughter',
            'father' => 'Father', 'mother' => 'Mother',
            'brother' => 'Brother', 'sister' => 'Sister',
            'grandfather' => 'Grandfather', 'grandmother' => 'Grandmother',
            'nephew' => 'Nephew', 'niece' => 'Niece',
            'grand daughter' => 'Granddaughter', 'granddaughter' => 'Granddaughter',
            'grand son' => 'Grandson', 'grandson' => 'Grandson',
            'son in law' => 'Son in Law', 'son-in-law' => 'Son in Law',
            'daughter in law' => 'Daughter in Law', 'daughter-in-law' => 'Daughter in Law',
        ];

        $genderMap = [
            'm' => 'male', 'male' => 'male',
            'f' => 'female', 'female' => 'female',
            'o' => 'other', 'other' => 'other',
        ];

        $lines = preg_split('/\r\n|\r|\n/', $text);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strlen($line) < 5) continue;

            // Format 1: "1 Ramesh Kumar 35 M Head" or "1. Ramesh Kumar 35 Male Son"
            if (preg_match(
                '/^\d+\.?\s+([A-Za-z][A-Za-z\s]{2,40}?)\s+(\d{1,3})\s+(M(?:ale)?|F(?:emale)?|O(?:ther)?)\s+(Head|Self|Wife|Husband|Son|Daughter|Father|Mother|Brother|Sister|Spouse|Grandfather|Grandmother|Nephew|Niece)/i',
                $line, $m
            )) {
                $name     = trim($m[1]);
                $age      = (int) $m[2];
                $gender   = $genderMap[strtolower(substr($m[3], 0, 1))] ?? 'male';
                $relation = $relationMap[strtolower($m[4])] ?? ucfirst(strtolower($m[4]));

                if ($age > 0 && $age < 120 && !empty($name)) {
                    $members[] = compact('name', 'relation', 'gender', 'age');
                }
            }
            // Format 2: Tab/pipe separated "Ramesh Kumar | 35 | Male | Son"
            elseif (preg_match(
                '/([A-Za-z][A-Za-z\s]{2,40}?)\s*[|\t]\s*(\d{1,3})\s*[|\t]\s*(Male|Female|M|F)\s*[|\t]\s*(Head|Self|Wife|Husband|Son|Daughter|Father|Mother|Brother|Sister|Spouse)/i',
                $line, $m
            )) {
                $name     = trim($m[1]);
                $age      = (int) $m[2];
                $gender   = $genderMap[strtolower(substr($m[3], 0, 1))] ?? 'male';
                $relation = $relationMap[strtolower($m[4])] ?? ucfirst(strtolower($m[4]));

                if ($age > 0 && $age < 120 && !empty($name)) {
                    $members[] = compact('name', 'relation', 'gender', 'age');
                }
            }
            // Format 3: Slash or space separated, with optional Aadhaar at the end (robust spacing version)
            // e.g. "1Parate Pushpaben Manikrao 67 / F SELF XXXX-XXXX-6733" or "5Deepak Dattatraya Deshpande53 / M SON IN LAW XXXX-XXXX-2165"
            elseif (preg_match(
                '/^(\d+)?\.?\s*([A-Za-z][A-Za-z\s\.\'-]*[A-Za-z])\s*(\d{1,3})\s*(?:\/)?\s*([MFO](?:ale|emale|ther)?)\b\s+([A-Za-z\s\-]{2,30}?)(?:\s+([Xx\d\-]+))?$/i',
                $line, $m
            )) {
                $name     = trim($m[2]);
                $age      = (int) $m[3];
                $gender   = $genderMap[strtolower(substr($m[4], 0, 1))] ?? 'male';
                $relation = $relationMap[strtolower(trim($m[5]))] ?? ucfirst(strtolower(trim($m[5])));

                if ($age > 0 && $age < 120 && !empty($name)) {
                    $members[] = compact('name', 'relation', 'gender', 'age');
                }
            }
        }

        return $members;
    }

    // GET /api/worker/lookup-ifsc/{ifsc}
    public function lookupIfsc($ifsc)
    {
        $ifsc = strtoupper(trim($ifsc));
        if (strlen($ifsc) !== 11) {
            return response()->json([
                'success' => false,
                'message' => 'IFSC Code 11 characters ka hona chahiye'
            ], 400);
        }

        $apiKey = env('IFSC_API_KEY');
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'IFSC API Key config file (.env) mein set nahi hai'
            ], 500);
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey,
                'Accept'    => 'application/json',
            ])->get("https://ifsc.in/api/v1/lookup/{$ifsc}");

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['status']) && !empty($data['data'])) {
                    return response()->json([
                        'success' => true,
                        'data'    => $data['data']
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'IFSC code valid nahi hai ya details nahi mili'
            ], 404);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'IFSC lookup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function extractAddress(string $text): ?string
    {
        if (preg_match('/(?:address|add)\s*:\s*([^\n\r]+)/i', $text, $m)) {
            $addr = trim($m[1]);
            $addr = preg_replace('/(?:mobile|mob|phone|tel|email)\s*:.+$/i', '', $addr);
            return trim($addr);
        }
        return null;
    }

    private function extractMobile(string $text): ?string
    {
        if (preg_match('/(?:mobile|mob|phone|contact)(?:\s*number)?\s*[:\-]?\s*([6-9]\d{9})\b/i', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}
