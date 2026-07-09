@extends('layouts.admin')

@section('title', 'Manage Schemes')

@section('content')
<div class="space-y-8">
    <!-- Header Console -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Manage Welfare Schemes</h2>
            <p class="text-gray-500 text-xs mt-1">Configure schemes, define descriptions, and track scheme configurations.</p>
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
        
        <!-- Column 1: Create Scheme Form -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-6 bg-gradient-to-b from-white to-gray-50/20">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-shapes text-purple-700"></i>
                    <span>Register New Scheme</span>
                </h3>
                <p class="text-xs text-gray-400 mt-1">Add a new welfare scheme for beneficiaries/workers to apply.</p>
            </div>

            <form action="{{ route('ngo.create-scheme') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="title" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Scheme Title *</label>
                    <input type="text" name="title" id="title" required placeholder="e.g. Kaushalya Vikas Yojana"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                </div>

                <div>
                    <label for="url" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Direct Portal URL *</label>
                    <input type="text" name="url" id="url" required placeholder="https://scheme-portal.gov.in"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition" />
                </div>

                <div>
                    <label for="description" class="block text-[10px] font-black text-gray-600 uppercase mb-1">Description / Benefits *</label>
                    <textarea name="description" id="description" required rows="6" placeholder="Provide full details of the scheme, benefits, eligibility criteria..."
                              class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 transition"></textarea>
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-purple-700 text-white font-bold rounded-xl text-sm shadow-md hover:bg-purple-600 hover:shadow-lg transition">
                    Create & Publish Scheme
                </button>
            </form>
        </div>

        <!-- Column 2 & 3: List Schemes -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between">
            
            <!-- Search & Filter Header -->
            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50/50">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Published Schemes</h3>
                    <p class="text-xs text-gray-400 mt-1">List of all active welfare schemes.</p>
                </div>
                <form action="{{ route('ngo.schemes') }}" method="GET" class="w-full sm:w-72">
                    <div class="relative">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search schemes..."
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
                            <th class="px-6 py-4 w-1/4">Scheme Title</th>
                            <th class="px-6 py-4 w-1/4">Direct URL</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($schemes as $scheme)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4 font-bold text-xs text-gray-800">
                                    {{ $scheme->title }}
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-purple-700 truncate max-w-[150px]">
                                    @if(!empty($scheme->url))
                                        <a href="{{ $scheme->url }}" target="_blank" class="hover:underline flex items-center gap-1">
                                            <span>{{ Str::limit($scheme->url, 25) }}</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                        </a>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                    {{ Str::limit($scheme->description, 200) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('ngo.delete-scheme', $scheme->_id ?? $scheme->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this Scheme? This checks if any worker has applied to it.')">
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
                                <td colspan="4" class="text-center py-8 text-gray-400">No Welfare Schemes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-6 border-t border-gray-100 bg-gray-50/30">
                {{ $schemes->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
