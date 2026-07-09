@extends('layouts.admin')

@section('title', 'NGO Verifier Dashboard')

@section('content')
<div class="space-y-10">
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-purple-950 p-8 rounded-3xl text-white shadow-lg border border-slate-700/50 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
            <h2 class="text-3xl font-black tracking-wide">Welcome, {{ Auth::user()->name }}</h2>
            <p class="text-purple-450 text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-purple-400"></i>
                <span>Assigned Role: NGO Verifier Portal</span>
            </p>
        </div>
        <div class="bg-white/10 px-4 py-2.5 rounded-2xl border border-white/10 text-xs font-mono font-bold">
            System Date: {{ date('d M Y') }}
        </div>
    </div>

    <!-- Section 1: Scheme Applications -->
    <div class="space-y-4">
        <div class="border-b border-gray-200 pb-2 flex items-center gap-2">
            <i class="fa-solid fa-file-signature text-ngo text-xl"></i>
            <h3 class="text-lg font-black text-gray-800 uppercase tracking-wider text-sm">Scheme Applications Management</h3>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Pending Scheme Applications -->
            <a href="{{ route('ngo-verifier.scheme-applications', ['status' => 'pending']) }}" 
               class="group bg-white p-6 rounded-2xl border border-gray-150 shadow-sm hover:shadow-md hover:border-amber-200 hover:-translate-y-1 transition duration-200 flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pending Verification</p>
                    <h4 class="text-3xl font-black text-slate-800">{{ $stats['pending'] }}</h4>
                    <span class="text-[10px] text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded-full">Requires Review</span>
                </div>
                <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition duration-200 shadow-inner">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </a>

            <!-- Verified Scheme Applications -->
            <a href="{{ route('ngo-verifier.scheme-applications', ['status' => 'verified']) }}" 
               class="group bg-white p-6 rounded-2xl border border-gray-150 shadow-sm hover:shadow-md hover:border-purple-200 hover:-translate-y-1 transition duration-200 flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Verified by You</p>
                    <h4 class="text-3xl font-black text-slate-800">{{ $stats['verified'] }}</h4>
                    <span class="text-[10px] text-purple-600 font-bold bg-purple-50 px-2 py-0.5 rounded-full">Awaiting Approval</span>
                </div>
                <div class="w-14 h-14 bg-purple-50 text-purple-500 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition duration-200 shadow-inner">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </a>

            <!-- Approved Scheme Applications -->
            <a href="{{ route('ngo-verifier.scheme-applications', ['status' => 'approved']) }}" 
               class="group bg-white p-6 rounded-2xl border border-gray-150 shadow-sm hover:shadow-md hover:border-emerald-200 hover:-translate-y-1 transition duration-200 flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Approved Applications</p>
                    <h4 class="text-3xl font-black text-slate-800">{{ $stats['approved'] }}</h4>
                    <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-full">Cleared Grants</span>
                </div>
                <div class="w-14 h-14 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition duration-200 shadow-inner">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
            </a>

            <!-- Rejected Scheme Applications -->
            <a href="{{ route('ngo-verifier.scheme-applications', ['status' => 'rejected']) }}" 
               class="group bg-white p-6 rounded-2xl border border-gray-150 shadow-sm hover:shadow-md hover:border-rose-200 hover:-translate-y-1 transition duration-200 flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Rejected Applications</p>
                    <h4 class="text-3xl font-black text-slate-800">{{ $stats['rejected'] }}</h4>
                    <span class="text-[10px] text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded-full">Declined Grants</span>
                </div>
                <div class="w-14 h-14 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition duration-200 shadow-inner">
                    <i class="fa-solid fa-file-circle-xmark"></i>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
