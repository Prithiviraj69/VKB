@extends('layouts.admin')

@section('title', 'Self-Registrations — Department Owner')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-user-clock text-yellow-500"></i> Self-Registrations Queue
            </h2>
            <p class="text-gray-500 text-xs mt-1">Review and approve/reject workers who self-registered on the portal.</p>
        </div>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="flex bg-gray-100 p-1 rounded-xl text-xs font-bold text-gray-500 gap-1 w-fit">
        @foreach(['pending' => '⏳ Pending', 'verified' => '✅ Verified', 'approved' => '⚖️ Approved', 'rejected' => '❌ Rejected'] as $tabStatus => $label)
            <a href="{{ route('dept-owner.self-registrations', ['status' => $tabStatus, 'search' => $search]) }}" 
               class="px-3 py-1.5 rounded-lg transition {{ $status === $tabStatus ? 'bg-white text-gray-800 font-extrabold shadow-sm' : 'hover:text-gray-800' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('dept-owner.self-registrations') }}" class="flex gap-3">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search by worker name..." 
               class="px-4 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-yellow-400 w-64 transition">
        <button type="submit" class="bg-yellow-500 text-white font-bold text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition">
            Search
        </button>
        @if($search)
            <a href="{{ route('dept-owner.self-registrations', ['status' => $status]) }}" 
               class="bg-gray-200 text-gray-700 font-bold text-xs px-4 py-2 rounded-xl hover:bg-gray-300 transition">Clear</a>
        @endif
    </form>

    {{-- Workers Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4">Worker Details</th>
                    <th class="px-6 py-4">Trade / Aadhaar</th>
                    <th class="px-6 py-4">Registered On</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                @forelse($workers as $worker)
                    @php
                        $w = (array) $worker;
                        $wId = $w['_id'] ?? $w['id'] ?? '';
                        $workerStatus = $w['status'] ?? 'pending';
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="text-xs font-bold text-gray-800">{{ $w['full_name'] ?? 'N/A' }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">Father: {{ $w['father_name'] ?? 'N/A' }}</div>
                            @if(isset($w['dob']))
                                <div class="text-[10px] text-gray-400">DOB: {{ is_string($w['dob']) ? explode(' ', $w['dob'])[0] : 'N/A' }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-bold text-yellow-700 bg-yellow-50 px-2 py-0.5 rounded w-fit">{{ $w['job_profile'] ?? 'N/A' }}</div>
                            <div class="text-[10px] text-gray-400 mt-1 font-mono">Aadhaar: {{ $w['aadhaar_number'] ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">
                            {{ isset($w['created_at']) ? (is_string($w['created_at']) ? explode(' ', $w['created_at'])[0] : 'N/A') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider
                                @if($workerStatus === 'approved') bg-blue-50 text-blue-700 border border-blue-200
                                @elseif($workerStatus === 'verified') bg-green-50 text-green-700 border border-green-200
                                @elseif($workerStatus === 'rejected') bg-red-50 text-red-700 border border-red-200
                                @else bg-amber-50 text-amber-700 border border-amber-200
                                @endif">
                                {{ $workerStatus }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('dept-owner.review', $wId) }}" 
                               class="bg-yellow-500 hover:bg-yellow-400 text-white font-bold text-[10px] px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1">
                                <i class="fa-solid fa-eye text-xs"></i> Review
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-10 text-gray-400">
                            <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                            No {{ $status }} self-registrations found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($workers->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $workers->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
