@extends('layouts.admin')

@php
    $title = 'Pending Clearances';
    $desc = 'Review verifier remarks, double check credentials, and issue official registrations.';
    if (isset($status)) {
        if ($status === 'approved') {
            $title = 'Approved Beneficiaries';
            $desc = 'List of all workers currently approved and active in the system.';
        } elseif ($status === 'deactivated') {
            $title = 'Deactivated Beneficiaries';
            $desc = 'List of worker profiles that have been suspended or deactivated.';
        }
    }
@endphp

@section('title', 'Approver - ' . $title)

@section('content')
<div class="space-y-6">
    <!-- Celebrate Registration Modal (If approved successfully) -->
    @if(session('success_reg'))
        <div class="mb-6 p-6 bg-blue-50 border-2 border-dashed border-blue-300 rounded-2xl flex flex-col md:flex-row items-center gap-6 shadow-md animate-bounce">
            <div class="w-14 h-14 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center shrink-0 border border-blue-200">
                <i class="fa-solid fa-stamp text-2xl"></i>
            </div>
            <div class="flex-1 text-center md:text-left">
                <h3 class="font-black text-lg text-blue-900">Final Approval Granted!</h3>
                <p class="text-sm font-semibold text-blue-700 mt-0.5">{{ session('success_reg') }}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="px-5 py-2.5 bg-blue-700 text-white font-bold text-xs rounded-xl hover:bg-blue-600 transition shadow animate-none">
                Dismiss
            </button>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">{{ $title }}</h2>
            <p class="text-gray-500 text-xs mt-1">{{ $desc }}</p>
        </div>
        <div>
            <a href="{{ route('approver.index') }}" class="bg-gray-200 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-gray-300 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 items-start">
        
        <!-- Column 1: Onboard Beneficiary Form -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-6 bg-gradient-to-b from-white to-gray-50/20">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-blue-700"></i>
                    <span>Register Worker</span>
                </h3>
                <p class="text-xs text-gray-400 mt-1">Directly onboard a worker and auto-approve/issue registration number.</p>
            </div>

            <form action="{{ route('approver.create-beneficiary') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Section: Personal Details -->
                <div class="space-y-3">
                    <h4 class="text-[10px] font-black text-blue-700 uppercase tracking-wider border-b pb-1">1. Personal Info</h4>
                    <div>
                        <label for="full_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Full Name *</label>
                        <input type="text" name="full_name" id="full_name" required placeholder="e.g. Ramesh Kumar"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="dob" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Date of Birth *</label>
                            <input type="date" name="dob" id="dob" required
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                        </div>
                        <div>
                            <label for="gender" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Gender *</label>
                            <select name="gender" id="gender" required
                                    class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="marital_status" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Marital Status *</label>
                            <select name="marital_status" id="marital_status" required
                                    class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                <option value="married">Married</option>
                                <option value="unmarried">Unmarried</option>
                            </select>
                        </div>
                        <div>
                            <label for="father_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Father's Name *</label>
                            <input type="text" name="father_name" id="father_name" required placeholder="Father's name"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                        </div>
                    </div>
                </div>

                <!-- Section: Contact & Identity -->
                <div class="space-y-3 pt-2">
                    <h4 class="text-[10px] font-black text-blue-700 uppercase tracking-wider border-b pb-1">2. Identity & Contact</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="mobile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Mobile *</label>
                            <input type="text" name="mobile" id="mobile" required placeholder="10-digit mobile"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                        </div>
                        <div>
                            <label for="aadhaar_number" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Aadhaar Number *</label>
                            <input type="text" name="aadhaar_number" id="aadhaar_number" required placeholder="12-digit Aadhaar"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                        </div>
                    </div>
                    <div>
                        <label for="aadhaar_address" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Aadhaar Address *</label>
                        <textarea name="aadhaar_address" id="aadhaar_address" required rows="2" placeholder="Full address..."
                                  class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition"></textarea>
                    </div>
                    <div>
                        <label for="job_profile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Job Trade / Profile *</label>
                        <input type="text" name="job_profile" id="job_profile" required placeholder="e.g. Carpenter"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                    </div>
                </div>

                <!-- Section: Bank Account Details -->
                <div class="space-y-3 pt-2">
                    <h4 class="text-[10px] font-black text-blue-700 uppercase tracking-wider border-b pb-1">3. Bank Account</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="bank_account_number" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Account No *</label>
                            <input type="text" name="bank_account_number" id="bank_account_number" required placeholder="Account number"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                        </div>
                        <div>
                            <label for="bank_ifsc" class="block text-[10px] font-black text-gray-600 uppercase mb-1">IFSC Code *</label>
                            <input type="text" name="bank_ifsc" id="bank_ifsc" required placeholder="SBIN0001234"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                        </div>
                    </div>
                    <div>
                        <label for="bank_name_branch" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Bank Name & Branch *</label>
                        <input type="text" name="bank_name_branch" id="bank_name_branch" required placeholder="SBI Main Branch"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                    </div>
                </div>

                <!-- Section: Login credentials -->
                <div class="space-y-3 pt-2">
                    <h4 class="text-[10px] font-black text-blue-700 uppercase tracking-wider border-b pb-1">4. Login Credentials</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Login Email *</label>
                            <input type="email" name="email" id="email" required placeholder="worker@example.com"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                        </div>
                        <div>
                            <label for="password" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Password *</label>
                            <input type="password" name="password" id="password" required placeholder="Min 6 chars" minlength="6"
                                   class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                        </div>
                    </div>
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-blue-700 hover:bg-blue-600 text-white font-bold rounded-xl text-sm shadow-md hover:shadow-lg transition">
                    Register & Approve Worker
                </button>
            </form>
        </div>

        <!-- Column 2 & 3: List Beneficiaries -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between">
            <!-- Search bar -->
            <div class="p-6 bg-gray-50/50 border-b border-gray-100">
                <form action="{{ route('approver.beneficiaries') }}" method="GET" class="flex gap-4">
                    <input type="hidden" name="status" value="{{ $status ?? 'pending' }}" />
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search workers by name..." 
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-approver focus:border-approver transition" />
                    </div>
                    <button type="submit" class="bg-blue-700 text-white font-bold text-xs px-6 py-2.5 rounded-xl hover:bg-blue-600 transition duration-150">
                        Search
                    </button>
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Worker details</th>
                            <th class="px-6 py-4">Trade</th>
                            <th class="px-6 py-4">Verification</th>
                            <th class="px-6 py-4 text-center">Account Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($workers as $worker)
                            @php
                                $worker = (array) $worker;
                                $wId = $worker['_id'] ?? $worker['id'] ?? '';
                                $userId = $worker['user_id'] ?? '';
                                $userModel = $userId ? \App\Models\User::find($userId) : null;
                                $statusVal = $worker['status'] ?? 'pending';
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-gray-800">{{ $worker['full_name'] ?? 'N/A' }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">Father: {{ $worker['father_name'] ?? 'N/A' }}</div>
                                    @if(isset($worker['dob']))
                                        <div class="text-[10px] text-gray-400 mt-0.5">DOB: {{ explode(' ', is_string($worker['dob']) ? $worker['dob'] : (isset($worker['dob']->date) ? $worker['dob']->date : 'N/A'))[0] }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded w-fit">{{ $worker['job_profile'] ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider
                                        @if($statusVal === 'approved' || $statusVal === 'verified') bg-green-50 text-green-700 border border-green-200
                                        @elseif($statusVal === 'rejected') bg-red-50 text-red-700 border border-red-200
                                        @else bg-amber-50 text-amber-700 border border-amber-200
                                        @endif">
                                        {{ $statusVal }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($userModel)
                                        <form action="{{ route('approver.toggle-beneficiary-status', $wId) }}" method="POST">
                                            @csrf
                                            <button type="submit" 
                                                    class="px-2.5 py-0.5 rounded-full text-xs font-bold transition duration-150 uppercase tracking-wider
                                                    {{ $userModel->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200' : 'bg-red-100 text-red-800 border border-red-200 hover:bg-red-200' }}">
                                                {{ $userModel->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400 text-xs">No Login Account</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if(($status ?? 'pending') === 'pending')
                                            <a href="{{ route('approver.review', $wId) }}" 
                                               class="bg-blue-700 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg hover:bg-blue-600 transition shadow-sm">
                                                Review
                                            </a>
                                        @endif
                                        <form action="{{ route('approver.delete-beneficiary', $wId) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this worker registration? This checks for active transactions.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-500 hover:text-rose-600 transition p-1">
                                                <i class="fa-solid fa-trash-can text-base"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-400">No records found matching this status.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if(count($workers) > 0)
                <div class="p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-xs text-gray-500 font-bold">
                        Showing {{ $workers->firstItem() }} to {{ $workers->lastItem() }} of {{ $workers->total() }} records.
                    </div>
                    <div>
                        {{ $workers->links() }}
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
