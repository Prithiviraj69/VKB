@extends('layouts.admin')

@php
    $title = 'Beneficiary Registrations';
    $desc = 'Verify, approve, or reject worker registrations for your NGO.';
    if (isset($status)) {
        if ($status === 'approved') {
            $title = 'Approved Beneficiaries';
            $desc = 'List of all active and approved workers in your NGO.';
        } elseif ($status === 'deactivated') {
            $title = 'Deactivated Beneficiaries';
            $desc = 'List of worker accounts that have been deactivated.';
        }
    }
@endphp

@section('title', 'NGO Approver - ' . $title)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">{{ $title }}</h2>
            <p class="text-gray-500 text-xs mt-1">{{ $desc }}</p>
        </div>
        <div>
            <a href="{{ route('ngo-approver.index') }}" 
               class="bg-gray-200 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-gray-300 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Main List Container -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between">
        <!-- Search bar -->
        <div class="p-6 bg-gray-50/50 border-b border-gray-100">
            <form action="{{ route('ngo-approver.beneficiaries') }}" method="GET" class="flex gap-4">
                <input type="hidden" name="status" value="{{ $status ?? 'pending' }}" />
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search workers by name..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition" />
                </div>
                <button type="submit" class="bg-ngo text-white font-bold text-sm px-6 py-2.5 rounded-xl hover:bg-purple-700 transition duration-150">
                    Search
                </button>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">DOB</th>
                        <th class="px-6 py-4">Gender</th>
                        <th class="px-6 py-4">Trade / Job</th>
                        <th class="px-6 py-4">Address</th>
                        <th class="px-6 py-4">Registered</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                    @forelse($workers as $worker)
                        @php $worker = (array) $worker; @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span>{{ $worker['full_name'] ?? 'N/A' }}</span>
                                    @if(isset($worker['status']) && $worker['status'] === 'rejected')
                                        <span class="px-2 py-0.5 text-[9px] font-bold bg-red-100 text-red-700 border border-red-200 rounded uppercase tracking-wider">
                                            Returned
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                {{ isset($worker['dob']) ? explode(' ', is_string($worker['dob']) ? $worker['dob'] : (isset($worker['dob']->date) ? $worker['dob']->date : 'N/A'))[0] : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 uppercase text-xs">{{ $worker['gender'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $worker['job_profile'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 truncate max-w-xs" title="{{ $worker['aadhaar_address'] ?? $worker['local_address'] ?? 'N/A' }}">
                                {{ $worker['aadhaar_address'] ?? $worker['local_address'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">{{ isset($worker['created_at']) ? explode(' ', $worker['created_at'])[0] : 'N/A' }}</td>
                            <td class="px-6 py-4 text-center">
                                @if(($status ?? 'pending') === 'pending')
                                    <a href="{{ route('ngo-approver.review-beneficiary', $worker['id'] ?? $worker['_id'] ?? '') }}" 
                                       class="bg-ngo text-white text-xs font-bold px-4 py-2 rounded-lg hover:bg-purple-700 transition shadow-sm">
                                        Review
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 font-bold">No Action Needed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-400">No records found matching this status.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        @if(count($workers) > 0)
            <div class="p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <div class="text-xs text-gray-500 font-bold">
                    Showing {{ $workers->firstItem() }} to {{ $workers->lastItem() }} of {{ $workers->total() }} records.
                </div>
                <div>
                    {{ $workers->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
