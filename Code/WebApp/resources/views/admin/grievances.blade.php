@extends('layouts.admin')

@php
    $title = 'Grievances';
    $desc = 'Resolve and track grievances filed by workers regarding registration, scheme applications, or subscription logs.';
    if (isset($status)) {
        if ($status === 'new') {
            $title = 'New Grievances';
            $desc = 'Unopened complaints requiring initial review and categorization.';
        } elseif ($status === 'pending') {
            $title = 'Pending Grievances';
            $desc = 'Complaints currently under investigation or in progress.';
        } elseif ($status === 'disposed') {
            $title = 'Disposed Grievances';
            $desc = 'Complaints that have been resolved and closed.';
        }
    }
    $role = Auth::user()->role;
    $themeColor = $role === 'verifier' ? 'verifier' : 'approver';
@endphp

@section('title', $title)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">{{ $title }}</h2>
            <p class="text-gray-500 text-xs mt-1">{{ $desc }}</p>
        </div>
        <div>
            <a href="{{ $role === 'verifier' ? route('verifier.index') : route('approver.index') }}" 
               class="bg-gray-200 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-gray-300 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Main List Container -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between">
        <!-- Search bar -->
        <div class="p-6 bg-gray-50/50 border-b border-gray-100">
            <form action="{{ route($role . '.grievances') }}" method="GET" class="flex gap-4">
                <input type="hidden" name="status" value="{{ $status ?? 'new' }}" />
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, code or description..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-{{ $themeColor }} focus:border-{{ $themeColor }} transition" />
                </div>
                <button type="submit" class="bg-{{ $themeColor }} text-white font-bold text-sm px-6 py-2.5 rounded-xl hover:opacity-90 transition duration-150">
                    Search
                </button>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Grievance ID</th>
                        <th class="px-6 py-4">Worker Name</th>
                        <th class="px-6 py-4">Issue Description</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Filed At</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                    @forelse($grievances as $grv)
                        @php $grv = (array) $grv; @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $grv['id'] ?? $grv['_id'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $grv['worker_name'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-xs font-semibold max-w-sm truncate" title="{{ $grv['description'] ?? 'N/A' }}">{{ $grv['description'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-xs">
                                <span class="px-2.5 py-0.5 font-bold rounded-full uppercase tracking-wider
                                    @if($grv['status'] === 'disposed') bg-emerald-50 text-emerald-700 border border-emerald-200
                                    @elseif($grv['status'] === 'pending') bg-amber-50 text-amber-700 border border-amber-200
                                    @else bg-blue-50 text-blue-700 border border-blue-200
                                    @endif">
                                    {{ $grv['status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">{{ isset($grv['created_at']) ? explode(' ', $grv['created_at'])[0] : 'N/A' }}</td>
                            <td class="px-6 py-4 text-center">
                                @if(($status ?? 'new') !== 'disposed')
                                    <a href="#" 
                                       class="bg-{{ $themeColor }} text-white text-xs font-bold px-4 py-2 rounded-lg hover:opacity-90 transition shadow-sm">
                                        Take Action
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 font-bold">Resolved</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-400">No grievances found matching this status.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        @if(count($grievances) > 0)
            <div class="p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <div class="text-xs text-gray-500 font-bold">
                    Showing {{ $grievances->firstItem() }} to {{ $grievances->lastItem() }} of {{ $grievances->total() }} records.
                </div>
                <div>
                    {{ $grievances->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
