@extends('layouts.admin')

@section('title', 'Manage Department Schemes')

@section('content')
<div class="space-y-8">
    <!-- Header Console -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Manage Welfare Schemes</h2>
            <p class="text-gray-500 text-xs mt-1">Configure department schemes, define portal landing URLs, and write descriptions.</p>
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
        
        <!-- Column 1: Create Scheme Form -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-6 bg-gradient-to-b from-white to-gray-50/20">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-shapes text-indigo-750"></i>
                    <span>Register New Scheme</span>
                </h3>
                <p class="text-xs text-gray-400 mt-1">Add a new welfare scheme for beneficiaries/workers to apply.</p>
            </div>

            <form action="{{ route('govt-dept-admin.create-scheme') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="title" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Scheme Title *</label>
                    <input type="text" name="title" id="title" required placeholder="e.g. Kaushalya Vikas Yojana"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>

                <div>
                    <label for="url" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Direct Portal URL *</label>
                    <input type="text" name="url" id="url" required placeholder="https://scheme-portal.gov.in"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>

                <div>
                    <label for="description" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Description / Benefits (Max 500 chars) *</label>
                    <textarea name="description" id="description" required rows="6" maxlength="500" placeholder="Provide full details of the scheme, benefits, eligibility criteria..."
                              class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"></textarea>
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-indigo-700 text-white font-bold rounded-xl text-sm shadow-md hover:bg-indigo-600 hover:shadow-lg transition">
                    Create & Publish Scheme
                </button>
            </form>
        </div>

        <!-- Column 2 & 3: List Schemes -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between">
            
            <!-- Search & Filter Header -->
            <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gray-50/50">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Published Schemes</h3>
                    <p class="text-xs text-gray-400 mt-1">List of all active welfare schemes.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <form action="{{ route('govt-dept-admin.schemes') }}" method="GET" class="w-full sm:w-60">
                        <div class="relative">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search schemes..."
                                   class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                            <div class="absolute left-3 top-2.5 text-gray-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </div>
                        </div>
                    </form>
                    <a href="{{ route('govt-dept-admin.export-schemes') }}" class="bg-indigo-600 text-white text-xs font-bold px-3 py-2 rounded-xl hover:bg-indigo-700 transition flex items-center justify-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-file-export"></i>Export CSV
                    </a>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4 w-1/4">Scheme Title</th>
                            <th class="px-6 py-4 w-1/4">Direct URL</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($schemes as $scheme)
                            @php
                                $sTitle = $scheme->title ?? $scheme->name ?? 'N/A';
                                $sUrl = $scheme->url ?? $scheme->apply_url ?? '#';
                                $sDesc = $scheme->description ?? '';
                                $isActive = !isset($scheme->is_active) || $scheme->is_active;
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4 font-bold text-xs text-gray-800">
                                    <button type="button" 
                                            onclick="openEditSchemeModal('{{ $scheme->_id ?? $scheme->id }}', '{{ addslashes($sTitle) }}', '{{ addslashes($sUrl) }}', '{{ addslashes($sDesc) }}', {{ $isActive ? 'true' : 'false' }})" 
                                            class="text-left font-black text-indigo-700 hover:text-indigo-900 hover:underline">
                                        {{ $sTitle }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-indigo-755 truncate max-w-[150px]">
                                    @if($sUrl && $sUrl !== '#')
                                        <a href="{{ $sUrl }}" target="_blank" class="hover:underline flex items-center gap-1">
                                            <span>{{ Str::limit($sUrl, 25) }}</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                        </a>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                    {{ Str::limit($sDesc, 200) }}
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
                                                onclick="openEditSchemeModal('{{ $scheme->_id ?? $scheme->id }}', '{{ addslashes($sTitle) }}', '{{ addslashes($sUrl) }}', '{{ addslashes($sDesc) }}', {{ $isActive ? 'true' : 'false' }})" 
                                                class="text-indigo-650 hover:text-indigo-805 transition p-1.5" title="Edit Scheme">
                                            <i class="fa-solid fa-pen-to-square text-base"></i>
                                        </button>
                                        <form action="{{ route('govt-dept-admin.delete-scheme', $scheme->_id ?? $scheme->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this Scheme? This checks if any worker has applied to it.')">
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
                                <td colspan="5" class="text-center py-8 text-gray-400">No Welfare Schemes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($schemes->hasPages())
                <div class="p-6 border-t border-gray-100 bg-gray-50/30">
                    {{ $schemes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Edit Scheme Modal -->
<div id="editSchemeModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full border border-gray-100 shadow-xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-square-pen text-indigo-650"></i>
                <span>Edit Scheme Details</span>
            </h3>
            <button type="button" onclick="closeEditSchemeModal()" class="text-gray-400 hover:text-gray-500 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form id="editSchemeForm" action="" method="POST" class="p-6 space-y-4">
            @csrf
            
            <div>
                <label for="edit_title" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Scheme Title *</label>
                <input type="text" name="title" id="edit_title" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
            </div>

            <div>
                <label for="edit_url" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Direct Portal URL *</label>
                <input type="text" name="url" id="edit_url" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
            </div>

            <div>
                <label for="edit_description" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Description / Benefits (Max 500 chars) *</label>
                <textarea name="description" id="edit_description" required rows="6" maxlength="500" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"></textarea>
            </div>

            <div>
                <label for="edit_is_active" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Status</label>
                <select name="is_active" id="edit_is_active" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    <option value="1">Active (Visible in Mobile App)</option>
                    <option value="0">Deactivated (Hidden from Mobile App)</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeEditSchemeModal()" class="px-4 py-2 border border-gray-200 text-gray-500 text-xs font-bold rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-650 text-white text-xs font-bold rounded-lg hover:bg-indigo-500 transition shadow">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openEditSchemeModal(id, title, url, description, isActive) {
        const modal = document.getElementById('editSchemeModal');
        const form = document.getElementById('editSchemeForm');
        
        form.action = "{{ route('govt-dept-admin.update-scheme', ':id') }}".replace(':id', id);
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_url').value = url;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_is_active').value = isActive ? "1" : "0";

        modal.classList.remove('hidden');
    }

    function closeEditSchemeModal() {
        const modal = document.getElementById('editSchemeModal');
        modal.classList.add('hidden');
    }
</script>
@endsection
