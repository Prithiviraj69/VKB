@extends('layouts.admin')

@section('title', 'Manage NGO Staff')

@section('content')
<div class="space-y-8">
    <!-- Header Console -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Manage NGO Staff</h2>
            <p class="text-gray-500 text-xs mt-1">Onboard and manage internal Verifier and Approver accounts for your NGO.</p>
        </div>
        <div>
            <a href="{{ route('ngo.index') }}" class="text-sm font-bold text-ngo hover:text-purple-600 transition flex items-center gap-2">
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
                    <i class="fa-solid fa-user-plus text-ngo"></i>
                    <span>Onboard NGO Staff</span>
                </h3>
                <p class="text-xs text-gray-400 mt-1">Add internal NGO staff to handle scheme applications and approvals.</p>
            </div>

            <!-- Form Tabs selector -->
            <div class="flex border-b border-gray-100 text-xs font-bold text-gray-400 overflow-x-auto pb-1 gap-2">
                <button type="button" onclick="switchFormTab('verifier')" id="form-tab-verifier" class="px-3 py-1.5 rounded-lg bg-purple-50 text-ngo font-extrabold transition">Verifier</button>
                <button type="button" onclick="switchFormTab('approver')" id="form-tab-approver" class="px-3 py-1.5 rounded-lg hover:bg-gray-100 transition">Approver</button>
            </div>

            <!-- Form 1: Verifier Onboarding -->
            <form action="{{ route('ngo.create-verifier') }}" method="POST" id="form-content-verifier" class="space-y-4 form-panel">
                @csrf
                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="Verifier Name"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Designation *</label>
                        <input type="text" name="designation" required placeholder="Auditor / Inspector"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Mobile *</label>
                        <input type="text" name="mobile" required placeholder="9876543210"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Login Email *</label>
                    <input type="email" name="email" required placeholder="verifier@ngo.org"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Password *</label>
                    <input type="password" name="password" required placeholder="Min 6 characters" minlength="6"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-ngo text-white font-bold rounded-xl text-sm shadow-md hover:bg-purple-700 hover:shadow-lg transition">
                    <i class="fa-solid fa-circle-check mr-1"></i> Register Verifier
                </button>
            </form>

            <!-- Form 2: Approver Onboarding -->
            <form action="{{ route('ngo.create-approver') }}" method="POST" id="form-content-approver" class="space-y-4 form-panel hidden">
                @csrf
                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="Approver Name"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Designation *</label>
                        <input type="text" name="designation" required placeholder="Director / Head"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Mobile *</label>
                        <input type="text" name="mobile" required placeholder="9876543210"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Login Email *</label>
                    <input type="email" name="email" required placeholder="approver@ngo.org"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Password *</label>
                    <input type="password" name="password" required placeholder="Min 6 characters" minlength="6"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-ngo text-white font-bold rounded-xl text-sm shadow-md hover:bg-purple-700 hover:shadow-lg transition">
                    <i class="fa-solid fa-gavel mr-1"></i> Register Approver
                </button>
            </form>
        </div>

        <!-- Column 2 & 3: List Onboarded Users -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between">
            
            <!-- Search & Filter Header -->
            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50/50">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">NGO Staff Registry</h3>
                    <p class="text-xs text-gray-400 mt-1">List of all Verifiers and Approvers created by your NGO.</p>
                </div>
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
                                    @if($user->role === 'ngo-verifier')
                                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase bg-purple-50 text-ngo border border-purple-200">
                                            ✅ Verifier
                                        </span>
                                    @elseif($user->role === 'ngo-approver')
                                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase bg-purple-50 text-ngo border border-purple-200">
                                            ⚖️ Approver
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase bg-gray-50 text-gray-700 border border-gray-200">
                                            {{ $user->role }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('ngo.toggle-status', $user->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" 
                                                class="px-2.5 py-0.5 rounded-full text-xs font-bold transition duration-150 uppercase tracking-wider
                                                {{ $user->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200' : 'bg-red-100 text-red-800 border border-red-200 hover:bg-red-200' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('ngo.delete-user', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this staff user?')">
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
                                <td colspan="5" class="text-center py-8 text-gray-400">No NGO staff onboarded.</td>
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
            tabVerifier.className = 'px-3 py-1.5 rounded-lg bg-purple-50 text-ngo font-extrabold transition';
            tabApprover.className = 'px-3 py-1.5 rounded-lg hover:bg-gray-100 transition';
            
            formVerifier.classList.remove('hidden');
            formApprover.classList.add('hidden');
        } else {
            tabApprover.className = 'px-3 py-1.5 rounded-lg bg-purple-50 text-ngo font-extrabold transition';
            tabVerifier.className = 'px-3 py-1.5 rounded-lg hover:bg-gray-100 transition';
            
            formApprover.classList.remove('hidden');
            formVerifier.classList.add('hidden');
        }
    }
</script>
@endsection
