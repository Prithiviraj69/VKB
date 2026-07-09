@extends('layouts.admin')

@section('title', 'Manage Department Users')

@section('content')
<div class="space-y-8">
    <!-- Header Console -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Manage Department Users</h2>
            <p class="text-gray-500 text-xs mt-1">Onboard and manage internal Verifiers and Approvers for your department.</p>
        </div>
        <div>
            <a href="{{ route('govt-dept-admin.index') }}" class="text-sm font-bold text-indigo-700 hover:text-indigo-600 transition flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        
        <!-- Column 1: Onboard Forms -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-6 bg-gradient-to-b from-white to-gray-50/20">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-indigo-700"></i>
                    <span>Onboard User</span>
                </h3>
                <p class="text-xs text-gray-400 mt-1">Add internal department staff with appropriate workflow roles.</p>
            </div>

            <!-- Form Tabs selector -->
            <div class="flex border-b border-gray-100 text-xs font-bold text-gray-400 overflow-x-auto pb-1 gap-2">
                <button type="button" onclick="switchFormTab('verifier')" id="form-tab-verifier" class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 font-extrabold transition">Verifier</button>
                <button type="button" onclick="switchFormTab('approver')" id="form-tab-approver" class="px-3 py-1.5 rounded-lg hover:bg-gray-100 transition">Approver</button>
            </div>

            <!-- Form 1: Verifier Onboarding -->
            <form action="{{ route('govt-dept-admin.create-verifier') }}" method="POST" id="form-content-verifier" class="space-y-4 form-panel">
                @csrf
                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="Verifier Name"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Designation *</label>
                        <input type="text" name="designation" required placeholder="Auditor / Inspector"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Mobile *</label>
                        <input type="text" name="mobile" required placeholder="9876543210"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Login Email *</label>
                    <input type="email" name="email" required placeholder="verifier@gov.in"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Password *</label>
                    <input type="password" name="password" required placeholder="Min 6 characters" minlength="6"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-indigo-700 text-white font-bold rounded-xl text-sm shadow-md hover:bg-indigo-600 hover:shadow-lg transition">
                    <i class="fa-solid fa-circle-check mr-1"></i> Register Verifier
                </button>
            </form>

            <!-- Form 2: Approver Onboarding -->
            <form action="{{ route('govt-dept-admin.create-approver') }}" method="POST" id="form-content-approver" class="space-y-4 form-panel hidden">
                @csrf
                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="Approver Name"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Designation *</label>
                        <input type="text" name="designation" required placeholder="Director / Commissioner"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Mobile *</label>
                        <input type="text" name="mobile" required placeholder="9876543210"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Login Email *</label>
                    <input type="email" name="email" required placeholder="approver@gov.in"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Password *</label>
                    <input type="password" name="password" required placeholder="Min 6 characters" minlength="6"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-indigo-700 text-white font-bold rounded-xl text-sm shadow-md hover:bg-indigo-600 hover:shadow-lg transition">
                    <i class="fa-solid fa-gavel mr-1"></i> Register Approver
                </button>
            </form>
        </div>

        <!-- Column 2 & 3: List Onboarded Users -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between">
            
            <!-- Search & Filter Header -->
            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50/50">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Department Staff</h3>
                    <p class="text-xs text-gray-400 mt-1">List of all Verifiers and Approvers created by your account.</p>
                </div>
                <form action="{{ route('govt-dept-admin.users') }}" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                    @if($roleFilter)
                        <input type="hidden" name="role" value="{{ $roleFilter }}" />
                    @endif
                    <div class="relative w-full sm:w-60">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search by name/email..."
                               class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                        <div class="absolute left-3 top-2.5 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </div>
                    </div>
                    <button type="submit" class="bg-indigo-700 hover:bg-indigo-600 text-white font-bold text-xs px-4 py-2 rounded-xl transition">
                        Search
                    </button>
                </form>
            </div>

            <!-- Role Filter tabs -->
            <div class="px-6 py-3 border-b border-gray-100 flex gap-2 text-xs">
                <a href="{{ route('govt-dept-admin.users') }}?role=&search={{ $search }}" 
                   class="px-3 py-1.5 rounded-lg font-bold transition {{ !$roleFilter ? 'bg-gray-100 text-gray-800' : 'text-gray-400 hover:bg-gray-50' }}">
                    All Staff
                </a>
                <a href="{{ route('govt-dept-admin.users') }}?role=dept-verifier&search={{ $search }}" 
                   class="px-3 py-1.5 rounded-lg font-bold transition {{ $roleFilter === 'dept-verifier' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-400 hover:bg-gray-50' }}">
                    Verifiers Only
                </a>
                <a href="{{ route('govt-dept-admin.users') }}?role=dept-approver&search={{ $search }}" 
                   class="px-3 py-1.5 rounded-lg font-bold transition {{ $roleFilter === 'dept-approver' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-400 hover:bg-gray-50' }}">
                    Approvers Only
                </a>
            </div>

            <!-- Users Table -->
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Name / Designation</th>
                            <th class="px-6 py-4">Contact Info</th>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-gray-800">{{ $user->name }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $user->designation }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <div class="font-mono">{{ $user->email }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $user->mobile }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->role === 'dept-verifier')
                                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase bg-green-50 text-green-700 border border-green-200">
                                            ✅ Verifier
                                        </span>
                                    @elseif($user->role === 'dept-approver')
                                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase bg-blue-50 text-blue-700 border border-blue-200">
                                            ⚖️ Approver
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase bg-gray-50 text-gray-700 border border-gray-200">
                                            {{ $user->role }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('govt-dept-admin.toggle-status', $user->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" 
                                                class="px-2.5 py-0.5 rounded-full text-xs font-bold transition duration-150 uppercase tracking-wider
                                                {{ $user->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200' : 'bg-red-100 text-red-800 border border-red-200 hover:bg-red-200' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('govt-dept-admin.delete-user', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This checks for existing transaction records.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-500 hover:text-rose-600 transition p-2">
                                            <i class="fa-solid fa-trash-can text-base"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-400">No department users onboarded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="p-6 border-t border-gray-100 bg-gray-50/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Tabs Script -->
<script>
    function switchFormTab(role) {
        // Toggle tabs classes
        const tabVerifier = document.getElementById('form-tab-verifier');
        const tabApprover = document.getElementById('form-tab-approver');
        
        const formVerifier = document.getElementById('form-content-verifier');
        const formApprover = document.getElementById('form-content-approver');

        if (role === 'verifier') {
            tabVerifier.className = 'px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 font-extrabold transition';
            tabApprover.className = 'px-3 py-1.5 rounded-lg hover:bg-gray-100 transition';
            
            formVerifier.classList.remove('hidden');
            formApprover.classList.add('hidden');
        } else {
            tabApprover.className = 'px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 font-extrabold transition';
            tabVerifier.className = 'px-3 py-1.5 rounded-lg hover:bg-gray-100 transition';
            
            formApprover.classList.remove('hidden');
            formVerifier.classList.add('hidden');
        }
    }
</script>
@endsection
