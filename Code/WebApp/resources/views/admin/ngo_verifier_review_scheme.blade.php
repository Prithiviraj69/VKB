@extends('layouts.admin')

@section('title', 'NGO Verifier - Review Scheme Application')

@section('content')
<div class="space-y-8 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-gray-200 pb-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800">Review Scheme Application</h2>
            <p class="text-xs text-gray-500 mt-1">Application ID: <span class="font-mono font-bold">{{ $application['id'] ?? $application['_id'] ?? 'N/A' }}</span></p>
        </div>
        <a href="{{ route('ngo-verifier.scheme-applications', ['status' => 'pending']) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Main Review Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Details Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- 1. Scheme Details -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-2">
                    <i class="fa-solid fa-shapes text-ngo"></i>
                    <span>Scheme Information</span>
                </h3>
                <div class="space-y-3">
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Scheme Name</div>
                        <div class="text-base font-bold text-purple-750">{{ $scheme['title'] ?? $scheme['name'] ?? $application['scheme_title'] ?? 'N/A' }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-[9px] uppercase font-black text-gray-400">Ministry / Board</div>
                            <div class="text-xs font-bold text-gray-700">{{ $scheme['ministry'] ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-[9px] uppercase font-black text-gray-400">Eligibility Level</div>
                            <div class="text-xs font-bold text-gray-700">{{ $scheme['level'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Description</div>
                        <p class="text-xs text-gray-600 leading-relaxed">{{ $scheme['description'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- 2. Applicant Details -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-2">
                    <i class="fa-solid fa-user text-ngo"></i>
                    <span>Applicant Information</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Applicant Full Name</div>
                        <div class="font-bold text-gray-800">{{ $worker['full_name'] ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Father's Name</div>
                        <div class="font-bold text-gray-800">{{ $worker['father_name'] ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Date of Birth (DOB)</div>
                        <div class="font-bold text-gray-800">
                            {{ isset($worker['dob']) ? explode(' ', is_string($worker['dob']) ? $worker['dob'] : (isset($worker['dob']->date) ? $worker['dob']->date : 'N/A'))[0] : 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Gender</div>
                        <div class="font-bold text-gray-800">{{ $worker['gender'] ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Primary Mobile</div>
                        <div class="font-bold text-gray-850 font-mono">{{ $worker['mobile_alt'] ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Social Category</div>
                        <div class="font-bold text-gray-800">{{ $worker['category'] ?? 'N/A' }}</div>
                    </div>
                    <div class="col-span-2 border-t border-gray-100 pt-3">
                        <div class="text-[9px] uppercase font-black text-gray-400 mb-1">Aadhaar Address</div>
                        <div class="text-gray-700 bg-gray-50 p-2.5 rounded-xl border border-gray-200/50 leading-relaxed">
                            {{ $worker['aadhaar_address'] ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Bank & Financial Info -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-2">
                    <i class="fa-solid fa-credit-card text-ngo"></i>
                    <span>Bank & Financial Details</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Bank Name & Branch</div>
                        <div class="font-bold text-gray-800">{{ $worker['bank_name_branch'] ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Account Number</div>
                        <div class="font-bold text-gray-800 font-mono">{{ $worker['bank_account_number'] ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">IFSC Code</div>
                        <div class="font-bold text-gray-800 font-mono text-purple-700">{{ $worker['bank_ifsc'] ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Actions Column -->
        <div class="space-y-6">
            <!-- Application Answers / Data -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-2">
                    <i class="fa-solid fa-clipboard-question text-ngo"></i>
                    <span>Submitted Data</span>
                </h3>
                <div class="space-y-3 text-xs">
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Annual Family Income</div>
                        <div class="font-bold text-gray-800 text-sm">Rs. {{ isset($application['submitted_data']['annual_income']) ? number_format((float)$application['submitted_data']['annual_income'], 2) : 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase font-black text-gray-400">Remarks / Purpose</div>
                        <div class="text-gray-700 bg-purple-50/20 p-2.5 rounded-xl border border-purple-100 leading-relaxed">
                            {{ $application['submitted_data']['remarks'] ?? 'No remarks provided.' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Box -->
            <div class="bg-white rounded-2xl p-6 border border-purple-100 shadow-lg space-y-4 bg-gradient-to-b from-white to-purple-50/10">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-2">
                    <i class="fa-solid fa-shield-halved text-ngo"></i>
                    <span>Verification Actions</span>
                </h3>
                
                <form id="action-form" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Verifier Remarks / Feedback *</label>
                        <textarea name="remarks" id="remarks" rows="4" required placeholder="Enter verification notes or rejection reasons..."
                                  class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500 transition"></textarea>
                    </div>

                    <div class="space-y-2 pt-2">
                        <button type="submit" onclick="submitAction('verify')"
                                class="w-full py-3 bg-ngo text-white font-bold rounded-xl text-xs shadow-md hover:bg-purple-700 hover:shadow-lg transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-circle-check"></i> Verify & Forward
                        </button>
                        <button type="submit" onclick="submitAction('reject')"
                                class="w-full py-3 bg-red-50 text-red-700 font-bold border border-red-200 rounded-xl text-xs hover:bg-red-100 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-circle-xmark"></i> Reject / Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function submitAction(type) {
        const form = document.getElementById('action-form');
        const remarksField = document.getElementById('remarks');
        
        if (type === 'verify') {
            form.action = "{{ route('ngo-verifier.verify', $application['id'] ?? $application['_id'] ?? '') }}";
            remarksField.required = false; // Optional for approval
        } else {
            form.action = "{{ route('ngo-verifier.reject', $application['id'] ?? $application['_id'] ?? '') }}";
            remarksField.required = true;  // Required for rejection
        }
    }
</script>
@endsection
