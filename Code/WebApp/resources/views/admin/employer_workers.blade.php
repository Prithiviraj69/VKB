@extends('layouts.admin')

@section('title', 'Manage Beneficiaries')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Manage Beneficiaries</h2>
            <p class="text-gray-500 text-xs mt-1">View, verify via Ration Card, register, and edit welfare program beneficiaries.</p>
        </div>
        <div>
            <a href="{{ route('employer.index') }}" 
               class="bg-gray-200 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-gray-300 transition flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-900 text-sm space-y-1">
            <div class="font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
                <span>Please fix the following errors:</span>
            </div>
            <ul class="list-disc pl-5 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $activeTab = 'add';
        if (request('tab') === 'view' || request()->has('search') || request()->has('page')) {
            $activeTab = 'view';
        }
    @endphp

    <!-- Tab Selectors -->
    <div class="flex border-b border-gray-200 text-xs font-bold text-gray-400 overflow-x-auto pb-1 gap-2">
        <button type="button" onclick="switchBenTab('add')" id="ben-tab-add" class="px-4 py-2 rounded-lg @if($activeTab === 'add') bg-indigo-50 text-indigo-700 font-extrabold @endif transition">
            <i class="fa-solid fa-user-plus mr-1.5"></i>Add Beneficiary (Ration Card Verification)
        </button>
        <button type="button" onclick="switchBenTab('view')" id="ben-tab-view" class="px-4 py-2 rounded-lg @if($activeTab === 'view') bg-indigo-50 text-indigo-700 font-extrabold @endif transition">
            <i class="fa-solid fa-address-book mr-1.5"></i>View Registry
        </button>
    </div>

    <!-- TAB 1: View Beneficiary Registry -->
    <div id="ben-content-view" class="ben-panel bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between @if($activeTab !== 'view') hidden @endif">
        <!-- Search bar -->
        <div class="p-6 bg-gray-50/50 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <form action="{{ route('employer.beneficiaries') }}" method="GET" class="w-full sm:w-80">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, mobile, profile..." 
                           class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Full Name</th>
                        <th class="px-6 py-4">Father Name</th>
                        <th class="px-6 py-4">Contact</th>
                        <th class="px-6 py-4">Job Profile</th>
                        <th class="px-6 py-4">Ration Card</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Account Status</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                    @forelse($workers as $worker)
                        @php
                            $w = (array) $worker;
                            $wId = $w['_id'] ?? $w['id'] ?? '';
                            $userId = $w['user_id'] ?? '';
                            $userModel = $userId ? \App\Models\User::find($userId) : null;
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 text-xs font-bold text-indigo-700 hover:text-indigo-900 hover:underline cursor-pointer" onclick="openViewBenModal({{ json_encode($w) }})">
                                {{ $w['full_name'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                {{ $w['father_name'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-gray-600">
                                {{ $w['mobile'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 text-[11px] font-bold">
                                    {{ $w['job_profile'] ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-gray-600">
                                {{ $w['ration_card_number'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $status = $w['status'] ?? 'pending';
                                @endphp
                                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider
                                    @if($status === 'approved' || $status === 'verified') bg-emerald-50 text-emerald-700 border border-emerald-200
                                    @elseif($status === 'rejected') bg-rose-50 text-rose-700 border border-rose-200
                                    @else bg-amber-50 text-amber-700 border border-amber-200
                                    @endif">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($userModel)
                                    <form action="{{ route('employer.toggle-beneficiary-status', $wId) }}" method="POST">
                                        @csrf
                                        <button type="submit" 
                                                class="px-2.5 py-0.5 rounded-full text-xs font-bold transition duration-150 uppercase tracking-wider
                                                {{ $userModel->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200 hover:bg-rose-200' }}">
                                            {{ $userModel->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-400 text-xs">No Login Account</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center flex items-center justify-center gap-1">
                                <button type="button" 
                                        onclick="openEditBenModal('{{ $wId }}', '{{ addslashes($w['full_name'] ?? '') }}', '{{ addslashes($w['father_name'] ?? '') }}', '{{ addslashes($w['mobile'] ?? '') }}', '{{ addslashes($w['job_profile'] ?? '') }}')"
                                        class="text-amber-500 hover:text-amber-600 transition p-1.5" title="Edit Profile">
                                    <i class="fa-solid fa-pen-to-square text-base"></i>
                                </button>
                                <form action="{{ route('employer.delete-beneficiary', $wId) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this beneficiary registration? This checks for active transactions.')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-600 transition p-1.5" title="Delete Registration">
                                        <i class="fa-solid fa-trash-can text-base"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-400">No beneficiaries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-6 border-t border-gray-100 bg-gray-50/30">
            {{ $workers->links() }}
        </div>
    </div>

    <!-- TAB 2: Add Beneficiary via Ration Card Verification -->
    <div id="ben-content-add" class="ben-panel grid grid-cols-1 lg:grid-cols-3 gap-6 @if($activeTab !== 'add') hidden @endif">
        <!-- Verification upload card -->
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div>
                <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-file-shield text-indigo-650 text-indigo-700"></i>
                    <span>Ration Card Verification</span>
                </h3>
                <p class="text-xs text-gray-400 mt-1">Upload PDS Ration Card PDF (Aadhaar number or details are NOT collected or stored under DPDP act compliance).</p>
            </div>

            <form action="{{ route('employer.verify-ration-card') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="ration_card_pdf" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Select PDS Ration Card (PDF) *</label>
                    <input type="file" name="ration_card_pdf" id="ration_card_pdf" accept="application/pdf" required 
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>
                <button type="submit" class="w-full py-2 bg-indigo-700 hover:bg-indigo-600 text-white font-bold rounded-xl text-xs transition shadow-md">
                    Verify & Parse PDF
                </button>
            </form>

            @if(session()->has('ration_card_number'))
                <div class="pt-4 border-t border-gray-150">
                    <div class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100 text-xs text-indigo-900 font-semibold space-y-2">
                        <div class="flex justify-between items-center">
                            <span>Card Number:</span>
                            <span class="font-bold text-indigo-800">{{ session('ration_card_number') }}</span>
                        </div>
                        <div class="flex justify-between items-start gap-2">
                            <span>Address:</span>
                            <span class="font-bold text-indigo-800 text-right">{{ session('ration_card_address') ?: 'N/A' }}</span>
                        </div>
                        <div class="pt-1 flex justify-between items-center text-[10px] text-gray-500">
                            <span>Status:</span>
                            <span class="text-emerald-700 font-black uppercase flex items-center gap-1">
                                <i class="fa-solid fa-circle-check text-xs"></i>Verified State
                            </span>
                        </div>
                        <a href="{{ route('employer.beneficiaries', ['tab' => 'add', 'clear_ration' => 1]) }}" 
                           class="block text-center mt-2 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 transition rounded-lg text-[10px] font-extrabold uppercase">
                            <i class="fa-solid fa-rotate-left mr-1"></i>Reset Verification
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Registration form card -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div>
                <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-check text-indigo-700"></i>
                    <span>Beneficiary Registry Details</span>
                </h3>
                <p class="text-xs text-gray-400 mt-1">Select verified family member and assign appropriate trade profile.</p>
            </div>

            <form action="{{ route('employer.create-beneficiary') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="ration_card_number" value="{{ session('ration_card_number') }}" />

                @if(session()->has('ration_card_family'))
                    <div>
                        <label for="family_selector" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Select Family Member *</label>
                        <select id="family_selector" required onchange="selectFamilyMember(this)" 
                                class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition font-bold text-gray-800">
                            <option value="">-- Choose Family Member --</option>
                            @foreach(session('ration_card_family') as $index => $member)
                                <option value="{{ $index }}" data-name="{{ $member['name'] }}" data-gender="{{ $member['gender'] }}" data-age="{{ $member['age'] }}" data-relation="{{ $member['relation'] }}">
                                    {{ $member['name'] }} ({{ ucwords($member['relation']) }} - Age: {{ $member['age'] }}, {{ ucwords($member['gender']) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="p-4 bg-gray-50 rounded-xl border border-dashed border-gray-200 text-center text-xs text-gray-400 font-semibold">
                        <i class="fa-solid fa-file-invoice text-lg mb-1.5 block text-gray-300"></i>
                        Please upload and verify a Ration Card PDF to populate registration options.
                    </div>
                @endif

                <!-- Read-only populated details -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="form_full_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Full Name</label>
                        <input type="text" name="full_name" id="form_full_name" readonly required 
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 bg-gray-50 text-xs text-gray-500 font-bold focus:outline-none" />
                    </div>
                    <div>
                        <label for="form_gender" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Gender</label>
                        <input type="text" name="gender_label" id="form_gender_label" readonly required 
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 bg-gray-50 text-xs text-gray-500 font-bold focus:outline-none" />
                        <input type="hidden" name="gender" id="form_gender" />
                    </div>
                    <div>
                        <label for="form_age" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Age</label>
                        <input type="number" name="age" id="form_age" readonly required 
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 bg-gray-50 text-xs text-gray-500 font-bold focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="form_relation" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Relation with Head</label>
                        <input type="text" name="relation" id="form_relation" readonly required 
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 bg-gray-50 text-xs text-gray-500 font-bold focus:outline-none" />
                    </div>
                    <div>
                        <label for="form_father_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Father's Name *</label>
                        <input type="text" name="father_name" id="form_father_name" required placeholder="Enter father's name" 
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="form_mobile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Contact Mobile Number *</label>
                        <input type="text" name="mobile" id="form_mobile" required placeholder="Enter 10-digit mobile number" maxlength="10" 
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                    </div>
                    <div>
                        <label for="form_job_profile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Job Trade Profile *</label>
                        <select name="job_profile" id="form_job_profile" required 
                                class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition font-bold text-gray-800">
                            <option value="">-- Choose Job Trade --</option>
                            @foreach($activeTrades as $trade)
                                <option value="{{ $trade->name }}">{{ $trade->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button type="submit" @if(!session()->has('ration_card_number')) disabled @endif
                        class="w-full py-3 bg-indigo-700 hover:bg-indigo-600 disabled:bg-gray-200 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs transition shadow-md">
                    Complete Registration & Onboard Worker
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal 1: View Profile Details -->
<div id="viewBenModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 bg-indigo-700 text-white flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full border border-white/20 overflow-hidden flex items-center justify-center bg-white/10 shrink-0">
                    <img id="view_ben_photo" src="" alt="Photo" class="w-full h-full object-cover hidden" onerror="this.src='https://placehold.co/100x100?text=Profile';">
                    <i id="view_ben_avatar_icon" class="fa-solid fa-user text-lg text-indigo-200"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold" id="view_modal_title">Beneficiary Profile Details</h3>
                    <p class="text-[10px] text-indigo-100 mt-0.5" id="view_modal_subtitle"></p>
                </div>
            </div>
            <button type="button" onclick="closeViewBenModal()" class="text-white hover:text-indigo-100 transition text-lg p-1">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto space-y-6 text-xs text-gray-700">
            <!-- Basic Details Accordion/List -->
            <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-150">
                <div><span class="text-[9px] font-black text-gray-400 uppercase block">Full Name</span><p id="view_full_name" class="font-bold text-gray-900 mt-0.5"></p></div>
                <div><span class="text-[9px] font-black text-gray-400 uppercase block">Father's Name</span><p id="view_father_name" class="font-bold text-gray-900 mt-0.5"></p></div>
                <div><span class="text-[9px] font-black text-gray-400 uppercase block">Mobile Contact</span><p id="view_mobile" class="font-bold text-gray-900 mt-0.5"></p></div>
                <div><span class="text-[9px] font-black text-gray-400 uppercase block">Assigned Trade</span><p id="view_job_profile" class="font-bold text-gray-900 mt-0.5"></p></div>
                <div><span class="text-[9px] font-black text-gray-400 uppercase block">Relation in Family</span><p id="view_relation" class="font-bold text-gray-900 mt-0.5"></p></div>
                <div><span class="text-[9px] font-black text-gray-400 uppercase block">Ration Card Number</span><p id="view_ration_card_number" class="font-bold text-gray-900 mt-0.5"></p></div>
            </div>

            <!-- Address block -->
            <div class="space-y-1.5">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-wider block">PDS Address (Retrieved from Ration Card)</span>
                <p id="view_address" class="p-3 bg-gray-50 rounded-lg border border-gray-150 font-bold text-gray-800"></p>
            </div>

            <!-- Family List Table -->
            <div class="space-y-2">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-wider block">Ration Card Family Tree / Members</span>
                <div class="border border-gray-100 rounded-xl overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-100 text-[10px] font-black text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-2">Member Name</th>
                                <th class="px-4 py-2">Relation</th>
                                <th class="px-4 py-2 text-center">Gender</th>
                                <th class="px-4 py-2 text-center">Age</th>
                            </tr>
                        </thead>
                        <tbody id="view_family_members_list" class="divide-y divide-gray-100 font-semibold text-gray-600 bg-white">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-100 flex justify-end">
            <button type="button" onclick="closeViewBenModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-lg transition">
                Close Profile
            </button>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Details Profile -->
<div id="editBenModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col">
        <div class="px-6 py-4 bg-indigo-755 bg-indigo-700 text-white flex justify-between items-center">
            <div>
                <h3 class="text-base font-bold">Edit Beneficiary Profile</h3>
                <p class="text-[10px] text-indigo-100 mt-0.5">Modify artisan registry details.</p>
            </div>
            <button type="button" onclick="closeEditBenModal()" class="text-white hover:text-indigo-100 transition text-lg p-1">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editBenForm" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            <div>
                <label for="edit_full_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Full Name *</label>
                <input type="text" name="full_name" id="edit_full_name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
            </div>

            <div>
                <label for="edit_father_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Father's Name *</label>
                <input type="text" name="father_name" id="edit_father_name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
            </div>

            <div>
                <label for="edit_mobile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Mobile Contact *</label>
                <input type="text" name="mobile" id="edit_mobile" required maxlength="10" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
            </div>

            <div>
                <label for="edit_job_profile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Job Trade Profile *</label>
                <select name="job_profile" id="edit_job_profile" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition font-bold text-gray-800">
                    @foreach($activeTrades as $trade)
                        <option value="{{ $trade->name }}">{{ $trade->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditBenModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl text-xs transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-700 hover:bg-indigo-600 text-white font-bold rounded-xl text-xs transition shadow-md">
                    Update Details
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const BASE_URL = "{{ asset('') }}";
    function switchBenTab(tabName) {
        document.querySelectorAll('.ben-panel').forEach(p => p.classList.add('hidden'));
        document.getElementById('ben-content-' + tabName).classList.remove('hidden');

        // Update classes
        const tabAdd = document.getElementById('ben-tab-add');
        const tabView = document.getElementById('ben-tab-view');

        if (tabName === 'add') {
            tabAdd.classList.add('bg-indigo-50', 'text-indigo-700', 'font-extrabold');
            tabView.classList.remove('bg-indigo-50', 'text-indigo-700', 'font-extrabold');
        } else {
            tabView.classList.add('bg-indigo-50', 'text-indigo-700', 'font-extrabold');
            tabAdd.classList.remove('bg-indigo-50', 'text-indigo-700', 'font-extrabold');
        }
    }

    function selectFamilyMember(selector) {
        const option = selector.options[selector.selectedIndex];
        if (!option.value) {
            document.getElementById('form_full_name').value = '';
            document.getElementById('form_gender_label').value = '';
            document.getElementById('form_gender').value = '';
            document.getElementById('form_age').value = '';
            document.getElementById('form_relation').value = '';
            return;
        }

        document.getElementById('form_full_name').value = option.getAttribute('data-name') || '';
        const gender = option.getAttribute('data-gender') || 'male';
        document.getElementById('form_gender').value = gender;
        document.getElementById('form_gender_label').value = gender.toUpperCase();
        document.getElementById('form_age').value = option.getAttribute('data-age') || '';
        document.getElementById('form_relation').value = option.getAttribute('data-relation') || '';
    }

    function openViewBenModal(w) {
        document.getElementById('view_modal_title').innerText = w.full_name || 'Artisan Profile';
        document.getElementById('view_modal_subtitle').innerText = 'Registration No / Reference ID: ' + (w.worker_code || 'N/A');

        const photoImg = document.getElementById('view_ben_photo');
        const avatarIcon = document.getElementById('view_ben_avatar_icon');
        if (w.photo_url) {
            const cleanPath = w.photo_url.startsWith('/') ? w.photo_url.substring(1) : w.photo_url;
            photoImg.src = BASE_URL + cleanPath;
            photoImg.classList.remove('hidden');
            avatarIcon.classList.add('hidden');
        } else {
            photoImg.src = '';
            photoImg.classList.add('hidden');
            avatarIcon.classList.remove('hidden');
        }

        document.getElementById('view_full_name').innerText = w.full_name || 'N/A';
        document.getElementById('view_father_name').innerText = w.father_name || 'N/A';
        document.getElementById('view_mobile').innerText = w.mobile || 'N/A';
        document.getElementById('view_job_profile').innerText = w.job_profile || 'N/A';
        document.getElementById('view_relation').innerText = w.relation || 'Member';
        document.getElementById('view_ration_card_number').innerText = w.ration_card_number || 'N/A';
        document.getElementById('view_address').innerText = w.aadhaar_address || 'Address not registered.';

        const membersTable = document.getElementById('view_family_members_list');
        membersTable.innerHTML = '';

        let membersList = [];
        if (typeof w.family_members === 'string') {
            try { membersList = JSON.parse(w.family_members); } catch (e) { membersList = []; }
        } else if (Array.isArray(w.family_members)) {
            membersList = w.family_members;
        }

        if (membersList.length > 0) {
            membersList.forEach(m => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50 transition';
                tr.innerHTML = `
                    <td class="px-4 py-2.5 font-bold text-gray-800">${m.name || 'N/A'}</td>
                    <td class="px-4 py-2.5">${m.relation || 'Member'}</td>
                    <td class="px-4 py-2.5 text-center uppercase text-[10px] font-black">${m.gender || 'male'}</td>
                    <td class="px-4 py-2.5 text-center font-mono">${m.age || '0'} yrs</td>
                `;
                membersTable.appendChild(tr);
            });
        } else {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td colspan="4" class="text-center py-4 text-gray-400">No family members data registered on this card.</td>`;
            membersTable.appendChild(tr);
        }

        document.getElementById('viewBenModal').classList.remove('hidden');
    }

    function closeViewBenModal() {
        document.getElementById('viewBenModal').classList.add('hidden');
    }

    function openEditBenModal(id, fullName, fatherName, mobile, jobProfile) {
        const form = document.getElementById('editBenForm');
        form.action = `/employer/beneficiary/${id}/update`;

        document.getElementById('edit_full_name').value = fullName;
        document.getElementById('edit_father_name').value = fatherName;
        document.getElementById('edit_mobile').value = mobile;
        document.getElementById('edit_job_profile').value = jobProfile;

        document.getElementById('editBenModal').classList.remove('hidden');
    }

    function closeEditBenModal() {
        document.getElementById('editBenModal').classList.add('hidden');
    }
</script>
@endsection
