@extends('layouts.admin')

@section('title', 'Employer Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Header Console -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Employer Dashboard</h2>
            <p class="text-gray-500 text-xs mt-1">Manage and track your associated Contractors and registered Beneficiaries.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('employer.beneficiaries') }}" class="bg-indigo-750 hover:bg-indigo-650 bg-indigo-700 text-white font-bold text-xs px-5 py-3 rounded-xl shadow-md hover:shadow-lg transition duration-150 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-sm"></i>
                <span>Register Beneficiary</span>
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card 1: Contractors -->
        <div onclick="window.location='{{ route('employer.contractors') }}'" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-200 transition duration-200 flex items-center justify-between cursor-pointer">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Contractors</span>
                <h3 class="text-2xl font-black text-gray-800 mt-2">{{ $stats['contractors'] ?? 0 }}</h3>
                <a href="{{ route('employer.contractors') }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 mt-2 inline-flex items-center gap-1">
                    <span>Manage Contractors</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i class="fa-solid fa-helmet-safety text-xl"></i>
            </div>
        </div>

        <!-- Card 2: Workers -->
        <div onclick="window.location='{{ route('employer.beneficiaries') }}'" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-200 transition duration-200 flex items-center justify-between cursor-pointer">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Beneficiaries</span>
                <h3 class="text-2xl font-black text-gray-800 mt-2">{{ $stats['workers'] ?? 0 }}</h3>
                <a href="{{ route('employer.beneficiaries') }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 mt-2 inline-flex items-center gap-1">
                    <span>View Beneficiaries</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i class="fa-solid fa-users text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Quick Help / Instructions -->
    <div class="bg-gradient-to-r from-indigo-900 to-slate-900 text-white rounded-2xl p-8 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
            <h3 class="text-lg font-bold">Onboard a new Contractor (Thekedar)?</h3>
            <p class="text-sm text-indigo-200 max-w-xl">You have the authority to register and manage Contractors directly. They will be able to immediately log in and register beneficiaries under your network.</p>
        </div>
        <div class="flex gap-4 shadow-sm">
            <a href="{{ route('employer.contractors') }}" class="bg-white text-indigo-900 font-bold text-xs px-5 py-3 rounded-xl hover:bg-indigo-50 transition duration-150">
                + Onboard Contractor
            </a>
        </div>
    </div>
</div>
@endsection
