@extends('layouts.admin')

@section('title', 'Verifier Dashboard')

@section('content')
<div class="space-y-10">
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 p-8 rounded-3xl text-white shadow-lg border border-slate-700/50 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
            <h2 class="text-3xl font-black tracking-wide">Welcome, {{ Auth::user()->name }}</h2>
            <p class="text-emerald-400 text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>Assigned Role: Verifier Portal</span>
                @if(Auth::user()->department)
                    <span>| Dept: [{{ Auth::user()->department_code }}] {{ Auth::user()->department->department_name }}</span>
                @endif
            </p>
        </div>
        <div class="bg-white/10 px-4 py-2.5 rounded-2xl border border-white/10 text-xs font-mono font-bold">
            System Date: {{ date('d M Y') }}
        </div>
    </div>



    <!-- Section 2: Scheme Applications -->
    <div class="space-y-4">
        <div class="border-b border-gray-200 pb-2 flex items-center gap-2">
            <i class="fa-solid fa-shapes text-indigo-600 text-xl"></i>
            <h3 class="text-lg font-black text-gray-800 uppercase tracking-wider text-sm">2. Scheme Applications</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Schemes Tile -->
            <a href="{{ route('verifier.schemes') }}" 
               class="group bg-white p-6 rounded-2xl border border-gray-150 shadow-sm hover:shadow-md hover:border-indigo-200 hover:-translate-y-1 transition duration-200 flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Board Schemes</p>
                    <h4 class="text-3xl font-black text-slate-800">{{ $stats['schemes']['total'] }}</h4>
                    <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded-full">Available Grants</span>
                </div>
                <div class="w-14 h-14 bg-indigo-50 text-indigo-500 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition duration-200 shadow-inner">
                    <i class="fa-solid fa-shapes"></i>
                </div>
            </a>

            <!-- Pending Scheme Applications -->
            <a href="{{ route('verifier.scheme-applications', ['status' => 'pending']) }}" 
               class="group bg-white p-6 rounded-2xl border border-gray-150 shadow-sm hover:shadow-md hover:border-amber-200 hover:-translate-y-1 transition duration-200 flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pending Applications</p>
                    <h4 class="text-3xl font-black text-slate-800">{{ $stats['schemes']['pending'] }}</h4>
                    <span class="text-[10px] text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded-full">Needs Verification</span>
                </div>
                <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition duration-200 shadow-inner">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
            </a>

            <!-- Approved Scheme Applications -->
            <a href="{{ route('verifier.scheme-applications', ['status' => 'approved']) }}" 
               class="group bg-white p-6 rounded-2xl border border-gray-150 shadow-sm hover:shadow-md hover:border-emerald-200 hover:-translate-y-1 transition duration-200 flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Approved Applications</p>
                    <h4 class="text-3xl font-black text-slate-800">{{ $stats['schemes']['approved'] }}</h4>
                    <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-full">Cleared Grants</span>
                </div>
                <div class="w-14 h-14 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition duration-200 shadow-inner">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
            </a>

            <!-- Rejected Scheme Applications -->
            <a href="{{ route('verifier.scheme-applications', ['status' => 'rejected']) }}" 
               class="group bg-white p-6 rounded-2xl border border-gray-150 shadow-sm hover:shadow-md hover:border-rose-200 hover:-translate-y-1 transition duration-200 flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Rejected Applications</p>
                    <h4 class="text-3xl font-black text-slate-800">{{ $stats['schemes']['rejected'] }}</h4>
                    <span class="text-[10px] text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded-full">Declined Grants</span>
                </div>
                <div class="w-14 h-14 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition duration-200 shadow-inner">
                    <i class="fa-solid fa-file-circle-xmark"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Section 3: Grievances -->
    <div class="space-y-4">
        <div class="border-b border-gray-200 pb-2 flex items-center gap-2">
            <i class="fa-solid fa-envelope text-blue-600 text-xl"></i>
            <h3 class="text-lg font-black text-gray-800 uppercase tracking-wider text-sm">3. Grievances</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- New Grievances -->
            <a href="{{ route('verifier.grievances', ['status' => 'new']) }}" 
               class="group bg-white p-6 rounded-2xl border border-gray-150 shadow-sm hover:shadow-md hover:border-blue-200 hover:-translate-y-1 transition duration-200 flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">New Complaints</p>
                    <h4 class="text-3xl font-black text-slate-800">{{ $stats['grievances']['new'] }}</h4>
                    <span class="text-[10px] text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded-full">Unopened Tickets</span>
                </div>
                <div class="w-14 h-14 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition duration-200 shadow-inner">
                    <i class="fa-solid fa-message"></i>
                </div>
            </a>

            <!-- Pending Grievances -->
            <a href="{{ route('verifier.grievances', ['status' => 'pending']) }}" 
               class="group bg-white p-6 rounded-2xl border border-gray-150 shadow-sm hover:shadow-md hover:border-amber-200 hover:-translate-y-1 transition duration-200 flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pending Grievances</p>
                    <h4 class="text-3xl font-black text-slate-800">{{ $stats['grievances']['pending'] }}</h4>
                    <span class="text-[10px] text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded-full">In Progress</span>
                </div>
                <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition duration-200 shadow-inner">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </a>

            <!-- Disposed Grievances -->
            <a href="{{ route('verifier.grievances', ['status' => 'disposed']) }}" 
               class="group bg-white p-6 rounded-2xl border border-gray-150 shadow-sm hover:shadow-md hover:border-emerald-200 hover:-translate-y-1 transition duration-200 flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Disposed Grievances</p>
                    <h4 class="text-3xl font-black text-slate-800">{{ $stats['grievances']['disposed'] }}</h4>
                    <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-full">Resolved Cases</span>
                </div>
                <div class="w-14 h-14 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition duration-200 shadow-inner">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
