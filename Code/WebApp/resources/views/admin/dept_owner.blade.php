@extends('layouts.admin')

@section('title', 'Department Owner Dashboard')

@section('content')
<div class="space-y-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-building-columns text-yellow-500"></i> Department Owner Dashboard
            </h2>
            <p class="text-gray-500 text-xs mt-1">
                You are responsible for reviewing and approving <span class="font-bold text-yellow-700">self-registered workers</span> in your department.
            </p>
            @if(Auth::user()->department)
                <span class="mt-2 inline-block text-[10px] bg-yellow-50 border border-yellow-200 text-yellow-800 font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                    Dept: [{{ Auth::user()->department_code }}] {{ Auth::user()->department->department_name }}
                </span>
            @endif
        </div>
        <a href="{{ route('dept-owner.self-registrations') }}" 
           class="bg-yellow-500 hover:bg-yellow-400 text-white font-bold text-xs px-5 py-3 rounded-xl shadow-md hover:shadow-lg transition flex items-center gap-2 w-fit">
            <i class="fa-solid fa-user-clock text-base"></i>
            <span>Review Self-Registrations</span>
        </a>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                    <i class="fa-solid fa-clock text-amber-500 text-lg"></i>
                </div>
                <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full uppercase">Pending</span>
            </div>
            <p class="text-3xl font-black text-gray-800">{{ $pendingCount }}</p>
            <p class="text-xs text-gray-500 mt-1">Awaiting your review</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-green-100 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-green-500 text-lg"></i>
                </div>
                <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full uppercase">Verified</span>
            </div>
            <p class="text-3xl font-black text-gray-800">{{ $verifiedCount }}</p>
            <p class="text-xs text-gray-500 mt-1">Verified, pending approval</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-blue-100 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                    <i class="fa-solid fa-gavel text-blue-500 text-lg"></i>
                </div>
                <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full uppercase">Approved</span>
            </div>
            <p class="text-3xl font-black text-gray-800">{{ $approvedCount }}</p>
            <p class="text-xs text-gray-500 mt-1">Fully approved</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-red-100 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                    <i class="fa-solid fa-times-circle text-red-500 text-lg"></i>
                </div>
                <span class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full uppercase">Rejected</span>
            </div>
            <p class="text-3xl font-black text-gray-800">{{ $rejectedCount }}</p>
            <p class="text-xs text-gray-500 mt-1">Returned for correction</p>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-5 flex items-start gap-4">
        <div class="w-10 h-10 rounded-xl bg-yellow-100 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-shield-halved text-yellow-600 text-lg"></i>
        </div>
        <div>
            <h3 class="font-black text-yellow-800 text-sm">Your Special Authority</h3>
            <p class="text-yellow-700 text-xs mt-1">
                As Department Owner, <strong>only you</strong> can approve and verify workers who have 
                <strong>self-registered</strong> on the portal. Other department members (Admin, Verifier, Approver) 
                handle workers registered through official channels.
            </p>
        </div>
    </div>

    {{-- Quick Link --}}
    @if($pendingCount > 0)
    <div class="bg-white border border-amber-200 rounded-2xl p-5 flex items-center justify-between gap-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                <i class="fa-solid fa-bell text-amber-600 text-base animate-bounce"></i>
            </div>
            <div>
                <p class="font-black text-gray-800 text-sm">{{ $pendingCount }} self-registration(s) need your review</p>
                <p class="text-gray-500 text-xs">Click to review and take action</p>
            </div>
        </div>
        <a href="{{ route('dept-owner.self-registrations', ['status' => 'pending']) }}" 
           class="bg-amber-500 hover:bg-amber-400 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition">
            Review Now →
        </a>
    </div>
    @endif

</div>
@endsection
