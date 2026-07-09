@extends('layouts.admin')

@section('title', 'Manage Employers')

@section('content')
<div class="space-y-8">
    <!-- Header Console -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Manage Employers</h2>
            <p class="text-gray-500 text-xs mt-1">Onboard factory owners / Nokri dene wale and track their registered status.</p>
        </div>
        <div>
            <a href="{{ route('ngo.index') }}" class="text-sm font-bold text-purple-700 hover:text-purple-600 transition flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        
        <!-- Column 1: Onboard Employer Form -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-6 bg-gradient-to-b from-white to-gray-50/20">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-industry text-purple-700"></i>
                    <span>Onboard Employer</span>
                </h3>
                <p class="text-xs text-gray-400 mt-1">Register new Employers and automatically create their login credentials.</p>
            </div>

            <form action="{{ route('ngo.create-employer') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="employer_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Employer / Factory Name *</label>
                    <input type="text" name="employer_name" id="employer_name" required placeholder="e.g. Acme Industries"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="registration_no" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Reg No / License *</label>
                        <input type="text" name="registration_no" id="registration_no" required placeholder="REG12345"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                    </div>
                    <div>
                        <label for="mobile" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Contact Mobile *</label>
                        <input type="text" name="mobile" id="mobile" required placeholder="e.g. 9876543210"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                    </div>
                </div>

                <div>
                    <label for="contact_person" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Contact Person Name *</label>
                    <input type="text" name="contact_person" id="contact_person" required placeholder="e.g. John Doe"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                </div>

                <div>
                    <label for="office_address" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Office/Factory Address *</label>
                    <textarea name="office_address" id="office_address" required rows="2" placeholder="Full postal address..."
                              class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition"></textarea>
                </div>

                <div class="border-t border-gray-100 pt-4 space-y-4">
                    <h4 class="text-xs font-black text-purple-700 uppercase tracking-wider">Login Credentials</h4>
                    
                    <div>
                        <label for="email" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Login Email *</label>
                        <input type="email" name="email" id="email" required placeholder="employer@example.com"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                    </div>

                    <div>
                        <label for="password" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Password *</label>
                        <input type="password" name="password" id="password" required placeholder="Min 6 characters" minlength="6"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                    </div>
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-purple-700 text-white font-bold rounded-xl text-sm shadow-md hover:bg-purple-600 hover:shadow-lg transition">
                    Register & Create Account
                </button>
            </form>
        </div>

        <!-- Column 2 & 3: List Onboarded Employers -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between">
            
            <!-- Search & Filter Header -->
            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50/50">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Onboarded Employers</h3>
                    <p class="text-xs text-gray-400 mt-1">List of all Employers registered by your NGO.</p>
                </div>
                <form action="{{ route('ngo.employers') }}" method="GET" class="w-full sm:w-72">
                    <div class="relative">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search by name..."
                               class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                        <div class="absolute left-3 top-2.5 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto flex-1">
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
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-gray-800">{{ $employer->employer_name }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $employer->office_address }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                        {{ $employer->registration_no }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-gray-800">{{ $employer->contact_person }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $employer->mobile }}</div>
                                    <div class="text-[10px] font-mono text-gray-500 mt-0.5">{{ $employer->email }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('ngo.toggle-employer-status', $employer->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" 
                                                class="px-2.5 py-0.5 rounded-full text-xs font-bold transition duration-150 uppercase tracking-wider
                                                {{ ($employer->user && $employer->user->is_active) ? 'bg-emerald-100 text-emerald-800 border border-emerald-200 hover:bg-emerald-200' : 'bg-red-100 text-red-800 border border-red-200 hover:bg-red-200' }}">
                                            {{ ($employer->user && $employer->user->is_active) ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('ngo.delete-employer', $employer->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this Employer account? This checks for active transactions.')">
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
                                <td colspan="5" class="text-center py-8 text-gray-400">No Employers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-6 border-t border-gray-100 bg-gray-50/30">
                {{ $employers->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
