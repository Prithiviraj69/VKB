@extends('layouts.admin')

@section('title', 'Super Admin Onboard Hub')

@section('content')
<div class="w-full space-y-6">
    <!-- Header Console -->
    <div>
        <h2 class="text-2xl font-black text-gray-800 tracking-wide">Onboard Hub</h2>
        <p class="text-gray-500 text-xs mt-1">Register new Departments, NGOs, Employers, Contractors, or Workers in the registry.</p>
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

    <!-- Onboard / Create Tabbed Forms -->
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-6 bg-gradient-to-b from-white to-gray-50/20">
        <div>
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-square-plus text-orange-600"></i>
                <span>Register Entity</span>
            </h3>
            <p class="text-xs text-gray-400 mt-1">Select a role tab below to display its registration form.</p>
        </div>

        <!-- Form Tabs selector -->
        <div class="flex border-b border-gray-100 text-xs font-bold text-gray-400 overflow-x-auto pb-1 gap-2">
            <button type="button" onclick="switchFormTab('dept')" id="form-tab-dept" class="px-3 py-1.5 rounded-lg bg-orange-50 text-orange-700 font-extrabold transition">Dept</button>
            <button type="button" onclick="switchFormTab('ngo')" id="form-tab-ngo" class="px-3 py-1.5 rounded-lg hover:bg-gray-100 transition">NGO</button>
            <button type="button" onclick="switchFormTab('emp')" id="form-tab-emp" class="px-3 py-1.5 rounded-lg hover:bg-gray-100 transition">Employer</button>
            <button type="button" onclick="switchFormTab('cnt')" id="form-tab-cnt" class="px-3 py-1.5 rounded-lg hover:bg-gray-100 transition">Contractor</button>
        </div>

        <!-- Form 1: Department Onboarding -->
        <form action="{{ route('super-admin.create-department') }}" method="POST" id="form-content-dept" enctype="multipart/form-data" class="space-y-5 form-panel">
            @csrf

            {{-- Section 1: Department Info --}}
            <div class="space-y-3">
                <h4 class="text-xs font-black text-orange-600 uppercase tracking-wider border-b pb-1">1. Department Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Dept Code *</label>
                        <input type="number" name="department_code" placeholder="e.g. 101" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Dept Name *</label>
                        <input type="text" name="department_name" placeholder="District Labour Office" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Parent Dept Name</label>
                    <input type="text" name="parent_department_name" placeholder="Board / Head Office (optional)" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Dept Email *</label>
                        <input type="email" name="department_email" placeholder="dept@gov.in" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Office Address *</label>
                        <input type="text" name="office_address" placeholder="123 Gov Complex" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Department Logo</label>
                    <input type="file" name="logo" accept="image/*" class="w-full px-3 py-1.5 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100" />
                    <p class="text-[10px] text-gray-400 mt-1 font-semibold">Supported formats: JPEG, PNG, JPG, GIF (Max 2MB)</p>
                </div>
            </div>

            {{-- Section 2: Department Admin Credentials --}}
            <div class="space-y-3">
                <h4 class="text-xs font-black text-sky-600 uppercase tracking-wider border-b pb-1 flex items-center gap-2">
                    <i class="fa-solid fa-user-tie"></i> 2. Department Admin Credentials
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Full Name *</label>
                        <input type="text" name="admin_name" placeholder="Admin Name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 transition" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Designation *</label>
                        <input type="text" name="admin_designation" placeholder="Joint Director" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 transition" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Mobile *</label>
                        <input type="text" name="admin_mobile" placeholder="9876543210" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 transition" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Login Email *</label>
                        <input type="email" name="admin_email" placeholder="admin@gov.in" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 transition" />
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Password *</label>
                    <input type="password" name="admin_password" placeholder="Min 6 chars" minlength="6" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 transition" />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="w-full py-3 bg-orange-600 hover:bg-orange-500 text-white font-bold rounded-xl text-xs shadow-md hover:shadow-lg transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-building-columns text-sm"></i> 
                    <span>Register Govt Dept</span>
                </button>
                <button type="submit" 
                        formaction="{{ route('super-admin.create-owner-department') }}"
                        @if($hasOwnerDept) disabled @endif
                        class="w-full py-3 bg-indigo-700 hover:bg-indigo-600 text-white font-bold rounded-xl text-xs shadow-md hover:shadow-lg transition flex items-center justify-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-crown text-sm"></i> 
                    <span>Register Owner Dept</span>
                </button>
            </div>
        </form>

        <!-- Form 2: NGO Onboarding -->
        <form action="{{ route('super-admin.create-ngo') }}" method="POST" id="form-content-ngo" class="space-y-4 form-panel hidden">
            @csrf
            <div>
                <label for="ngo_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">NGO Name *</label>
                <input type="text" name="ngo_name" id="ngo_name" placeholder="NGO Name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="ngo_registration_no" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Reg No *</label>
                    <input type="text" name="registration_no" id="ngo_registration_no" placeholder="NGO12345" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
                <div>
                    <label for="ngo_mobile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Mobile *</label>
                    <input type="text" name="mobile" id="ngo_mobile" placeholder="9876543210" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
            </div>
            <div>
                <label for="ngo_contact_person" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Contact Person *</label>
                <input type="text" name="contact_person" id="ngo_contact_person" placeholder="Contact person name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
            </div>
            <div>
                <label for="ngo_office_address" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Office Address *</label>
                <textarea name="office_address" id="ngo_office_address" rows="2" placeholder="Full Address..." required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition"></textarea>
            </div>
            <div class="border-t border-gray-100 pt-4 space-y-4">
                <h4 class="text-xs font-black text-orange-600 uppercase tracking-wider">Login Credentials</h4>
                <div>
                    <label for="ngo_email" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Login Email *</label>
                    <input type="email" name="email" id="ngo_email" placeholder="ngo@example.com" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
                <div>
                    <label for="ngo_password" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Password *</label>
                    <input type="password" name="password" id="ngo_password" placeholder="Min 6 chars" minlength="6" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
            </div>
            <button type="submit" class="w-full py-3 bg-orange-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-orange-500 hover:shadow-lg transition">
                Register NGO
            </button>
        </form>

        <!-- Form 3: Employer Onboarding -->
        <form action="{{ route('super-admin.create-employer') }}" method="POST" id="form-content-emp" class="space-y-4 form-panel hidden">
            @csrf
            <div>
                <label for="emp_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Employer / Factory Name *</label>
                <input type="text" name="employer_name" id="emp_name" placeholder="Factory name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="emp_reg_no" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Registration No *</label>
                    <input type="text" name="registration_no" id="emp_reg_no" placeholder="REG12345" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
                <div>
                    <label for="emp_mobile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Mobile *</label>
                    <input type="text" name="mobile" id="emp_mobile" placeholder="9876543210" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
            </div>
            <div>
                <label for="emp_contact_person" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Contact Person *</label>
                <input type="text" name="contact_person" id="emp_contact_person" placeholder="Contact person name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
            </div>
            <div>
                <label for="emp_office_address" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Factory Address *</label>
                <textarea name="office_address" id="emp_office_address" rows="2" placeholder="Full Address..." required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition"></textarea>
            </div>
            <div class="border-t border-gray-100 pt-4 space-y-4">
                <h4 class="text-xs font-black text-orange-600 uppercase tracking-wider">Login Credentials</h4>
                <div>
                    <label for="emp_email" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Login Email *</label>
                    <input type="email" name="email" id="emp_email" placeholder="employer@example.com" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
                <div>
                    <label for="emp_password" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Password *</label>
                    <input type="password" name="password" id="emp_password" placeholder="Min 6 chars" minlength="6" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
            </div>
            <button type="submit" class="w-full py-3 bg-orange-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-orange-500 hover:shadow-lg transition">
                Register Employer
            </button>
        </form>

        <!-- Form 4: Contractor Onboarding -->
        <form action="{{ route('super-admin.create-contractor') }}" method="POST" id="form-content-cnt" class="space-y-4 form-panel hidden">
            @csrf
            <div>
                <label for="cnt_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Contractor / Thekedar Name *</label>
                <input type="text" name="contractor_name" id="cnt_name" placeholder="Contractor name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="cnt_lic" class="block text-[10px] font-black text-gray-600 uppercase mb-1">License No *</label>
                    <input type="text" name="license_no" id="cnt_lic" placeholder="LIC12345" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
                <div>
                    <label for="cnt_mobile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Mobile *</label>
                    <input type="text" name="mobile" id="cnt_mobile" placeholder="9876543210" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
            </div>
            <div>
                <label for="cnt_office_address" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Office Address *</label>
                <textarea name="office_address" id="cnt_office_address" rows="2" placeholder="Full Address..." required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition"></textarea>
            </div>
            <div class="border-t border-gray-100 pt-4 space-y-4">
                <h4 class="text-xs font-black text-orange-600 uppercase tracking-wider">Login Credentials</h4>
                <div>
                    <label for="cnt_email" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Login Email *</label>
                    <input type="email" name="email" id="cnt_email" placeholder="contractor@example.com" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
                <div>
                    <label for="cnt_password" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Password *</label>
                    <input type="password" name="password" id="cnt_password" placeholder="Min 6 chars" minlength="6" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition" />
                </div>
            </div>
            <button type="submit" class="w-full py-3 bg-orange-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-orange-500 hover:shadow-lg transition">
                Register Contractor
            </button>
        </form>


    </div>
</div>
@endsection

@section('scripts')
<script>
    function switchFormTab(tabId) {
        // Toggle tabs styles
        const tabs = ['dept', 'ngo', 'emp', 'cnt'];
        tabs.forEach(t => {
            const btn = document.getElementById('form-tab-' + t);
            if (btn) {
                btn.className = "px-3 py-1.5 rounded-lg hover:bg-gray-100 transition";
            }
        });
        const activeBtn = document.getElementById('form-tab-' + tabId);
        if (activeBtn) {
            activeBtn.className = "px-3 py-1.5 rounded-lg bg-orange-50 text-orange-700 font-extrabold transition";
        }

        // Toggle panel visibility
        const panels = document.querySelectorAll('.form-panel');
        panels.forEach(p => p.classList.add('hidden'));
        const activePanel = document.getElementById('form-content-' + tabId);
        if (activePanel) {
            activePanel.classList.remove('hidden');
        }
    }
</script>
@endsection
