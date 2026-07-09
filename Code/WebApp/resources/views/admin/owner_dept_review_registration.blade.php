@extends('layouts.admin')

@section('title', 'Review Worker Registration')

@section('content')
<div class="space-y-6">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Review & Edit Worker Profile</h2>
            <p class="text-gray-500 text-xs mt-1">Review the self-registered worker profile in edit mode. Correct any typos before making an approval decision.</p>
        </div>
        <a href="{{ route('govt-dept-admin.index') }}" class="text-sm font-bold text-indigo-755 hover:text-indigo-600 transition flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Dashboard</span>
        </a>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-900 text-xs space-y-1">
            <div class="font-bold flex items-center gap-2 text-sm text-rose-800">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
                <span>Please fix the validation errors:</span>
            </div>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Form Wrapper -->
    <form id="review-decision-form" action="{{ route('owner-dept.approve-registration', $worker['_id'] ?? $worker['id']) }}" method="POST">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <!-- Col 1 & 2: Editable Information Fields -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-8 space-y-8">
                <!-- Profile Photo -->
                <div class="flex items-center gap-6">
                    <div class="w-24 h-24 rounded-full bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center shadow-inner">
                        @if(!empty($worker['photo_url']))
                            <img src="{{ asset($worker['photo_url']) }}" alt="Worker Photo" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=Profile';" />
                        @else
                            <i class="fa-solid fa-user text-4xl text-gray-400"></i>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-800">{{ $worker['full_name'] ?? 'N/A' }}</h3>
                        <p class="text-xs text-amber-600 font-extrabold mt-0.5 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 w-fit">Pending Review</p>
                    </div>
                </div>

                <!-- Personal Info Details (Editable) -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                        <i class="fa-solid fa-address-card text-indigo-650"></i>
                        <h4 class="font-black text-xs text-indigo-650 uppercase tracking-wider">Personal & Identity Details</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="full_name" class="block text-[10px] font-black text-gray-650 uppercase mb-1">Full Name *</label>
                            <input type="text" name="full_name" id="full_name" value="{{ old('full_name', $worker['full_name'] ?? '') }}" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="father_name" class="block text-[10px] font-black text-gray-655 uppercase mb-1">Father's Name *</label>
                            <input type="text" name="father_name" id="father_name" value="{{ old('father_name', $worker['father_name'] ?? '') }}" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="aadhaar_number" class="block text-[10px] font-black text-gray-655 uppercase mb-1">Aadhaar Number *</label>
                            <input type="text" name="aadhaar_number" id="aadhaar_number" value="{{ old('aadhaar_number', $worker['aadhaar_number'] ?? '') }}" required minlength="12" maxlength="12" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="dob" class="block text-[10px] font-black text-gray-655 uppercase mb-1">Date of Birth *</label>
                            <input type="date" name="dob" id="dob" value="{{ old('dob', isset($worker['dob']) ? explode(' ', is_string($worker['dob']) ? $worker['dob'] : (isset($worker['dob']->date) ? $worker['dob']->date : ''))[0] : '') }}" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="gender" class="block text-[10px] font-black text-gray-655 uppercase mb-1">Gender *</label>
                            <select name="gender" id="gender" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                <option value="male" @if(old('gender', $worker['gender'] ?? '') === 'male') selected @endif>Male</option>
                                <option value="female" @if(old('gender', $worker['gender'] ?? '') === 'female') selected @endif>Female</option>
                                <option value="other" @if(old('gender', $worker['gender'] ?? '') === 'other') selected @endif>Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="marital_status" class="block text-[10px] font-black text-gray-655 uppercase mb-1">Marital Status *</label>
                            <select name="marital_status" id="marital_status" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                <option value="unmarried" @if(old('marital_status', $worker['marital_status'] ?? '') === 'unmarried') selected @endif>Unmarried</option>
                                <option value="married" @if(old('marital_status', $worker['marital_status'] ?? '') === 'married') selected @endif>Married</option>
                            </select>
                        </div>
                        <div>
                            <label for="job_profile" class="block text-[10px] font-black text-gray-655 uppercase mb-1">Job Profile / Trade *</label>
                            <input type="text" name="job_profile" id="job_profile" value="{{ old('job_profile', $worker['job_profile'] ?? '') }}" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div class="col-span-1 md:col-span-2">
                            <label for="aadhaar_address" class="block text-[10px] font-black text-gray-655 uppercase mb-1">Aadhaar Address *</label>
                            <textarea name="aadhaar_address" id="aadhaar_address" required rows="2" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">{{ old('aadhaar_address', $worker['aadhaar_address'] ?? $worker['local_address'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Bank Info Details (Editable) -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                        <i class="fa-solid fa-building-columns text-indigo-650"></i>
                        <h4 class="font-black text-xs text-indigo-650 uppercase tracking-wider">Bank Account Details</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="bank_account_number" class="block text-[10px] font-black text-gray-655 uppercase mb-1">Account Number *</label>
                            <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $worker['bank_account_number'] ?? '') }}" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="bank_ifsc" class="block text-[10px] font-black text-gray-655 uppercase mb-1">IFSC Code *</label>
                            <input type="text" name="bank_ifsc" id="bank_ifsc" value="{{ old('bank_ifsc', $worker['bank_ifsc'] ?? '') }}" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div class="col-span-1 md:col-span-2">
                            <label for="bank_name_branch" class="block text-[10px] font-black text-gray-655 uppercase mb-1">Bank Name & Branch *</label>
                            <input type="text" name="bank_name_branch" id="bank_name_branch" value="{{ old('bank_name_branch', $worker['bank_name_branch'] ?? '') }}" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                    </div>
                </div>

                <!-- Signature Attachment -->
                @if(!empty($worker['signature_url']))
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                            <i class="fa-solid fa-signature text-indigo-650"></i>
                            <h4 class="font-black text-xs text-indigo-650 uppercase tracking-wider">Signature Attachment</h4>
                        </div>
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl inline-block max-w-sm">
                            <img src="{{ asset($worker['signature_url']) }}" alt="Worker Signature" class="max-h-24 object-contain" onerror="this.onerror=null; this.innerHTML='<span class=\'text-xs text-red-500\'>Failed to load signature</span>';" />
                        </div>
                    </div>
                @endif
            </div>

            <!-- Col 3: Remarks and Decision Actions -->
            <div class="space-y-6">
                <!-- Returned Alert Banner -->
                @if(isset($worker['status']) && $worker['status'] === 'rejected')
                    <div class="p-5 bg-rose-50 border border-rose-200 rounded-2xl space-y-3 shadow-sm">
                        <div class="flex items-center gap-2 text-rose-800 font-bold text-sm">
                            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                            <span>Returned Application Alert</span>
                        </div>
                        <div class="text-xs font-semibold text-rose-700 divide-y divide-rose-100 space-y-2">
                            @if(!empty($worker['verifier_remarks']) && $worker['verifier_remarks'] !== 'N/A')
                                <p class="pt-2"><strong>Verifier Remarks:</strong> {{ $worker['verifier_remarks'] }}</p>
                            @endif
                            @if(!empty($worker['approver_remarks']) && $worker['approver_remarks'] !== 'N/A')
                                <p class="pt-2"><strong>Approver Remarks:</strong> {{ $worker['approver_remarks'] }}</p>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Decision Panel -->
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                    <div>
                        <h3 class="text-md font-bold text-gray-800">Registration Decision</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Approve or reject this profile. Edits to profile attributes will be saved upon clicking Approve.</p>
                    </div>

                    <div class="space-y-4">
                        <label for="remarks" class="block text-[10px] font-black text-gray-600 uppercase">Decision Remarks / Notes</label>
                        <textarea name="remarks" id="remarks" rows="4" placeholder="Optional notes for approval, required remarks for rejection..."
                                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">{{ old('remarks') }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Reject Button -->
                        <button type="submit" onclick="return submitAs('reject', event)"
                                class="w-full py-3 bg-white text-rose-600 font-bold rounded-xl text-xs border border-rose-200 hover:bg-rose-50 transition shadow-sm">
                            Reject profile
                        </button>

                        <!-- Approve Button -->
                        <button type="submit" onclick="return submitAs('approve', event)"
                                class="w-full py-3 bg-indigo-750 text-white font-bold rounded-xl text-xs hover:bg-indigo-700 transition shadow-sm">
                            Approve Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function submitAs(actionType, event) {
        const form = document.getElementById('review-decision-form');
        const remarksValue = document.getElementById('remarks').value.trim();
        
        if (actionType === 'reject') {
            if (remarksValue.length < 3) {
                alert('Please enter remarks explaining the rejection (minimum 3 characters).');
                event.preventDefault();
                return false;
            }
            form.action = "{{ route('owner-dept.reject-registration', $worker['_id'] ?? $worker['id']) }}";
            // Disable validation for rejection
            form.setAttribute('novalidate', 'novalidate');
        } else {
            form.action = "{{ route('owner-dept.approve-registration', $worker['_id'] ?? $worker['id']) }}";
            form.removeAttribute('novalidate');
        }
        
        return true;
    }
</script>
@endsection
