<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Worker;
use App\Services\AuditLogger;

class ContractorDashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'contractor') {
            abort(403, 'Forbidden');
        }

        $userId = Auth::id();

        // Calculate dashboard stats
        $workersCount = DB::table('workers')
            ->where('created_by', $userId)
            ->count();

        $stats = [
            'workers' => $workersCount,
        ];

        return view('admin.contractor', compact('stats'));
    }

    public function beneficiaries(Request $request)
    {
        if (Auth::user()->role !== 'contractor') {
            abort(403);
        }

        if ($request->has('clear_ration')) {
            session()->forget(['ration_card_number', 'ration_card_family', 'ration_card_address']);
            return redirect()->route('contractor.beneficiaries');
        }

        $search = $request->query('search', '');
        $userId = Auth::id();

        $query = DB::table('workers')->where('created_by', $userId);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                  ->orWhere('mobile', 'like', '%' . $search . '%')
                  ->orWhere('job_profile', 'like', '%' . $search . '%');
            });
        }

        $workers = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        foreach ($workers->items() as $w) {
            $wId = $w->_id ?? $w->id ?? null;
            $w->family_members = \App\Models\Worker::getFamilyMembers($wId, $w->family_members ?? []);
        }
        $activeTrades = \App\Models\Trade::whereIn('is_active', [true, 1])->orderBy('name', 'asc')->get();

        return view('admin.contractor_workers', compact('workers', 'search', 'activeTrades'));
    }

    public function createBeneficiary(Request $request)
    {
        if (Auth::user()->role !== 'contractor') {
            abort(403);
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'job_profile' => 'required|string',
            'job_profile_other' => 'required_if:job_profile,Other|nullable|string',
            'ration_card_number' => 'required|string',
            'gender' => 'required|string',
            'relation' => 'nullable|string',
            'age' => 'nullable|integer',
            'mobile' => 'required|string|max:20',
            'aadhaar_address' => 'required|string',
        ]);

        $cntProfile = \App\Models\Contractor::where('email', Auth::user()->email)->first();
        $ngoId = $cntProfile ? $cntProfile->ngo_id : null;

        $jobProfile = $request->job_profile;
        if ($jobProfile === 'Other') {
            $jobProfile = $request->job_profile_other ?: 'Other';
        }

        // 1. Create User
        $user = User::create([
            'mobile' => $request->mobile,
            'name' => $request->full_name,
            'address' => $request->aadhaar_address,
            'job_profile' => $jobProfile,
            'trade' => $jobProfile,
            'role' => 'beneficiary',
            'is_active' => true,
            'is_verified' => false,
            'ngo_id' => $ngoId,
            'created_by' => Auth::id(),
            'created_by_ngo_id' => $ngoId,
            'created_by_dept_code' => Auth::user()->department_code ?: Auth::user()->created_by_dept_code,
        ]);

        // Filter other family members (excluding primary beneficiary)
        $selectedName = $request->full_name;
        $allFamily = session('ration_card_family', []);
        $otherFamily = array_values(array_filter($allFamily, function($m) use ($selectedName) {
            return strtolower(trim($m['name'])) !== strtolower(trim($selectedName));
        }));

        // 2. Create Worker profile
        $workerId = DB::table('workers')->insertGetId([
            'user_id' => $user->id,
            'full_name' => $request->full_name,
            'father_name' => '',
            'dob' => '',
            'gender' => $request->gender,
            'marital_status' => '',
            'aadhaar_number' => '', // Removed under DPDP Act
            'aadhaar_address' => $request->aadhaar_address,
            'job_profile' => $jobProfile,
            'bank_account_number' => '',
            'bank_name_branch' => '',
            'bank_ifsc' => '',
            'ration_card_number' => preg_replace('/[\s\-\/]+/', '', $request->ration_card_number),
            'relation' => $request->relation,
            'age' => $request->age ? (int)$request->age : null,
            'family_members' => $otherFamily,
            'mobile' => $request->mobile,
            'is_verified' => false,
            'status' => 'approved',
            'department_code' => Auth::user()->department_code ?: Auth::user()->created_by_dept_code,
            'created_by' => Auth::id(),
            'created_by_ngo_id' => $ngoId,
            'created_by_dept_code' => Auth::user()->department_code ?: Auth::user()->created_by_dept_code,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
            
            // Mobile app synchronization fields
            'worker_code' => 'WK' . strtoupper(uniqid()),
            'profile_score' => 0,
            'date_first_subscription' => null,
            'date_last_subscription' => null,
            'installments_paid_count' => 0,
            'installments_paid_total' => 0,
            'is_membership_revived' => false,
        ]);

        \App\Models\Worker::syncFamilyMembers($workerId, $otherFamily);

        session()->forget(['ration_card_number', 'ration_card_family', 'ration_card_address']);

        AuditLogger::log('CREATE', 'worker', $user->id, $request->full_name, ['mobile' => $request->mobile]);

        return redirect()->route('contractor.beneficiaries')->with('success', 'Beneficiary registered successfully.');
    }

    public function verifyRationCard(Request $request)
    {
        if (Auth::user()->role !== 'contractor') {
            abort(403);
        }

        $request->validate([
            'ration_card_pdf' => 'required|file|mimes:pdf',
        ]);

        try {
            $parser    = new \Smalot\PdfParser\Parser();
            $pdf       = $parser->parseFile($request->file('ration_card_pdf')->getRealPath());
            $pages     = $pdf->getPages();
            $pageTexts = array_map(fn($p) => $p->getText(), $pages);
            $fullText  = implode("\n", $pageTexts);
            $page1Text = $pageTexts[0] ?? $fullText;

            $isValidId = fn($v) => (bool) preg_match('/\d{4,}/', $v);
            $extractedNumber = null;

            if (!$extractedNumber && preg_match('/ration\s*card\s*number\s*[\r\n\s]+(\d{6,20})/i', $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }
            if (!$extractedNumber && preg_match('/ration\s*card\s*number\s*[:\-]\s*([A-Z0-9][\w\-\/]{4,24})/i', $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }
            if (!$extractedNumber && preg_match('/ration\s*card\s*(?:no\.?|num\.?|#)\s*[:\-]?\s*[\r\n\s]*([A-Z0-9][\w\-\/]{4,24})/i', $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }
            if (!$extractedNumber && preg_match('/\bR\.?\s*C\.?\s*(?:no\.?|number)?\s*[:\-]\s*([A-Z0-9][\w\-\/]{4,24})/i', $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }
            if (!$extractedNumber && preg_match('/ration\s*card[^\n]{0,50}\n\s*([A-Z0-9][\w\-\/]{4,24})/i', $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }
            if (!$extractedNumber && preg_match('/(?:card|kard)\s*(?:no\.?|number)\s*[:\-]?\s*[\r\n\s]*([A-Z0-9][\w\-\/]{4,24})/i', $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }
            if (!$extractedNumber) {
                $cleanPage1 = preg_replace('/(fare\s*price\s*shop|shop\s*no|mobile\s*(?:number|no)?|phone|aadhar)[^\n]{0,60}/i', '', $page1Text);
                if (preg_match('/\b(\d{10,14})\b/', $cleanPage1, $m)) {
                    $extractedNumber = $m[1];
                }
            }
            if (!$extractedNumber && preg_match('/\b([A-Z]{2,4}[\s\/\-]?[A-Z0-9]{2,6}[\s\/\-]?\d{4,10})\b/i', $fullText, $m) && $isValidId($m[1])) {
                $extractedNumber = trim($m[1]);
            }
            if (!$extractedNumber && preg_match('/\b(\d{8,9})\b/', $fullText, $m)) {
                $extractedNumber = $m[1];
            }

            if (!$extractedNumber) {
                $snippet = substr(preg_replace('/\s+/', ' ', $fullText), 0, 400);
                return back()->withErrors([
                    'ration_card_pdf' => 'Could not extract Ration Card Number. PDF text preview: "' . $snippet . '"'
                ]);
            }

            $normalizedExtracted = preg_replace('/[\s\-\/]+/', '', $extractedNumber);

            if (DB::table('workers')->where('ration_card_number', $normalizedExtracted)->exists()) {
                return back()->withErrors([
                    'ration_card_pdf' => 'This Ration Card (No: ' . $extractedNumber . ') is already registered.'
                ]);
            }

            $family = [];
            $relKwPat = 'GRAND\s+DAUGHTER|GREAT\s+GRAND(?:\s+SON|\s+DAUGHTER)?|SON\s+IN\s+LAW|'
                      . 'DAUGHTER\s+IN\s+LAW|GRANDSON|GRANDDAUGHTER|'
                      . 'HEAD|SELF|WIFE|HUSBAND|SON|DAUGHTER|FATHER|MOTHER|'
                      . 'BROTHER|SISTER|SPOUSE|GUARDIAN|OTHER|MUKHIYA|BETI|PUTRA';

            $relMap = [
                'self'               => 'Head',
                'head'               => 'Head',
                'mukhiya'            => 'Head',
                'wife'               => 'Spouse',
                'husband'            => 'Spouse',
                'spouse'             => 'Spouse',
                'son'                => 'Son',
                'putra'              => 'Son',
                'grandson'           => 'Grandson',
                'daughter'           => 'Daughter',
                'beti'               => 'Daughter',
                'granddaughter'      => 'Granddaughter',
                'grand daughter'     => 'Grand Daughter',
                'great grand son'    => 'Great Grand Son',
                'great grand daughter' => 'Great Grand Daughter',
                'father'             => 'Father',
                'mother'             => 'Mother',
                'brother'            => 'Brother',
                'sister'             => 'Sister',
                'son in law'         => 'Son-in-law',
                'daughter in law'    => 'Daughter-in-law',
                'guardian'           => 'Guardian',
            ];

            $headerFirstWords = ['sr', 'sl', 'name', 'member', 'age', 'gender',
                                  'relation', 'aadhar', 'aadhaar', 'scheme', 'category',
                                  'signature', 'address', 'mobile', 'head', 'fare', 'ration'];

            $allLines = explode("\n", $fullText);
            $primaryRegex = '/^\s*(?:\d+)?\s*([A-Za-z][A-Za-z\s\.]{1,45}?)\s*(\d{1,3})\s*(?:\/)?\s*(M|F)\b\s+(' . $relKwPat . ')(\s+.*)?$/i';
            $fallbackRegex = '/^\s*(?:\d+)?\s*([A-Za-z][A-Za-z\s\.]{1,45}?)\s*(\d{1,3})\s*(?:\/)?\s*(M|F)\b(?:\s+(.*?))?$/i';

            foreach ($allLines as $rawLine) {
                $line = trim($rawLine);
                if (strlen($line) < 6) continue;

                $m = [];
                $matched = false;

                if (preg_match($primaryRegex, $line, $m)) {
                    $name = trim($m[1]);
                    $age = (int)$m[2];
                    $genderChr = strtolower($m[3]);
                    $relRaw = strtolower(trim(preg_replace('/\s+/', ' ', $m[4])));
                    $relation = $relMap[$relRaw] ?? ucwords($relRaw);
                    $matched = true;
                } elseif (preg_match($fallbackRegex, $line, $m)) {
                    $name = trim($m[1]);
                    $age = (int)$m[2];
                    $genderChr = strtolower($m[3]);
                    $after = isset($m[4]) ? trim($m[4]) : '';
                    $relRaw = strtolower(strtok($after, " \t\r\n"));
                    $relation = $relMap[$relRaw] ?? (empty($relRaw) ? 'Member' : ucwords($relRaw));
                    $matched = true;
                }

                if (!$matched) continue;
                if ($age === 0 || $age > 110) continue;
                if (strlen($name) < 3) continue;
                if (!preg_match('/^[A-Za-z][A-Za-z\s\.]{1,}$/', $name)) continue;

                $firstWord = strtolower(strtok($name, ' '));
                if (in_array($firstWord, $headerFirstWords)) continue;

                $gender = $genderChr === 'f' ? 'female' : 'male';

                $dup = false;
                foreach ($family as $f) {
                    if (strtolower($f['name']) === strtolower($name)) {
                        $dup = true;
                        break;
                    }
                }
                if (!$dup) {
                    $family[] = compact('name', 'age', 'gender', 'relation');
                }
            }

            if (empty($family)) {
                $family[] = ['name' => '', 'age' => 0, 'gender' => 'male', 'relation' => 'Head'];
            }

            $extractedAddress = null;
            $lines = explode("\n", $fullText);
            foreach ($lines as $i => $l) {
                if (preg_match('/^\s*(?:address|residential\s*address|સરનામું|पता)\s*[:\-]?\s*(.+)$/ui', $l, $m)) {
                    $extractedAddress = trim($m[1]);
                    $nextLine = isset($lines[$i+1]) ? trim($lines[$i+1]) : '';
                    if (!empty($nextLine) && !preg_match('/(name|card|no|relation|gender|age|fps|shop|uid|aadhaar|mobile|phone)\s*[:\-]/i', $nextLine)) {
                        $extractedAddress .= ' ' . $nextLine;
                    }
                    break;
                }
            }

            session([
                'ration_card_number' => $normalizedExtracted,
                'ration_card_family' => $family,
                'ration_card_address' => $extractedAddress
            ]);

            $cnt = count(array_filter($family, fn($f) => !empty($f['name'])));
            $msg = $cnt > 0
                ? "Ration Card verified (No: {$extractedNumber}). {$cnt} family member(s) found — select one to register."
                : "Ration Card verified (No: {$extractedNumber}). Family members could not be detected — please fill in manually.";

            return back()->with('success', $msg);

        } catch (\Exception $e) {
            return back()->withErrors(['ration_card_pdf' => 'Error reading PDF: ' . $e->getMessage()]);
        }
    }

    public function updateBeneficiary(Request $request, $id)
    {
        if (Auth::user()->role !== 'contractor') {
            abort(403);
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'mobile' => 'required|string|max:15',
            'job_profile' => 'required|string|max:255',
        ]);

        DB::table('workers')->where('_id', $id)->update([
            'full_name' => $request->full_name,
            'father_name' => $request->father_name,
            'mobile' => $request->mobile,
            'job_profile' => $request->job_profile,
            'updated_at' => now()->toDateTimeString(),
        ]);

        AuditLogger::log('UPDATE', 'worker', $id, $request->full_name, [
            'full_name' => $request->full_name
        ]);

        return back()->with('success', 'Beneficiary details updated successfully.');
    }

    public function deleteBeneficiary($id)
    {
        if (Auth::user()->role !== 'contractor') {
            abort(403);
        }

        $worker = DB::table('workers')->where('_id', $id)->orWhere('id', $id)->first();
        if (!$worker) {
            return redirect()->route('contractor.beneficiaries')->with('error', 'Worker not found.');
        }

        $worker = (array) $worker;
        $workerId = $worker['id'] ?? $worker['_id'] ?? null;
        $userId = $worker['user_id'] ?? null;
        $wName = $worker['full_name'] ?? 'Worker';

        // Check transactions
        $hasSchemes = DB::table('scheme_applications')->where('worker_id', $workerId)->exists();
        $hasGrievances = DB::table('grievances')->where('worker_id', $workerId)->exists();

        if ($hasSchemes || $hasGrievances) {
            return back()->with('error', 'User transaction are recorded, so user cannot be deleted. Please deactive the user instead of deletion.');
        }

        if ($workerId) {
            DB::table('worker_family')->where('worker_id', $workerId)->delete();
            DB::table('worker_documents')->where('worker_id', $workerId)->delete();
            DB::table('worker_skills')->where('worker_id', $workerId)->delete();
        }

        DB::table('workers')->where('_id', $workerId)->orWhere('id', $workerId)->delete();

        if ($userId) {
            User::where('id', $userId)->delete();
        }

        AuditLogger::log('DELETE', 'worker', $userId ?? $id, $wName);

        return redirect()->route('contractor.beneficiaries')->with('success', 'Beneficiary deleted successfully.');
    }

    public function toggleBeneficiaryStatus($id)
    {
        if (Auth::user()->role !== 'contractor') {
            abort(403);
        }

        $worker = DB::table('workers')->where('_id', $id)->orWhere('id', $id)->first();
        if ($worker) {
            $worker = (array) $worker;
            $userId = $worker['user_id'] ?? null;
            $wName = $worker['full_name'] ?? 'Worker';
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->is_active = !$user->is_active;
                    $user->save();

                    AuditLogger::log('TOGGLE_STATUS', 'worker', $userId, $wName, ['new_status' => $user->is_active ? 'active' : 'inactive']);

                    return back()->with('success', 'Beneficiary account status updated successfully.');
                }
            }
        }

        return back()->with('error', 'Associated login account not found.');
    }

    public function downloadBeneficiaryTemplate()
    {
        if (Auth::user()->role !== 'contractor') {
            abort(403);
        }
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="beneficiary_import_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Full Name', 'Mobile', 'Email', 'Password', 'Father Name', 
                'DOB (YYYY-MM-DD)', 'Gender (male/female/other)', 'Marital Status (married/unmarried)', 
                'Aadhaar Number', 'Aadhaar Address', 'Job Profile', 'Bank Account Number', 
                'Bank Name & Branch', 'Bank IFSC'
            ]);
            fputcsv($file, [
                'Ramesh Kumar', '9876543210', 'ramesh@example.com', 'password123', 'Suresh Kumar',
                '1995-08-15', 'male', 'married', '123456789012', '123, Main Street, Silvassa',
                'Carpenter', '10002345678', 'State Bank of India, Silvassa', 'SBIN0001234'
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importBeneficiaries(Request $request)
    {
        if (Auth::user()->role !== 'contractor') {
            abort(403);
        }
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->with('error', 'Unable to open the CSV file.');
        }

        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            return back()->with('error', 'CSV file is empty.');
        }

        $successCount = 0;
        $skippedCount = 0;
        $skips = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $row = array_map('trim', $row);

            if (empty(array_filter($row))) {
                continue;
            }

            if (count($row) < 14) {
                $row = array_pad($row, 14, '');
            }

            $fullName = $row[0];
            $mobile = $row[1];
            $email = $row[2];
            $password = $row[3];
            $fatherName = $row[4];
            $dob = $row[5];
            $gender = strtolower($row[6]);
            $maritalStatus = strtolower($row[7]);
            $aadhaarNumber = $row[8];
            $aadhaarAddress = $row[9];
            $jobProfile = $row[10];
            $bankAccountNumber = $row[11];
            $bankNameBranch = $row[12];
            $bankIfsc = $row[13];

            $errors = [];
            if (empty($fullName)) $errors[] = 'Full Name is required';
            if (empty($mobile)) $errors[] = 'Mobile is required';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid Email is required';
            }
            if (empty($password) || strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            }
            if (empty($fatherName)) $errors[] = 'Father Name is required';
            if (empty($dob) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                $errors[] = 'DOB must be in YYYY-MM-DD format';
            }
            if (!in_array($gender, ['male', 'female', 'other'])) {
                $errors[] = 'Gender must be male, female, or other';
            }
            if (!in_array($maritalStatus, ['married', 'unmarried', 'widowed'])) {
                $errors[] = 'Marital Status must be married, unmarried, or widowed';
            }
            if (empty($aadhaarNumber) || strlen($aadhaarNumber) < 12) {
                $errors[] = 'Aadhaar Number must be at least 12 digits';
            }
            if (empty($aadhaarAddress)) $errors[] = 'Aadhaar Address is required';
            if (empty($jobProfile)) $errors[] = 'Job Profile is required';
            if (empty($bankAccountNumber)) $errors[] = 'Bank Account Number is required';
            if (empty($bankNameBranch)) $errors[] = 'Bank Name & Branch is required';
            if (empty($bankIfsc)) $errors[] = 'Bank IFSC is required';

            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $existingUser = User::where('email', $email)->first();
                if ($existingUser) {
                    $errors[] = 'Email already registered';
                }
            }

            if (!empty($errors)) {
                $skippedCount++;
                $skips[] = [
                    'row' => $rowNumber,
                    'email' => $email ?: 'N/A',
                    'errors' => implode(', ', $errors)
                ];
                continue;
            }

            $user = User::create([
                'mobile' => $mobile,
                'name' => $fullName,
                'address' => $aadhaarAddress,
                'job_profile' => $jobProfile,
                'trade' => $jobProfile,
                'role' => 'beneficiary',
                'is_active' => true,
                'is_verified' => false,
                'created_by' => Auth::id(),
                'created_by_dept_code' => Auth::user()->department_code ?: Auth::user()->created_by_dept_code,
            ]);

            DB::table('workers')->insert([
                'user_id' => $user->id,
                'full_name' => $fullName,
                'father_name' => $fatherName,
                'dob' => $dob,
                'gender' => $gender,
                'marital_status' => $maritalStatus,
                'aadhaar_number' => $aadhaarNumber,
                'aadhaar_address' => $aadhaarAddress,
                'job_profile' => $jobProfile,
                'bank_account_number' => $bankAccountNumber,
                'bank_name_branch' => $bankNameBranch,
                'bank_ifsc' => $bankIfsc,
                'is_verified' => false,
                'status' => 'approved',
                'department_code' => Auth::user()->department_code ?: Auth::user()->created_by_dept_code,
                'created_by' => Auth::id(),
                'created_by_dept_code' => Auth::user()->department_code ?: Auth::user()->created_by_dept_code,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
                
                // Mobile app synchronization fields
                'worker_code' => 'WK' . strtoupper(uniqid()),
                'profile_score' => 0,
                'date_first_subscription' => null,
                'date_last_subscription' => null,
                'installments_paid_count' => 0,
                'installments_paid_total' => 0,
                'is_membership_revived' => false,
            ]);

            $successCount++;
        }

        fclose($handle);

        AuditLogger::log('IMPORT', 'worker', null, 'Bulk CSV Import', [
            'success_count' => $successCount,
            'skipped_count' => $skippedCount
        ]);

        $msg = "Import completed: $successCount worker(s) successfully registered.";
        if ($skippedCount > 0) {
            $msg .= " $skippedCount row(s) were skipped due to errors.";
        }

        return redirect()->route('contractor.beneficiaries')
            ->with('success', $msg)
            ->with('import_skips', $skips);
    }
}
