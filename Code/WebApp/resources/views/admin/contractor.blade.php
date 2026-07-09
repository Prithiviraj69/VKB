@extends('layouts.admin')

@section('title', 'Contractor Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Header Console -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Contractor Dashboard</h2>
            <p class="text-gray-500 text-xs mt-1">Manage and register beneficiaries under your contractor license.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('contractor.beneficiaries') }}" class="bg-teal-750 hover:bg-teal-650 bg-teal-700 text-white font-bold text-xs px-5 py-3 rounded-xl shadow-md hover:shadow-lg transition duration-150 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-sm"></i>
                <span>Register Beneficiary</span>
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card 1: Beneficiaries -->
        <div onclick="window.location='{{ route('contractor.beneficiaries') }}'" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-teal-200 transition duration-200 flex items-center justify-between col-span-2 md:col-span-1 cursor-pointer">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Registered Beneficiaries</span>
                <h3 class="text-2xl font-black text-gray-800 mt-2">{{ $stats['workers'] ?? 0 }}</h3>
                <a href="{{ route('contractor.beneficiaries') }}" class="text-[11px] font-bold text-teal-650 text-teal-600 hover:text-teal-700 mt-2 inline-flex items-center gap-1">
                    <span>Manage Beneficiaries</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                <i class="fa-solid fa-users text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Quick Help / Instructions -->
    <div class="bg-gradient-to-r from-teal-900 to-slate-900 text-white rounded-2xl p-8 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
            <h3 class="text-lg font-bold">Register a new Beneficiary?</h3>
            <p class="text-sm text-teal-200 max-w-xl">You have the authority to register beneficiaries under your contractor profile. Registered beneficiaries' profiles will be verified by the respective Government Department.</p>
        </div>
        <div class="flex gap-4 shadow-sm">
            <a href="{{ route('contractor.beneficiaries') }}" class="bg-white text-teal-900 font-bold text-xs px-5 py-3 rounded-xl hover:bg-teal-50 transition duration-150">
                + Register Beneficiary
            </a>
        </div>
    </div>
</div>
@endsection
