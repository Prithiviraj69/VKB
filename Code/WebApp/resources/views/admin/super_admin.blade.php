@extends('layouts.admin')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Header Console -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Super Admin Dashboard</h2>
            <p class="text-gray-500 text-xs mt-1">Manage administrative registry logs and view overall system metrics.</p>
        </div>

    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Stats 1: Departments -->
        <div onclick="switchListTab('dept')" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-orange-200 transition duration-150">
            <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                <i class="fa-solid fa-building-columns text-xl"></i>
            </div>
            <div>
                <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">Depts</span>
                <span class="text-xl font-bold text-gray-800">{{ count($departments) }}</span>
            </div>
        </div>

        <!-- Stats 2: NGOs -->
        <div onclick="switchListTab('ngo')" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-purple-200 transition duration-150">
            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                <i class="fa-solid fa-handshake text-xl"></i>
            </div>
            <div>
                <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">NGOs</span>
                <span class="text-xl font-bold text-gray-800">{{ count($ngos) }}</span>
            </div>
        </div>

        <!-- Stats 3: Employers -->
        <div onclick="switchListTab('emp')" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-blue-200 transition duration-150">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                <i class="fa-solid fa-industry text-xl"></i>
            </div>
            <div>
                <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">Employers</span>
                <span class="text-xl font-bold text-gray-800">{{ count($employers) }}</span>
            </div>
        </div>

        <!-- Stats 4: Contractors -->
        <div onclick="switchListTab('cnt')" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-teal-200 transition duration-150">
            <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center">
                <i class="fa-solid fa-helmet-safety text-xl"></i>
            </div>
            <div>
                <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">Contractors</span>
                <span class="text-xl font-bold text-gray-800">{{ count($contractors) }}</span>
            </div>
        </div>

    </div>

    <!-- Main Workspace (Full Width) -->
    <div class="w-full bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between">
            <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Accounts Registry</h3>
                    <p class="text-xs text-gray-400 mt-1">Manage system accounts across all RBAC role tiers.</p>
                </div>

                <!-- Account List Tabs selector -->
                <div class="flex bg-gray-100 p-1 rounded-xl text-xs font-bold text-gray-500 gap-1">
                    <button type="button" onclick="switchListTab('dept')" id="list-tab-dept" class="px-3 py-1.5 rounded-lg bg-white text-gray-850 font-extrabold shadow-sm transition">Depts</button>
                    <button type="button" onclick="switchListTab('ngo')" id="list-tab-ngo" class="px-3 py-1.5 rounded-lg hover:text-gray-800 transition">NGOs</button>
                    <button type="button" onclick="switchListTab('emp')" id="list-tab-emp" class="px-3 py-1.5 rounded-lg hover:text-gray-800 transition">Employers</button>
                    <button type="button" onclick="switchListTab('cnt')" id="list-tab-cnt" class="px-3 py-1.5 rounded-lg hover:text-gray-800 transition">Contractors</button>
                </div>
            </div>

            <!-- List 1: Departments -->
            <div id="list-content-dept" class="overflow-x-auto flex-1 list-panel space-y-6">
                @if($ownerDeptUser)
                    <!-- Owner Department Special Section -->
                    <div class="mx-6 my-4 p-5 bg-gradient-to-r from-violet-50 to-indigo-50 border border-violet-200 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
                        <div class="flex items-start gap-4">
                            @if($ownerDeptUser->department && !empty($ownerDeptUser->department->logo_path))
                                <img src="{{ (str_contains(request()->root(), 'localhost') || str_contains(request()->root(), '127.0.0.1')) ? asset($ownerDeptUser->department->logo_path) : asset('public/' . $ownerDeptUser->department->logo_path) }}" alt="Logo" class="w-12 h-12 rounded-xl object-cover border border-violet-200 shadow shrink-0" />
                            @else
                                <div class="w-12 h-12 rounded-xl bg-violet-600 text-white flex items-center justify-center shadow shrink-0">
                                    <i class="fa-solid fa-crown text-xl"></i>
                                </div>
                            @endif
                            <div>
                                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded bg-violet-200 text-violet-800 tracking-wider">Owner Department</span>
                                <h4 class="font-black text-gray-800 text-sm mt-1">
                                    [{{ $ownerDeptUser->department_code }}]
                                    @if($ownerDeptUser->department)
                                        <a href="javascript:void(0)" onclick="openEditDeptModal('{{ $ownerDeptUser->department->id ?? $ownerDeptUser->department->_id }}', '{{ addslashes($ownerDeptUser->department->department_name) }}', '{{ addslashes($ownerDeptUser->department->parent_department_name ?? '') }}', '{{ addslashes($ownerDeptUser->department->office_address) }}', '{{ $ownerDeptUser->department->email }}')" class="text-violet-700 hover:text-violet-900 hover:underline">
                                            {{ $ownerDeptUser->department->department_name }}
                                        </a>
                                    @else
                                        <span>Owner Department</span>
                                    @endif
                                </h4>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Admin: <span class="font-bold text-gray-700">{{ $ownerDeptUser->name }}</span> ({{ $ownerDeptUser->email }})
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <form action="{{ route('super-admin.toggle-status', $ownerDeptUser->id) }}" method="POST">
                                @csrf
                                <button type="submit" 
                                        class="px-3 py-1 rounded-lg text-xs font-bold transition duration-150 uppercase tracking-wider
                                        {{ $ownerDeptUser->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200' : 'bg-red-100 text-red-800 border border-red-200 hover:bg-red-200' }}">
                                    {{ $ownerDeptUser->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                            <button type="button" 
                                    onclick="openChangePasswordModal('{{ $ownerDeptUser->id }}', '{{ $ownerDeptUser->name }}')" 
                                    class="text-amber-500 hover:text-amber-600 transition p-2 bg-white rounded-lg border border-gray-100 hover:shadow shadow-sm"
                                    title="Change Password">
                                <i class="fa-solid fa-key text-base"></i>
                            </button>
                            <form action="{{ route('super-admin.delete-user', $ownerDeptUser->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete the Owner Department? This deletes all associated verifiers/approvers.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-500 hover:text-rose-600 transition p-2 bg-white rounded-lg border border-gray-100 hover:shadow shadow-sm">
                                    <i class="fa-solid fa-trash-can text-base"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Department</th>
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($deptUsers as $user)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    @if($user->department)
                                        <div class="flex items-center gap-3">
                                            @if(!empty($user->department->logo_path))
                                                <img src="{{ (str_contains(request()->root(), 'localhost') || str_contains(request()->root(), '127.0.0.1')) ? asset($user->department->logo_path) : asset('public/' . $user->department->logo_path) }}" alt="Logo" class="w-10 h-10 rounded-xl object-cover border border-gray-150 shrink-0 shadow-sm" />
                                            @else
                                                <div class="w-10 h-10 rounded-xl bg-orange-100 border border-orange-200 text-orange-655 flex items-center justify-center font-bold text-sm shrink-0 shadow-sm uppercase">
                                                    {{ substr($user->department->department_name, 0, 1) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-xs font-bold text-gray-800">
                                                    [{{ $user->department_code }}]
                                                    <a href="javascript:void(0)" onclick="openEditDeptModal('{{ $user->department->id ?? $user->department->_id }}', '{{ addslashes($user->department->department_name) }}', '{{ addslashes($user->department->parent_department_name ?? '') }}', '{{ addslashes($user->department->office_address) }}', '{{ $user->department->email }}')" class="text-indigo-650 hover:text-indigo-850 hover:underline">
                                                        {{ $user->department->department_name }}
                                                    </a>
                                                </div>
                                                <div class="text-[10px] text-gray-400">{{ $user->department->office_address }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">Dept #{{ $user->department_code }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs">{{ $user->name }}</td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $user->email }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $roleColors = [
                                            'govt-dept-admin' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                                            'dept-verifier'   => 'bg-green-50 text-green-700 border border-green-200',
                                            'dept-approver'   => 'bg-blue-50 text-blue-700 border border-blue-200',
                                        ];
                                        $roleLabels = [
                                            'govt-dept-admin' => '👑 Dept Admin',
                                            'dept-verifier'   => '✅ Verifier',
                                            'dept-approver'   => '⚖️ Approver',
                                        ];
                                    @endphp
                                    <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider {{ $roleColors[$user->role] ?? 'bg-gray-50 text-gray-700 border border-gray-200' }}">
                                        {{ $roleLabels[$user->role] ?? $user->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('super-admin.toggle-status', $user->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" 
                                                class="px-2.5 py-0.5 rounded-full text-xs font-bold transition duration-150 uppercase tracking-wider
                                                {{ $user->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200' : 'bg-red-100 text-red-800 border border-red-200 hover:bg-red-200' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" 
                                                onclick="openChangePasswordModal('{{ $user->id }}', '{{ $user->name }}')" 
                                                class="text-amber-500 hover:text-amber-600 transition p-2"
                                                title="Change Password">
                                            <i class="fa-solid fa-key text-base"></i>
                                        </button>
                                        <form action="{{ route('super-admin.delete-user', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure? This checks for transactions.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-500 hover:text-rose-600 transition p-2">
                                                <i class="fa-solid fa-trash-can text-base"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-gray-400">No departmental accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- List 2: NGOs -->
            <div id="list-content-ngo" class="overflow-x-auto flex-1 list-panel hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">NGO Name</th>
                            <th class="px-6 py-4">Reg No</th>
                            <th class="px-6 py-4">Contact Details</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($ngos as $ngo)
                            @php $nUser = \App\Models\User::where('email', $ngo->email)->first(); @endphp
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-gray-800">{{ $ngo->ngo_name }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $ngo->office_address }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-600">
                                    {{ $ngo->registration_no }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <div>{{ $ngo->contact_person }} ({{ $ngo->mobile }})</div>
                                    <div class="text-[10px] font-mono text-gray-500 mt-0.5">{{ $ngo->email }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($nUser)
                                        <form action="{{ route('super-admin.toggle-status', $nUser->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-0.5 rounded-full text-xs font-bold transition duration-150 uppercase tracking-wider {{ $nUser->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200' : 'bg-red-100 text-red-800 border border-red-200 hover:bg-red-200' }}">
                                                {{ $nUser->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400 text-xs">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($nUser)
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" 
                                                    onclick="openChangePasswordModal('{{ $nUser->id }}', '{{ $nUser->name }}')" 
                                                    class="text-amber-500 hover:text-amber-600 transition p-2"
                                                    title="Change Password">
                                                <i class="fa-solid fa-key text-base"></i>
                                            </button>
                                            <form action="{{ route('super-admin.delete-user', $nUser->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-500 hover:text-rose-600 transition p-2">
                                                    <i class="fa-solid fa-trash-can text-base"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-400">No NGOs onboarded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- List 3: Employers -->
            <div id="list-content-emp" class="overflow-x-auto flex-1 list-panel hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Employer/Factory</th>
                            <th class="px-6 py-4">Reg No</th>
                            <th class="px-6 py-4">Contact Details</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($employers as $employer)
                            @php $eUser = \App\Models\User::where('email', $employer->email)->first(); @endphp
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-gray-800">{{ $employer->employer_name }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $employer->office_address }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-600">
                                    {{ $employer->registration_no }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <div>{{ $employer->contact_person }} ({{ $employer->mobile }})</div>
                                    <div class="text-[10px] font-mono text-gray-500 mt-0.5">{{ $employer->email }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($eUser)
                                        <form action="{{ route('super-admin.toggle-status', $eUser->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-0.5 rounded-full text-xs font-bold transition duration-150 uppercase tracking-wider {{ $eUser->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200' : 'bg-red-100 text-red-800 border border-red-200 hover:bg-red-200' }}">
                                                {{ $eUser->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400 text-xs">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($eUser)
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" 
                                                    onclick="openChangePasswordModal('{{ $eUser->id }}', '{{ $eUser->name }}')" 
                                                    class="text-amber-500 hover:text-amber-600 transition p-2"
                                                    title="Change Password">
                                                <i class="fa-solid fa-key text-base"></i>
                                            </button>
                                            <form action="{{ route('super-admin.delete-user', $eUser->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-500 hover:text-rose-600 transition p-2">
                                                    <i class="fa-solid fa-trash-can text-base"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-400">No Employers onboarded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- List 4: Contractors -->
            <div id="list-content-cnt" class="overflow-x-auto flex-1 list-panel hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Contractor/Thekedar</th>
                            <th class="px-6 py-4">License No</th>
                            <th class="px-6 py-4">Contact Details</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($contractors as $contractor)
                            @php $cUser = \App\Models\User::where('email', $contractor->email)->first(); @endphp
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-gray-800">{{ $contractor->contractor_name }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $contractor->office_address }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-600">
                                    {{ $contractor->license_no }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <div>Mobile: {{ $contractor->mobile }}</div>
                                    <div class="text-[10px] font-mono text-gray-500 mt-0.5">{{ $contractor->email }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($cUser)
                                        <form action="{{ route('super-admin.toggle-status', $cUser->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-0.5 rounded-full text-xs font-bold transition duration-150 uppercase tracking-wider {{ $cUser->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200' : 'bg-red-100 text-red-800 border border-red-200 hover:bg-red-200' }}">
                                                {{ $cUser->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400 text-xs">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($cUser)
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" 
                                                    onclick="openChangePasswordModal('{{ $cUser->id }}', '{{ $cUser->name }}')" 
                                                    class="text-amber-500 hover:text-amber-600 transition p-2"
                                                    title="Change Password">
                                                <i class="fa-solid fa-key text-base"></i>
                                            </button>
                                            <form action="{{ route('super-admin.delete-user', $cUser->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-500 hover:text-rose-600 transition p-2">
                                                    <i class="fa-solid fa-trash-can text-base"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-400">No Contractors onboarded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeChangePasswordModal()"></div>
        
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl w-full max-w-md p-6 border border-gray-100 shadow-2xl transition-all duration-300 transform scale-95 opacity-0">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-key text-amber-500"></i>
                    <span>Reset Account Password</span>
                </h3>
                <button type="button" onclick="closeChangePasswordModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <form id="changePasswordForm" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wide mb-1">User Account</label>
                    <div id="changePasswordUserName" class="px-3 py-2 rounded-xl bg-gray-50 border border-gray-100 text-xs font-bold text-gray-700"></div>
                </div>
                <div>
                    <label for="new_password_input" class="block text-[10px] font-black text-gray-500 uppercase tracking-wide mb-1">New Password *</label>
                    <input type="password" id="new_password_input" name="new_password" required minlength="6" placeholder="Enter at least 6 characters" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition" />
                </div>
                
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeChangePasswordModal()" class="px-4 py-2 border border-gray-105 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div id="editDeptModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeEditDeptModal()"></div>
        
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl w-full max-w-md p-6 border border-gray-100 shadow-2xl transition-all duration-300 transform scale-95 opacity-0">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-building-columns text-indigo-600"></i>
                    <span>Edit Department Details</span>
                </h3>
                <button type="button" onclick="closeEditDeptModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <form id="editDeptForm" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="edit_dept_name" class="block text-[10px] font-black text-gray-500 uppercase tracking-wide mb-1">Department Name *</label>
                    <input type="text" id="edit_dept_name" name="department_name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>
                <div>
                    <label for="edit_dept_parent" class="block text-[10px] font-black text-gray-500 uppercase tracking-wide mb-1">Parent Department Name</label>
                    <input type="text" id="edit_dept_parent" name="parent_department_name" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>
                <div>
                    <label for="edit_dept_address" class="block text-[10px] font-black text-gray-500 uppercase tracking-wide mb-1">Office Address *</label>
                    <input type="text" id="edit_dept_address" name="office_address" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>
                <div>
                    <label for="edit_dept_email" class="block text-[10px] font-black text-gray-500 uppercase tracking-wide mb-1">Department Email *</label>
                    <input type="email" id="edit_dept_email" name="email" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wide mb-1">Department Logo</label>
                    <input type="file" name="logo" accept="image/*" class="w-full px-3 py-1.5 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                    <p class="text-[10px] text-gray-400 mt-1 font-semibold">Supported formats: JPEG, PNG, JPG, GIF (Max 2MB). Leave empty to keep existing.</p>
                </div>
                
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeEditDeptModal()" class="px-4 py-2 border border-gray-105 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function switchListTab(tabId) {
        // Toggle tabs styles
        const tabs = ['dept', 'ngo', 'emp', 'cnt'];
        tabs.forEach(t => {
            const btn = document.getElementById('list-tab-' + t);
            if (btn) {
                btn.className = "px-3 py-1.5 rounded-lg hover:text-gray-800 transition";
            }
        });
        const activeBtn = document.getElementById('list-tab-' + tabId);
        if (activeBtn) {
            activeBtn.className = "px-3 py-1.5 rounded-lg bg-white text-gray-850 font-extrabold shadow-sm transition";
        }

        // Toggle panel visibility
        const panels = document.querySelectorAll('.list-panel');
        panels.forEach(p => p.classList.add('hidden'));
        const activePanel = document.getElementById('list-content-' + tabId);
        if (activePanel) {
            activePanel.classList.remove('hidden');
        }
    }

    function openChangePasswordModal(userId, userName) {
        const modal = document.getElementById('changePasswordModal');
        const modalContent = modal.querySelector('.relative');
        const nameDiv = document.getElementById('changePasswordUserName');
        const form = document.getElementById('changePasswordForm');
        
        nameDiv.innerText = userName;
        form.action = "{{ route('super-admin.change-password', ':id') }}".replace(':id', userId);
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    
    function closeChangePasswordModal() {
        const modal = document.getElementById('changePasswordModal');
        const modalContent = modal.querySelector('.relative');
        
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            document.getElementById('new_password_input').value = '';
        }, 300);
    }

    function openEditDeptModal(deptId, deptName, deptParent, deptAddress, deptEmail) {
        const modal = document.getElementById('editDeptModal');
        const modalContent = modal.querySelector('.relative');
        const form = document.getElementById('editDeptForm');
        
        document.getElementById('edit_dept_name').value = deptName;
        document.getElementById('edit_dept_parent').value = deptParent || '';
        document.getElementById('edit_dept_address').value = deptAddress;
        document.getElementById('edit_dept_email').value = deptEmail;
        
        form.action = "{{ route('super-admin.update-department', ':id') }}".replace(':id', deptId);
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    
    function closeEditDeptModal() {
        const modal = document.getElementById('editDeptModal');
        const modalContent = modal.querySelector('.relative');
        
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
</script>
@endsection


