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
            <a href="{{ route('govt-dept-admin.index') }}" 
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
        <!-- Search bar & Export btn -->
        <div class="p-6 bg-gray-50/50 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <form action="{{ route('owner-dept.beneficiaries') }}" method="GET" class="w-full sm:w-80">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, mobile, profile..." 
                           class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>
            </form>
            <div class="flex gap-2">
                <a href="{{ route('owner-dept.export-csv') }}" class="bg-indigo-650 text-white text-xs font-bold px-4 py-2 rounded-xl hover:bg-indigo-700 transition flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-file-export"></i>Export to CSV
                </a>
            </div>
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
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                    @forelse($workers as $worker)
                        @php
                            $w = (array) $worker;
                            $wId = $w['_id'] ?? $w['id'] ?? '';
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 text-xs font-bold text-indigo-650 hover:text-indigo-850 hover:underline cursor-pointer" onclick="openViewBenModal({{ json_encode($w) }})">
                                {{ $w['full_name'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                {{ $w['father_name'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-gray-600">
                                {{ $w['mobile'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <span class="bg-indigo-50 text-indigo-700 font-bold px-2 py-0.5 rounded">{{ $w['job_profile'] ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-gray-500">
                                {{ $w['ration_card_number'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider
                                    @if(($w['status'] ?? 'pending') === 'approved' || ($w['status'] ?? 'pending') === 'verified') bg-green-50 text-green-700 border border-green-200
                                    @elseif(($w['status'] ?? 'pending') === 'rejected') bg-red-50 text-red-700 border border-red-200
                                    @else bg-amber-50 text-amber-700 border border-amber-200
                                    @endif">
                                    {{ $w['status'] ?? 'pending' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center flex items-center justify-center gap-2">
                                <button type="button" 
                                        onclick="openViewBenModal({{ json_encode($w) }})"
                                        class="text-emerald-600 hover:text-emerald-800 transition p-1.5" title="View Profile Details">
                                    <i class="fa-solid fa-eye text-base"></i>
                                </button>
                                <button type="button" 
                                        onclick="openEditBenModal({{ json_encode($w) }})"
                                        class="text-indigo-600 hover:text-indigo-850 transition p-1.5" title="Edit Beneficiary">
                                    <i class="fa-solid fa-pen-to-square text-base"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-400">No registered beneficiaries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($workers instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $workers->hasPages())
            <div class="p-6 border-t border-gray-100 bg-gray-50/20">
                {{ $workers->links() }}
            </div>
        @endif
    </div>

    <!-- TAB 2: Add Beneficiary -->
    <div id="ben-content-add" class="ben-panel space-y-6 @if($activeTab !== 'add') hidden @endif">
        @if(!session('ration_card_family'))
            <!-- STEP 1: Verify Ration Card PDF -->
            <div class="bg-white p-6 border border-gray-100 rounded-2xl shadow-sm">
                <div class="space-y-4 max-w-lg mx-auto py-6">
                    <div class="text-center space-y-2">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto text-xl shadow-inner">
                            <i class="fa-solid fa-file-shield"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Ration Card Verification</h3>
                        <p class="text-xs text-gray-400 leading-relaxed">
                            To register a worker, please upload the Ration Card PDF to extract and verify the family structure first.
                        </p>
                    </div>

                    <form action="{{ route('owner-dept.verify-ration-card') }}" method="POST" enctype="multipart/form-data" class="space-y-4 bg-gray-50 p-6 rounded-2xl border border-gray-250/30">
                        @csrf
                        <div>
                            <label for="ration_card_pdf" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Ration Card PDF File *</label>
                            <input type="file" name="ration_card_pdf" id="ration_card_pdf" accept=".pdf" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition file:mr-3 file:py-1 file:px-2 file:rounded file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-bold hover:file:bg-indigo-100" />
                        </div>

                        <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow transition">
                            Verify & Parse Ration Card
                        </button>
                    </form>
                </div>
            </div>
        @else
            <!-- STEP 2: Selected Family Member Registration Form -->
        <div id="ben-content-more-details" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="space-y-1">
                    <h3 class="text-md font-bold text-gray-800 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-green-50 text-green-600 flex items-center justify-center text-xs shadow-inner">
                            <i class="fa-solid fa-check"></i>
                        </span>
                        <span>Ration Card Verified (No: {{ session('ration_card_number') }})</span>
                    </h3>
                    <p class="text-xs text-gray-400">Parsed {{ count(session('ration_card_family')) }} family members. Select one to register.</p>
                </div>
                <a href="{{ route('owner-dept.beneficiaries', ['clear_ration' => 1]) }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-xmark"></i>Change Ration Card
                </a>
            </div>

            <!-- Member Dropdown Selector -->
            <div>
                <label for="member_selector" class="block text-[10px] font-black text-gray-650 uppercase mb-1.5">Select Member to Onboard *</label>
                <select id="member_selector" onchange="selectFamilyMember()" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition font-bold text-gray-700 bg-gray-50/50">
                    <option value="">-- Choose Family Member --</option>
                    @foreach(session('ration_card_family') as $index => $m)
                        <option value="{{ $index }}" data-name="{{ $m['name'] }}" data-gender="{{ $m['gender'] }}" data-relation="{{ $m['relation'] ?? 'Member' }}" data-age="{{ $m['age'] ?? 0 }}">
                            {{ $m['name'] }} (Age: {{ $m['age'] ?? 0 }}, Relation: {{ $m['relation'] ?? 'Member' }}, Gender: {{ ucfirst($m['gender']) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Complete Registration Form -->
            <form id="ben_register_form" action="{{ route('owner-dept.create-beneficiary') }}" method="POST" class="space-y-6 hidden">
                @csrf
                <input type="hidden" name="ration_card_number" value="{{ session('ration_card_number') }}" />

                <div class="space-y-4">
                    <h4 class="text-xs font-black text-indigo-650 uppercase tracking-wider border-b pb-1">Artisan Profile Details</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="wrk_full_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Full Name *</label>
                            <input type="text" name="full_name" id="wrk_full_name" required readonly class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs bg-gray-50 font-bold focus:outline-none transition cursor-not-allowed" />
                        </div>
                        <div>
                            <label for="wrk_gender" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Gender *</label>
                            <input type="text" name="gender" id="wrk_gender" required readonly class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs bg-gray-50 font-bold focus:outline-none transition cursor-not-allowed" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="wrk_relation" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Relationship to Head *</label>
                            <input type="text" name="relation" id="wrk_relation" required readonly class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs bg-gray-50 font-bold focus:outline-none transition cursor-not-allowed" />
                        </div>
                        <div>
                            <label for="wrk_age" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Age *</label>
                            <input type="number" name="age" id="wrk_age" required readonly class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs bg-gray-50 font-bold focus:outline-none transition cursor-not-allowed" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="wrk_mobile_in" class="block text-[10px] font-black text-gray-650 uppercase mb-1">Mobile Contact *</label>
                            <input type="text" name="mobile" id="wrk_mobile_in" placeholder="10-digit Mobile Number" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-505 transition font-bold" />
                        </div>
                        <div>
                            <label for="wrk_job_profile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Job Profile / Trade *</label>
                            <select name="job_profile" id="wrk_job_profile" onchange="checkOtherTrade(this)" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-505 transition font-bold text-gray-700">
                                <option value="">-- Select Job Profile / Trade * --</option>
                                @foreach($activeTrades as $trade)
                                    <option value="{{ $trade->name }}">{{ $trade->name }}</option>
                                @endforeach
                                <option value="Other">Other (Please Specify)</option>
                            </select>
                            <div id="other_trade_wrapper" class="mt-2 hidden">
                                <label for="wrk_job_profile_other" class="block text-[9px] font-black text-gray-500 uppercase mb-1">Specify Trade *</label>
                                <input type="text" name="job_profile_other" id="wrk_job_profile_other" placeholder="Please specify your trade" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-505 transition font-bold" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="wrk_address" class="block text-[10px] font-black text-gray-655 uppercase mb-1">Aadhaar Address *</label>
                        <input type="text" name="aadhaar_address" id="wrk_address" value="{{ session('ration_card_address') }}" placeholder="Full address details..." required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                    </div>
                </div>

                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md transition">
                    Complete Worker Registration
                </button>
            </form>
        </div>
        @endif
    </div>


<!-- View Beneficiary Details Modal -->
<div id="viewBenModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full border border-gray-100 shadow-xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-indigo-900 text-white">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full border border-white/20 overflow-hidden flex items-center justify-center bg-white/10 shrink-0">
                    <img id="view_ben_photo" src="" alt="Photo" class="w-full h-full object-cover hidden" onerror="this.src='https://placehold.co/100x100?text=Profile';">
                    <i id="view_ben_avatar_icon" class="fa-solid fa-user text-lg text-indigo-200"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white" id="view_ben_name">Worker Profile</h3>
                    <p class="text-[10px] text-indigo-200 font-bold uppercase tracking-wider" id="view_ben_status">Verification: Pending</p>
                </div>
            </div>
            <button type="button" onclick="closeViewBenModal()" class="text-white/70 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Body / Accordion List -->
        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto bg-gray-50/50">
            
            <!-- 1. Personal Details Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('personal')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-user text-indigo-500"></i> Personal Details
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-personal"></i>
                </button>
                <div id="acc-personal" class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold text-gray-700 hidden">
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Father's Name</span><p id="view_father_name" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Gender</span><p id="view_gender" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Date of Birth</span><p id="view_dob" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Marital Status</span><p id="view_marital" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Caste / Category</span><p id="view_category" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Mobile Number</span><p id="view_mobile" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">District</span><p id="view_district" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">State</span><p id="view_state" class="font-bold text-gray-900 mt-0.5"></p></div>
                </div>
            </div>

            <!-- 2. Education Details Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('education')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-graduation-cap text-indigo-500"></i> Education Details
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-education"></i>
                </button>
                <div id="acc-education" class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold text-gray-700 hidden">
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Qualification</span><p id="view_qualification" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">School / Institution Name</span><p id="view_school" class="font-bold text-gray-900 mt-0.5"></p></div>
                </div>
            </div>

            <!-- 3. Employment Details Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('employment')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-briefcase text-indigo-500"></i> Employment Details
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-employment"></i>
                </button>
                <div id="acc-employment" class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold text-gray-700 hidden">
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Job Profile / Trade</span><p id="view_job_profile" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Work Experience (in years)</span><p id="view_experience" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Daily Wage (INR)</span><p id="view_wage" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Employment Site Address</span><p id="view_employment_site" class="font-bold text-gray-900 mt-0.5"></p></div>
                </div>
            </div>

            <!-- 4. Bank Details Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('bank')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-building-columns text-indigo-500"></i> Bank Details
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-bank"></i>
                </button>
                <div id="acc-bank" class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold text-gray-700 hidden">
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Account Number</span><p id="view_bank_acc" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Bank Name & Branch</span><p id="view_bank_name" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Bank IFSC</span><p id="view_bank_ifsc" class="font-bold text-gray-900 mt-0.5"></p></div>
                </div>
            </div>

            <!-- 5. BOCW Registration Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('bocw')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-certificate text-indigo-500"></i> BOCW Registration
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-bocw"></i>
                </button>
                <div id="acc-bocw" class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold text-gray-700 hidden">
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">BOCW Card Number</span><p id="view_bocw_card" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Registration Date</span><p id="view_bocw_reg_date" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Expiry Date / Valid Upto</span><p id="view_bocw_valid" class="font-bold text-gray-900 mt-0.5"></p></div>
                </div>
            </div>

            <!-- 6. PDS Data Accordion (Ration Card) -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('pds')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-users text-indigo-500"></i> PDS Data (Family Members)
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-pds"></i>
                </button>
                <div id="acc-pds" class="p-4 text-xs font-semibold text-gray-700 hidden space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="text-[9px] font-black text-gray-400 uppercase block">Ration Card Number</span><p id="view_ration_card_number" class="font-bold text-gray-900 mt-0.5"></p></div>
                        <div><span class="text-[9px] font-black text-gray-400 uppercase block">Ration Card Address</span><p id="view_ration_address" class="font-bold text-gray-900 mt-0.5"></p></div>
                    </div>
                    <div class="border-t border-gray-150/40 pt-3">
                        <span class="text-[9px] font-black text-gray-400 uppercase block mb-2">Parsed Family Members</span>
                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="w-full text-left border-collapse text-[11px]">
                                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider text-[8px]">
                                    <tr>
                                        <th class="p-2 border-b border-gray-100">Name</th>
                                        <th class="p-2 border-b border-gray-100">Relationship</th>
                                        <th class="p-2 border-b border-gray-100">Gender</th>
                                        <th class="p-2 border-b border-gray-100">Age</th>
                                    </tr>
                                </thead>
                                <tbody id="view_family_members_list" class="divide-y divide-gray-100 text-gray-700 font-medium">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. Signature Upload Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('signature')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-signature text-indigo-500"></i> Signature Upload
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-signature"></i>
                </button>
                <div id="acc-signature" class="p-4 flex flex-col items-center justify-center hidden">
                    <span class="text-[9px] font-black text-gray-400 uppercase mb-2">Worker Signature</span>
                    <img id="view_signature_img" src="" alt="No signature uploaded" class="max-h-32 border border-gray-200 rounded shadow-sm bg-gray-50 p-2 object-contain" />
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
            <button type="button" onclick="closeViewBenModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow transition">Close Details</button>
        </div>
    </div>
</div>

<!-- Edit Beneficiary Modal -->
<div id="editBenModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full border border-gray-100 shadow-xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-indigo-900 text-white">
            <h3 class="text-sm font-black text-white flex items-center gap-2">
                <i class="fa-solid fa-user-pen text-indigo-200"></i>
                <span>Edit Beneficiary Details</span>
            </h3>
            <button type="button" onclick="closeEditBenModal()" class="text-white/70 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form id="editBenForm" action="" method="POST" class="flex flex-col">
            @csrf
            
            <div class="p-6 space-y-6 max-h-[60vh] overflow-y-auto bg-gray-50/50 text-xs text-gray-700">
                <!-- Section 1: Personal Details -->
                <div class="bg-white border border-gray-200/65 rounded-xl p-4 space-y-4 shadow-sm">
                    <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2 mb-2 flex items-center gap-1.5 text-xs">
                        <i class="fa-solid fa-user text-indigo-500"></i> Personal Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_full_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Full Name *</label>
                            <input type="text" name="full_name" id="edit_full_name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_father_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Father's Name *</label>
                            <input type="text" name="father_name" id="edit_father_name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_dob" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Date of Birth</label>
                            <input type="date" name="dob" id="edit_dob" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_gender" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Gender</label>
                            <select name="gender" id="edit_gender" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition bg-white">
                                <option value="male">MALE</option>
                                <option value="female">FEMALE</option>
                                <option value="other">OTHER</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_category" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Category</label>
                            <input type="text" name="category" id="edit_category" placeholder="GEN, OBC, SC, ST etc." class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_marital_status" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Marital Status</label>
                            <input type="text" name="marital_status" id="edit_marital_status" placeholder="Married, Single, Widowed etc." class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Contact & Address -->
                <div class="bg-white border border-gray-200/65 rounded-xl p-4 space-y-4 shadow-sm">
                    <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2 mb-2 flex items-center gap-1.5 text-xs">
                        <i class="fa-solid fa-address-book text-indigo-500"></i> Contact & Location
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_mobile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Mobile *</label>
                            <input type="text" name="mobile" id="edit_mobile" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_state" class="block text-[10px] font-black text-gray-600 uppercase mb-1">State</label>
                            <input type="text" name="state" id="edit_state" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_district" class="block text-[10px] font-black text-gray-600 uppercase mb-1">District</label>
                            <input type="text" name="district" id="edit_district" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="edit_aadhaar_address" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Aadhaar Address</label>
                            <textarea name="aadhaar_address" id="edit_aadhaar_address" rows="2" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Professional Info -->
                <div class="bg-white border border-gray-200/65 rounded-xl p-4 space-y-4 shadow-sm">
                    <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2 mb-2 flex items-center gap-1.5 text-xs">
                        <i class="fa-solid fa-briefcase text-indigo-500"></i> Professional Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_job_profile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Job Profile / Trade *</label>
                            <input type="text" name="job_profile" id="edit_job_profile" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_qualification" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Qualification</label>
                            <input type="text" name="qualification" id="edit_qualification" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_experience_years" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Experience (Years)</label>
                            <input type="number" name="experience_years" id="edit_experience_years" min="0" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_daily_wage" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Daily Wage (₹)</label>
                            <input type="number" name="daily_wage" id="edit_daily_wage" min="0" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                    </div>
                </div>

                <!-- Section 4: Government IDs & Cards -->
                <div class="bg-white border border-gray-200/65 rounded-xl p-4 space-y-4 shadow-sm">
                    <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2 mb-2 flex items-center gap-1.5 text-xs">
                        <i class="fa-solid fa-id-card text-indigo-500"></i> Cards & Identity Documents
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_aadhaar_number" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Aadhaar Number</label>
                            <input type="text" name="aadhaar_number" id="edit_aadhaar_number" maxlength="12" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_ration_card_number" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Ration Card Number</label>
                            <input type="text" name="ration_card_number" id="edit_ration_card_number" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_bocw_card_number" class="block text-[10px] font-black text-gray-600 uppercase mb-1">BOCW Card Number</label>
                            <input type="text" name="bocw_card_number" id="edit_bocw_card_number" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                    </div>
                </div>

                <!-- Section 5: Bank Details -->
                <div class="bg-white border border-gray-200/65 rounded-xl p-4 space-y-4 shadow-sm">
                    <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2 mb-2 flex items-center gap-1.5 text-xs">
                        <i class="fa-solid fa-building-columns text-indigo-500"></i> Bank Account Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_bank_account_number" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Bank Account Number</label>
                            <input type="text" name="bank_account_number" id="edit_bank_account_number" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_bank_name_branch" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Bank Name & Branch</label>
                            <input type="text" name="bank_name_branch" id="edit_bank_name_branch" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                        <div>
                            <label for="edit_bank_ifsc" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Bank IFSC</label>
                            <input type="text" name="bank_ifsc" id="edit_bank_ifsc" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 p-6 bg-gray-50 border-t border-gray-100">
                <button type="button" onclick="closeEditBenModal()" class="px-4 py-2 border border-gray-200 text-gray-500 text-xs font-bold rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-500 transition shadow">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const BASE_URL = "{{ asset('') }}";
    function switchBenTab(tabId) {
        // Toggle tabs buttons styles
        const tabs = ['view', 'add'];
        tabs.forEach(t => {
            const btn = document.getElementById('ben-tab-' + t);
            if (btn) {
                btn.className = "px-4 py-2 rounded-lg hover:bg-gray-100 transition";
            }
        });
        const activeBtn = document.getElementById('ben-tab-' + tabId);
        if (activeBtn) {
            activeBtn.className = "px-4 py-2 rounded-lg bg-indigo-50 text-indigo-700 font-extrabold transition";
        }

        // Toggle panel visibility
        const panels = document.querySelectorAll('.ben-panel');
        panels.forEach(p => p.classList.add('hidden'));
        
        const activePanel = document.getElementById('ben-content-' + tabId);
        if (activePanel) {
            activePanel.classList.remove('hidden');
        }
    }

    function selectFamilyMember() {
        const selector = document.getElementById('member_selector');
        const selectedIndex = selector.selectedIndex;
        const form = document.getElementById('ben_register_form');
        
        if (selectedIndex === 0) {
            form.classList.add('hidden');
            return;
        }

        const option = selector.options[selectedIndex];
        const name = option.getAttribute('data-name');
        const gender = option.getAttribute('data-gender');
        const relation = option.getAttribute('data-relation');
        const age = option.getAttribute('data-age');

        document.getElementById('wrk_full_name').value = name;
        document.getElementById('wrk_gender').value = gender;
        document.getElementById('wrk_relation').value = relation;
        document.getElementById('wrk_age').value = age;

        form.classList.remove('hidden');
    }

    function openEditBenModal(worker) {
        const modal = document.getElementById('editBenModal');
        const form = document.getElementById('editBenForm');
        
        const wId = worker._id || worker.id || '';
        form.action = "{{ route('owner-dept.update-beneficiary', ':id') }}".replace(':id', wId);

        // Populate fields
        document.getElementById('edit_full_name').value = worker.full_name || '';
        document.getElementById('edit_father_name').value = worker.father_name || '';
        document.getElementById('edit_mobile').value = worker.mobile || '';
        document.getElementById('edit_job_profile').value = worker.job_profile || '';

        // Additional fields
        document.getElementById('edit_dob').value = worker.dob ? (typeof worker.dob === 'string' ? worker.dob.split('T')[0] : (worker.dob.date ? worker.dob.date.split(' ')[0] : '')) : '';
        document.getElementById('edit_gender').value = worker.gender || 'male';
        document.getElementById('edit_category').value = worker.category || '';
        document.getElementById('edit_marital_status').value = worker.marital_status || '';
        
        document.getElementById('edit_state').value = worker.state || '';
        document.getElementById('edit_district').value = worker.district || '';
        document.getElementById('edit_aadhaar_address').value = worker.aadhaar_address || '';

        document.getElementById('edit_qualification').value = worker.qualification || '';
        document.getElementById('edit_experience_years').value = worker.experience_years !== undefined ? worker.experience_years : '';
        document.getElementById('edit_daily_wage').value = worker.daily_wage !== undefined ? worker.daily_wage : '';

        document.getElementById('edit_aadhaar_number').value = worker.aadhaar_number || '';
        document.getElementById('edit_ration_card_number').value = worker.ration_card_number || '';
        document.getElementById('edit_bocw_card_number').value = worker.bocw_card_number || '';

        document.getElementById('edit_bank_account_number').value = worker.bank_account_number || '';
        document.getElementById('edit_bank_name_branch').value = worker.bank_name_branch || '';
        document.getElementById('edit_bank_ifsc').value = worker.bank_ifsc || '';

        modal.classList.remove('hidden');
    }

    function closeEditBenModal() {
        const modal = document.getElementById('editBenModal');
        modal.classList.add('hidden');
    }

    function openViewBenModal(worker) {
        document.getElementById('view_ben_name').innerText = worker.full_name || 'N/A';
        document.getElementById('view_ben_status').innerText = 'Verification: ' + (worker.status || 'pending').toUpperCase();

        const photoImg = document.getElementById('view_ben_photo');
        const avatarIcon = document.getElementById('view_ben_avatar_icon');
        if (worker.photo_url) {
            const cleanPath = worker.photo_url.startsWith('/') ? worker.photo_url.substring(1) : worker.photo_url;
            photoImg.src = BASE_URL + cleanPath;
            photoImg.classList.remove('hidden');
            avatarIcon.classList.add('hidden');
        } else {
            photoImg.src = '';
            photoImg.classList.add('hidden');
            avatarIcon.classList.remove('hidden');
        }

        // 1. Personal Details
        document.getElementById('view_father_name').innerText = worker.father_name || 'N/A';
        document.getElementById('view_gender').innerText = (worker.gender || 'N/A').toUpperCase();
        document.getElementById('view_dob').innerText = worker.dob ? (typeof worker.dob === 'string' ? worker.dob.split('T')[0] : (worker.dob.date ? worker.dob.date.split(' ')[0] : 'N/A')) : 'N/A';
        document.getElementById('view_marital').innerText = (worker.marital_status || 'N/A').toUpperCase();
        document.getElementById('view_category').innerText = worker.category || 'N/A';
        document.getElementById('view_mobile').innerText = worker.mobile || 'N/A';
        document.getElementById('view_district').innerText = worker.district || 'N/A';
        document.getElementById('view_state').innerText = worker.state || 'N/A';

        // 2. Education Details
        document.getElementById('view_qualification').innerText = worker.qualification || 'N/A';
        document.getElementById('view_school').innerText = worker.school_institution || 'N/A';

        // 3. Employment Details
        document.getElementById('view_job_profile').innerText = worker.job_profile || 'N/A';
        document.getElementById('view_experience').innerText = worker.experience_years !== undefined ? worker.experience_years + ' Years' : 'N/A';
        document.getElementById('view_wage').innerText = worker.daily_wage !== undefined ? '₹' + worker.daily_wage : 'N/A';
        document.getElementById('view_employment_site').innerText = worker.employment_site || 'N/A';

        // 4. Bank Details
        document.getElementById('view_bank_acc').innerText = worker.bank_account_number || 'N/A';
        document.getElementById('view_bank_name').innerText = worker.bank_name_branch || 'N/A';
        document.getElementById('view_bank_ifsc').innerText = worker.bank_ifsc || 'N/A';

        // 5. BOCW Details
        document.getElementById('view_bocw_card').innerText = worker.bocw_card_number || 'N/A';
        document.getElementById('view_bocw_reg_date').innerText = worker.bocw_reg_date ? worker.bocw_reg_date.split('T')[0] : 'N/A';
        document.getElementById('view_bocw_valid').innerText = worker.bocw_valid_upto ? worker.bocw_valid_upto.split('T')[0] : 'N/A';

        // 6. PDS Details
        document.getElementById('view_ration_card_number').innerText = worker.ration_card_number || 'N/A';
        document.getElementById('view_ration_address').innerText = worker.aadhaar_address || 'N/A';

        const familyTable = document.getElementById('view_family_members_list');
        familyTable.innerHTML = '';
        if (worker.family_members && Array.isArray(worker.family_members) && worker.family_members.length > 0) {
            worker.family_members.forEach(member => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="p-2 border-b border-gray-100 font-bold text-gray-800">${member.name || 'N/A'}</td>
                    <td class="p-2 border-b border-gray-100">${member.relation || 'N/A'}</td>
                    <td class="p-2 border-b border-gray-100 uppercase text-[10px]">${member.gender || 'N/A'}</td>
                    <td class="p-2 border-b border-gray-100">${member.age !== undefined ? member.age + ' Years' : 'N/A'}</td>
                `;
                familyTable.appendChild(tr);
            });
        } else {
            familyTable.innerHTML = `
                <tr>
                    <td colspan="4" class="p-4 text-center text-gray-400">No family member records found.</td>
                </tr>
            `;
        }

        // 7. Signature Details
        const sigImg = document.getElementById('view_signature_img');
        if (worker.signature_url) {
            const cleanSigPath = worker.signature_url.startsWith('/') ? worker.signature_url.substring(1) : worker.signature_url;
            sigImg.src = BASE_URL + cleanSigPath;
            sigImg.classList.remove('hidden');
        } else {
            sigImg.src = '';
            sigImg.classList.add('hidden');
        }

        // Collapse all accordions by default on load
        ['personal', 'education', 'employment', 'bank', 'bocw', 'pds', 'signature'].forEach(sec => {
            document.getElementById('acc-' + sec).classList.add('hidden');
            document.getElementById('arrow-' + sec).classList.remove('rotate-180');
        });

        // Open Modal
        const modal = document.getElementById('viewBenModal');
        modal.classList.remove('hidden');
    }

    function closeViewBenModal() {
        document.getElementById('viewBenModal').classList.add('hidden');
    }

    function toggleAccordion(sectionId) {
        const acc = document.getElementById('acc-' + sectionId);
        const arrow = document.getElementById('arrow-' + sectionId);
        if (acc.classList.contains('hidden')) {
            acc.classList.remove('hidden');
            arrow.classList.add('rotate-180');
        } else {
            acc.classList.add('hidden');
            arrow.classList.remove('rotate-180');
        }
    }

    function checkOtherTrade(select) {
        const wrapper = document.getElementById('other_trade_wrapper');
        const input = document.getElementById('wrk_job_profile_other');
        if (select.value === 'Other') {
            wrapper.classList.remove('hidden');
            input.required = true;
        } else {
            wrapper.classList.add('hidden');
            input.required = false;
            input.value = '';
        }
    }
</script>
@endsection





