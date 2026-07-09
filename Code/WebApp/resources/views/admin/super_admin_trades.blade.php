@extends('layouts.admin')

@section('title', 'Manage Trades & Job Profiles')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header Console -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Manage Trades & Job Profiles</h2>
            <p class="text-gray-500 text-xs mt-1">Configure, edit, and manage beneficiary trades/job profiles selectable in the web & mobile registration forms.</p>
        </div>
        <div>
            <a href="{{ route('super-admin.index') }}" class="text-sm font-bold text-orange-600 hover:text-orange-500 transition flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Error/Success Alerts -->
    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-red-950 text-xs flex gap-3 shadow-inner">
            <i class="fa-solid fa-triangle-exclamation text-base text-red-600 shrink-0"></i>
            <div>
                <span class="font-bold">Errors occurred:</span>
                <ul class="list-disc list-inside mt-1 font-semibold font-sans">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-950 text-xs flex gap-3 shadow-inner">
            <i class="fa-solid fa-circle-check text-base text-emerald-600 shrink-0"></i>
            <span class="font-bold font-sans">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        
        <!-- Column 1: Create Trade Form -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-6 bg-gradient-to-b from-white to-gray-50/20">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-folder-plus text-orange-655 text-orange-600"></i>
                    <span>Register New Trade</span>
                </h3>
                <p class="text-xs text-gray-400 mt-1">Add a new job profile to the system dropdown.</p>
            </div>

            <form action="{{ route('super-admin.create-trade') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Trade / Job Profile Name *</label>
                    <input type="text" name="name" id="name" required placeholder="e.g. Carpenter, Plumber, Mason" value="{{ old('name') }}"
                           class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition font-bold" />
                </div>

                <div>
                    <label for="category" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Category *</label>
                    <input type="text" name="category" id="category" required placeholder="e.g. Construction, Craftsmanship, Woodworking" value="{{ old('category') }}"
                           class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition font-bold" />
                </div>

                <div>
                    <label for="description" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Description</label>
                    <textarea name="description" id="description" rows="4" placeholder="Brief description of the job profile and required skills..."
                              class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition font-bold">{{ old('description') }}</textarea>
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-orange-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-orange-500 hover:shadow-lg transition">
                    Create & Enable Trade
                </button>
            </form>
        </div>

        <!-- Column 2 & 3: List Trades -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between">
            
            <!-- Search Header -->
            <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gray-50/50">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Configured Trades / Job Profiles</h3>
                    <p class="text-xs text-gray-400 mt-1">List of all job profiles currently accessible on the portal.</p>
                </div>
                <div class="flex items-center gap-3">
                    <form action="{{ route('super-admin.trades') }}" method="GET" class="w-full sm:w-60">
                        <div class="relative">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search trades or categories..."
                                   class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-500 transition font-semibold" />
                            <div class="absolute left-3 top-2.5 text-gray-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Trade Name</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($trades as $trade)
                            @php
                                $isActive = !isset($trade->is_active) || $trade->is_active;
                                $tradeId = $trade->_id ?? $trade->id;
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4 font-bold text-xs text-gray-800">
                                    <button type="button" 
                                            data-id="{{ $tradeId }}"
                                            data-name="{{ $trade->name }}"
                                            data-category="{{ $trade->category }}"
                                            data-description="{{ $trade->description ?? '' }}"
                                            data-active="{{ $isActive ? 'true' : 'false' }}"
                                            onclick="openEditTradeModal(this)" 
                                            class="text-left font-black text-orange-600 hover:text-orange-500 hover:underline">
                                        {{ $trade->name }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-gray-700">
                                    {{ $trade->category }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                    {{ Str::limit($trade->description ?? 'N/A', 150) }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    @if($isActive)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase">Active</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-100 uppercase">Deactivated</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" 
                                                data-id="{{ $tradeId }}"
                                                data-name="{{ $trade->name }}"
                                                data-category="{{ $trade->category }}"
                                                data-description="{{ $trade->description ?? '' }}"
                                                data-active="{{ $isActive ? 'true' : 'false' }}"
                                                onclick="openEditTradeModal(this)" 
                                                class="text-orange-650 hover:text-orange-500 transition p-1.5" title="Edit Trade">
                                            <i class="fa-solid fa-pen-to-square text-base text-orange-600"></i>
                                        </button>
                                        <form action="{{ route('super-admin.delete-trade', $tradeId) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this Trade profile? It will fail if any beneficiaries are registered under this trade.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-500 hover:text-rose-600 transition p-1.5">
                                                <i class="fa-solid fa-trash-can text-base"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-400">No configured trades or job profiles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($trades->hasPages())
                <div class="p-6 border-t border-gray-100 bg-gray-50/30">
                    {{ $trades->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Edit Trade Modal -->
<div id="editTradeModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full border border-gray-100 shadow-xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-square-pen text-orange-600"></i>
                <span>Edit Trade Details</span>
            </h3>
            <button type="button" onclick="closeEditTradeModal()" class="text-gray-400 hover:text-gray-500 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form id="editTradeForm" action="" method="POST" class="p-6 space-y-4">
            @csrf
            
            <div>
                <label for="edit_name" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Trade / Job Profile Name *</label>
                <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition font-bold" />
            </div>

            <div>
                <label for="edit_category" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Category *</label>
                <input type="text" name="category" id="edit_category" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition font-bold" />
            </div>

            <div>
                <label for="edit_description" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Description</label>
                <textarea name="description" id="edit_description" rows="4" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition font-bold"></textarea>
            </div>

            <div>
                <label for="edit_is_active" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Status</label>
                <select name="is_active" id="edit_is_active" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 transition font-bold">
                    <option value="1">Active (Selectable in Registration Forms)</option>
                    <option value="0">Deactivated (Hidden from Selection)</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeEditTradeModal()" class="px-4 py-2 border border-gray-200 text-gray-500 text-xs font-bold rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-orange-600 text-white text-xs font-bold rounded-lg hover:bg-orange-500 transition shadow">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openEditTradeModal(btn) {
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');
        const category = btn.getAttribute('data-category');
        const description = btn.getAttribute('data-description');
        const isActive = btn.getAttribute('data-active') === 'true';

        const modal = document.getElementById('editTradeModal');
        const form = document.getElementById('editTradeForm');
        
        form.action = "{{ route('super-admin.update-trade', ':id') }}".replace(':id', id);
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_category').value = category;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_is_active').value = isActive ? "1" : "0";

        modal.classList.remove('hidden');
    }

    function closeEditTradeModal() {
        const modal = document.getElementById('editTradeModal');
        modal.classList.add('hidden');
    }
</script>
@endsection
