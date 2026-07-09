@extends('layouts.admin')

@section('title', 'NGO Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Header Console -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">NGO Dashboard</h2>
            <p class="text-gray-500 text-xs mt-1">Manage and track your associated Employers, Contractors, Beneficiaries, and Welfare Schemes.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('ngo.beneficiaries') }}" class="bg-purple-700 hover:bg-purple-600 text-white font-bold text-xs px-5 py-3 rounded-xl shadow-md hover:shadow-lg transition duration-150 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-sm"></i>
                <span>Register Beneficiary</span>
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Card 1: Employers -->
        <div onclick="window.location='{{ route('ngo.employers') }}'" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-purple-200 transition duration-200 flex items-center justify-between cursor-pointer">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Employers</span>
                <h3 class="text-2xl font-black text-gray-800 mt-2">{{ $stats['employers'] ?? 0 }}</h3>
                <a href="{{ route('ngo.employers') }}" class="text-[11px] font-bold text-purple-600 hover:text-purple-700 mt-2 inline-flex items-center gap-1">
                    <span>Manage Employers</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <i class="fa-solid fa-industry text-xl"></i>
            </div>
        </div>

        <!-- Card 2: Contractors -->
        <div onclick="window.location='{{ route('ngo.contractors') }}'" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-purple-200 transition duration-200 flex items-center justify-between cursor-pointer">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Contractors</span>
                <h3 class="text-2xl font-black text-gray-800 mt-2">{{ $stats['contractors'] ?? 0 }}</h3>
                <a href="{{ route('ngo.contractors') }}" class="text-[11px] font-bold text-purple-600 hover:text-purple-700 mt-2 inline-flex items-center gap-1">
                    <span>Manage Contractors</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <i class="fa-solid fa-helmet-safety text-xl"></i>
            </div>
        </div>

        <!-- Card 3: Workers -->
        <div onclick="window.location='{{ route('ngo.beneficiaries') }}'" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-purple-200 transition duration-200 flex items-center justify-between cursor-pointer">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Beneficiaries</span>
                <h3 class="text-2xl font-black text-gray-800 mt-2">{{ $stats['workers'] ?? 0 }}</h3>
                <a href="{{ route('ngo.beneficiaries') }}" class="text-[11px] font-bold text-purple-600 hover:text-purple-700 mt-2 inline-flex items-center gap-1">
                    <span>View Beneficiaries</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <i class="fa-solid fa-users text-xl"></i>
            </div>
        </div>

        <!-- Card 4: Schemes -->
        <div onclick="window.location='{{ route('ngo.schemes') }}'" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-purple-200 transition duration-200 flex items-center justify-between cursor-pointer">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Schemes</span>
                <h3 class="text-2xl font-black text-gray-800 mt-2">{{ $stats['schemes'] ?? 0 }}</h3>
                <a href="{{ route('ngo.schemes') }}" class="text-[11px] font-bold text-purple-600 hover:text-purple-700 mt-2 inline-flex items-center gap-1">
                    <span>Manage Schemes</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <i class="fa-solid fa-shapes text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Quick Help / Instructions -->
    <div class="bg-gradient-to-r from-purple-900 to-indigo-900 text-white rounded-2xl p-8 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
            <h3 class="text-lg font-bold">Need to onboard a new Employer or Contractor?</h3>
            <p class="text-sm text-purple-200 max-w-xl">You have the authority to onboard Employers and Contractors directly. Accounts created will immediately be able to log in using their credentials.</p>
        </div>
        <div class="flex gap-4">
            <a href="{{ route('ngo.employers') }}" class="bg-white text-purple-900 font-bold text-xs px-5 py-3 rounded-xl shadow-md hover:bg-purple-50 transition duration-150">
                + Onboard Employer
            </a>
            <a href="{{ route('ngo.contractors') }}" class="bg-purple-600/30 text-white hover:bg-purple-600/40 border border-purple-500/30 font-bold text-xs px-5 py-3 rounded-xl transition duration-150">
                + Onboard Contractor
            </a>
        </div>
    </div>
</div>
@endsection
