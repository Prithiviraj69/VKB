@extends('layouts.admin')

@php
    $title = 'Scheme Applications';
    $desc = 'Verify, approve, or reject welfare scheme applications submitted by registered workers.';
    if (isset($status)) {
        if ($status === 'pending') {
            $title = 'Pending Scheme Applications';
            $desc = 'Verify and review scheme applications awaiting clearance.';
        } elseif ($status === 'approved') {
            $title = 'Approved Scheme Applications';
            $desc = 'List of approved applications with active grants.';
        } elseif ($status === 'rejected') {
            $title = 'Rejected Scheme Applications';
            $desc = 'List of applications declined/returned for corrections.';
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
            <form action="{{ route($role . '.scheme-applications') }}" method="GET" class="flex gap-4">
                <input type="hidden" name="status" value="{{ $status ?? 'pending' }}" />
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by applicant name or scheme title..." 
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
                        <th class="px-6 py-4">Application ID</th>
                        <th class="px-6 py-4">Applicant Name</th>
                        <th class="px-6 py-4">Scheme Title</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Submitted At</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                    @forelse($applications as $app)
                        @php $app = (array) $app; @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $app['id'] ?? $app['_id'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $app['worker_name'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-indigo-600">{{ $app['scheme_title'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-xs">
                                <span class="px-2.5 py-0.5 font-bold rounded-full uppercase tracking-wider
                                    @if($app['status'] === 'approved') bg-emerald-50 text-emerald-700 border border-emerald-200
                                    @elseif($app['status'] === 'verified') bg-blue-50 text-blue-700 border border-blue-200
                                    @elseif($app['status'] === 'rejected') bg-red-50 text-red-700 border border-red-200
                                    @else bg-amber-50 text-amber-700 border border-amber-200
                                    @endif">
                                    {{ $app['status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">{{ isset($app['created_at']) ? explode(' ', $app['created_at'])[0] : 'N/A' }}</td>
                            <td class="px-6 py-4 text-center">
                                @if(($status ?? 'pending') === 'pending')
                                    <a href="#" 
                                       class="bg-{{ $themeColor }} text-white text-xs font-bold px-4 py-2 rounded-lg hover:opacity-90 transition shadow-sm">
                                        Review App
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 font-bold">No Action Needed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-400">No scheme applications found matching this status.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        @if(count($applications) > 0)
            <div class="p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <div class="text-xs text-gray-500 font-bold">
                    Showing {{ $applications->firstItem() }} to {{ $applications->lastItem() }} of {{ $applications->total() }} records.
                </div>
                <div>
                    {{ $applications->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
