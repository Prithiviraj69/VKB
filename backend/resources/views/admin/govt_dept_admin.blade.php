@extends('layouts.admin')

@section('title', 'Govt Dept Admin Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Header Console -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                    Government Department Admin Console
                </span>
            </div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide mt-2">
                {{ $department->department_name ?? 'Government Department' }}
            </h2>
            <p class="text-gray-500 text-xs mt-1">
                Manage your department's internal Verifiers, Approvers, and monitor beneficiary registration statistics.
            </p>
        </div>
        <div class="flex gap-2">
            @if($isOwnerDept)
                <a href="{{ route('owner-dept.export-csv') }}" class="bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs px-5 py-3 rounded-xl shadow-md hover:shadow-lg transition duration-150 flex items-center gap-2">
                    <i class="fa-solid fa-file-excel text-sm"></i>
                    <span>Export Self-Registered Beneficiaries (CSV)</span>
                </a>
                <a href="{{ route('owner-dept.onboard') }}" class="bg-indigo-700 hover:bg-indigo-600 text-white font-bold text-xs px-5 py-3 rounded-xl shadow-md hover:shadow-lg transition duration-150 flex items-center gap-2">
                    <i class="fa-solid fa-square-plus text-sm"></i>
                    <span>Onboard Hub</span>
                </a>
            @endif
            @if(!$isOwnerDept)
                <a href="{{ route('govt-dept-admin.users') }}" class="bg-indigo-700 hover:bg-indigo-600 text-white font-bold text-xs px-5 py-3 rounded-xl shadow-md hover:shadow-lg transition duration-150 flex items-center gap-2">
                    <i class="fa-solid fa-users-gear text-sm"></i>
                    <span>Manage internal Users</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Stats Grid -->
    @if($isOwnerDept)
        <!-- Owner Dept Premium Tiles -->
        <div class="space-y-8">
            <!-- Row 1: Beneficiaries -->
            <div class="space-y-3">
                <h4 class="text-xs font-black text-gray-450 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-users text-indigo-500 text-sm"></i>
                    <span>Beneficiaries Registry Status</span>
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Total Beneficiaries Tile -->
                    <div onclick="window.location='?status=pending'" class="bg-gradient-to-br from-indigo-50 to-white rounded-2xl p-5 border border-indigo-100 shadow-sm hover:shadow-md hover:border-indigo-300 transition duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Total Beneficiaries</span>
                            <h3 class="text-2xl font-black text-indigo-700 mt-2">{{ $totalBeneficiariesCount }}</h3>
                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block">All registered beneficiaries</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
                            <i class="fa-solid fa-users text-xl"></i>
                        </div>
                    </div>

                    <!-- Pending Beneficiaries Tile -->
                    <div onclick="window.location='?status=pending'" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-amber-200 transition duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pending</span>
                            <h3 class="text-2xl font-black text-amber-600 mt-2">{{ $pendingWorkers }}</h3>
                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Awaiting verification</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i class="fa-solid fa-hourglass-half text-xl"></i>
                        </div>
                    </div>

                    <!-- Active Beneficiaries Tile (approved + verified) -->
                    <div onclick="window.location='?status=active'" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-emerald-200 transition duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active</span>
                            <h3 class="text-2xl font-black text-emerald-600 mt-2">{{ $activeWorkersCount }}</h3>
                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Approved &amp; verified accounts</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="fa-solid fa-user-check text-xl"></i>
                        </div>
                    </div>

                    <!-- Inactive Beneficiaries Tile -->
                    <div onclick="window.location='?status=inactive'" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-rose-200 transition duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Inactive</span>
                            <h3 class="text-2xl font-black text-rose-600 mt-2">{{ $inactiveWorkersCount }}</h3>
                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Deactivated accounts</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <i class="fa-solid fa-user-slash text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Row 2: Schemes -->
            <div class="space-y-3 pt-2">
                <h4 class="text-xs font-black text-gray-455 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-graduation-cap text-orange-500 text-sm"></i>
                    <span>Schemes & Applications</span>
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Active Schemes -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active Schemes</span>
                            <h3 class="text-2xl font-black text-emerald-600 mt-2">{{ $activeSchemesCount }}</h3>
                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Live onboarded schemes</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="fa-solid fa-circle-check text-xl"></i>
                        </div>
                    </div>

                    <!-- InActive Schemes -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">InActive Schemes</span>
                            <h3 class="text-2xl font-black text-rose-600 mt-2">{{ $inactiveSchemesCount }}</h3>
                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Paused/draft schemes</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <i class="fa-solid fa-circle-xmark text-xl"></i>
                        </div>
                    </div>

                    <!-- Total applications received -->
                    <div onclick="window.location='?status=scheme_applications'" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-blue-200 transition duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Applications</span>
                            <h3 class="text-2xl font-black text-blue-600 mt-2">{{ $totalAppsCount }}</h3>
                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Total scheme requests received</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="fa-solid fa-file-invoice text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Onboarded Institutions -->
            <div class="space-y-3 pt-2">
                <h4 class="text-xs font-black text-gray-460 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-sitemap text-violet-500 text-sm"></i>
                    <span>Onboarded Institutions</span>
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <!-- Govt Depts Tile -->
                    <div onclick="scrollToRegistry('dept')" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-200 transition duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Govt Depts</span>
                            <h3 class="text-2xl font-black text-indigo-700 mt-2">{{ $govtDeptsCount }}</h3>
                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Registered departments</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center">
                            <i class="fa-solid fa-building-columns text-xl"></i>
                        </div>
                    </div>

                    <!-- NGOs Tile -->
                    <div onclick="scrollToRegistry('ngo')" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-purple-200 transition duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">NGOs</span>
                            <h3 class="text-2xl font-black text-purple-700 mt-2">{{ $ngosCount }}</h3>
                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Partner NGOs</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
                            <i class="fa-solid fa-handshake text-xl"></i>
                        </div>
                    </div>

                    <!-- Employers Tile -->
                    <div onclick="scrollToRegistry('emp')" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-blue-200 transition duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Employers</span>
                            <h3 class="text-2xl font-black text-blue-700 mt-2">{{ $employersCount }}</h3>
                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Factories & employers</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                            <i class="fa-solid fa-industry text-xl"></i>
                        </div>
                    </div>

                    <!-- Contractors Tile -->
                    <div onclick="scrollToRegistry('cnt')" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-teal-200 transition duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Contractors</span>
                            <h3 class="text-2xl font-black text-teal-700 mt-2">{{ $contractorsCount }}</h3>
                            <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Onboarded contractors</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center">
                            <i class="fa-solid fa-helmet-safety text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 4: Trade-wise Beneficiary Counts -->
            <div class="space-y-3 pt-2">
                <h4 class="text-xs font-black text-gray-450 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-list-check text-orange-655 text-sm"></i>
                    <span>Job Profile & Trade Registry Breakdown</span>
                </h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @forelse($tradeBreakdown as $trade => $count)
                        @php
                            $isCurrentTrade = $jobProfile === $trade;
                        @endphp
                        <div onclick="window.location='?{{ http_build_query(array_merge(request()->query(), ['job_profile' => $isCurrentTrade ? null : $trade])) }}'" 
                             class="bg-white rounded-xl p-4 border {{ $isCurrentTrade ? 'border-orange-500 bg-orange-50/10 shadow-md' : 'border-gray-100 shadow-sm hover:border-orange-200' }} hover:shadow-md transition duration-200 cursor-pointer flex flex-col justify-between">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-xs font-bold text-gray-700 line-clamp-2" title="{{ $trade }}">{{ $trade }}</span>
                                @if($isCurrentTrade)
                                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                                @endif
                            </div>
                            <div class="mt-3 flex items-baseline justify-between">
                                <span class="text-xl font-black text-gray-800">{{ $count }}</span>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Beneficiaries</span>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full bg-white rounded-2xl p-6 border border-gray-100 shadow-sm text-center text-gray-450 text-xs font-semibold">
                            No registered beneficiaries found under any job profile yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Card 0: Total -->
            <div class="bg-gradient-to-br from-indigo-50 to-white rounded-2xl p-6 border border-indigo-100 shadow-sm flex items-center justify-between col-span-2 md:col-span-1">
                <div>
                    <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Total</span>
                    <h3 class="text-2xl font-black text-indigo-700 mt-2">{{ $totalBeneficiariesCount }}</h3>
                    <span class="text-[10px] text-gray-400 font-semibold mt-2 block">All beneficiaries</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
                    <i class="fa-solid fa-users text-xl"></i>
                </div>
            </div>

            <!-- Card 1: Verifiers -->
            <div onclick="window.location='{{ route('govt-dept-admin.users') }}?role=dept-verifier'" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-200 transition duration-200 flex items-center justify-between cursor-pointer">
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Verifiers</span>
                    <h3 class="text-2xl font-black text-gray-800 mt-2">{{ $verifierCount }}</h3>
                    <a href="{{ route('govt-dept-admin.users') }}?role=dept-verifier" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 mt-2 inline-flex items-center gap-1">
                        <span>View Verifiers</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-xl"></i>
                </div>
            </div>

            <!-- Card 2: Approvers -->
            <div onclick="window.location='{{ route('govt-dept-admin.users') }}?role=dept-approver'" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-200 transition duration-200 flex items-center justify-between cursor-pointer">
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Approvers</span>
                    <h3 class="text-2xl font-black text-gray-800 mt-2">{{ $approverCount }}</h3>
                    <a href="{{ route('govt-dept-admin.users') }}?role=dept-approver" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 mt-2 inline-flex items-center gap-1">
                        <span>View Approvers</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="fa-solid fa-gavel text-xl"></i>
                </div>
            </div>

            <!-- Card 3: Pending Beneficiaries -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pending</span>
                    <h3 class="text-2xl font-black text-amber-600 mt-2">{{ $pendingWorkers }}</h3>
                    <span class="text-[10px] text-gray-400 font-semibold mt-2 block">Awaiting verification</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="fa-solid fa-hourglass-half text-xl"></i>
                </div>
            </div>

            <!-- Card 4: Verified Beneficiaries -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Verified</span>
                    <h3 class="text-2xl font-black text-blue-600 mt-2">{{ $verifiedWorkers }}</h3>
                    <span class="text-[10px] text-gray-400 font-semibold mt-2 block">Awaiting approval</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i class="fa-solid fa-shield-halved text-xl"></i>
                </div>
            </div>

            <!-- Card 5: Approved Beneficiaries -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Approved</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-2">{{ $approvedWorkers }}</h3>
                    <span class="text-[10px] text-gray-400 font-semibold mt-2 block">Onboarded in registry</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="fa-solid fa-square-check text-xl"></i>
                </div>
            </div>
        </div>
    @endif



    @if($isOwnerDept)
        <!-- Main Dashboard Tabs -->
        <div class="flex border border-gray-100 bg-white p-1.5 rounded-2xl shadow-sm gap-2 max-w-xl">
            <button type="button" onclick="switchMainTab('workers')" id="main-tab-btn-workers" class="flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-xs font-bold transition duration-200">
                <i class="fa-solid fa-users text-sm"></i>
                <span>Beneficiaries Registry</span>
            </button>
            <button type="button" onclick="switchMainTab('master')" id="main-tab-btn-master" class="flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-xs font-bold transition duration-200">
                <i class="fa-solid fa-database text-sm"></i>
                <span>Master Registry Console</span>
            </button>
        </div>

        <div id="main-tab-content-workers" class="main-tab-panel mt-6">
            @if($status === 'scheme_beneficiaries')
                <!-- Scheme Beneficiaries Summary Table -->
                <div class="bg-white rounded-2xl border border-gray-105 shadow-sm overflow-hidden flex flex-col justify-between w-full">
                    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">
                                Applicants for: <span class="text-indigo-650">{{ $selectedScheme['title'] ?? $selectedScheme['name'] ?? 'Scheme' }}</span>
                            </h3>
                            <p class="text-xs text-gray-400 mt-1">
                                List of beneficiaries who applied to this scheme and their application status.
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <a href="?status=scheme_applications" class="text-xs font-bold text-gray-650 hover:text-gray-700 bg-gray-50 border border-gray-250 px-3 py-1.5 rounded-lg flex items-center gap-1.5 w-fit">
                                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                                <span>Back to Schemes</span>
                            </a>
                            <a href="{{ route('govt-dept-admin.index') }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 border border-rose-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 w-fit">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Clear Filter</span>
                            </a>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Beneficiary Name</th>
                                    <th class="px-6 py-4">Applied Date</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-center">Benefit Received</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                                @forelse($beneficiaries as $ben)
                                    @php
                                        $ben = (array) $ben;
                                        $benefitReceived = !empty($ben['benefit_received']) && in_array($ben['benefit_received'], [true, 1, '1', 'yes', 'Yes', 'received']);
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-4 text-xs font-bold text-gray-800">
                                            {{ $ben['worker_name'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                            {{ isset($ben['created_at']) ? explode(' ', $ben['created_at'])[0] : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider
                                                @if(($ben['status'] ?? 'pending') === 'approved') bg-green-50 text-green-700 border border-green-200
                                                @elseif(($ben['status'] ?? 'pending') === 'verified') bg-blue-50 text-blue-700 border border-blue-200
                                                @elseif(($ben['status'] ?? 'pending') === 'rejected') bg-red-50 text-red-700 border border-red-200
                                                @else bg-amber-50 text-amber-700 border border-amber-200
                                                @endif">
                                                {{ $ben['status'] ?? 'pending' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if($benefitReceived)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 shadow-sm" title="Benefit Received">
                                                    <i class="fa-solid fa-check text-sm font-black"></i>
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400 font-bold" title="Benefit Pending/Not Received">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-8 text-gray-400">No applicants found for this scheme.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($beneficiaries instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                        <div class="p-6 border-t border-gray-100">
                            {{ $beneficiaries->links() }}
                        </div>
                    @endif
                </div>
            @elseif($status === 'scheme_applications')
                <!-- Scheme Applications Summary Table -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between w-full">
                    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Scheme Applications Summary</h3>
                            <p class="text-xs text-gray-400 mt-1">
                                Showing registered schemes along with the total number of applications received.
                            </p>
                        </div>
                        <a href="{{ route('govt-dept-admin.index') }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 border border-rose-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 w-fit">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <span>Clear Filter</span>
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Scheme Title</th>
                                    <th class="px-6 py-4">URL</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-center">Total Applications</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                                @forelse($schemesList as $sch)
                                    @php
                                        $title = $sch['title'] ?? $sch['name'] ?? 'N/A';
                                        $url = $sch['url'] ?? $sch['apply_url'] ?? '#';
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-4 text-xs font-bold text-gray-800">
                                            <a href="?status=scheme_beneficiaries&scheme_id={{ $sch['id'] ?? $sch['_id'] }}" class="text-indigo-650 hover:text-indigo-850 hover:underline">
                                                {{ $title }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-mono text-indigo-600 hover:underline">
                                            @if($url && $url !== '#')
                                                <a href="{{ $url }}" target="_blank">{{ $url }}</a>
                                            @else
                                                <span class="text-gray-400">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Active
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center font-bold text-indigo-755">
                                            {{ $sch['applications_count'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-8 text-gray-400">No schemes found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <!-- Self-Registered Beneficiaries Table -->
                <div class="bg-white rounded-2xl border border-gray-105 shadow-sm overflow-hidden flex flex-col justify-between w-full">
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Self-Registered Beneficiaries Registry</h3>
                        <p class="text-xs text-gray-400 mt-1">
                            @if($status || $jobProfile)
                                Showing beneficiaries with 
                                @if($status)
                                    status <span class="font-extrabold text-indigo-650 uppercase">{{ $status }}</span>
                                @endif
                                @if($status && $jobProfile)
                                    and 
                                @endif
                                @if($jobProfile)
                                    trade <span class="font-extrabold text-orange-600 uppercase">{{ $jobProfile }}</span>
                                @endif
                            @else
                                Registry of beneficiaries who self-registered on the portal.
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($jobProfile)
                            <a href="?{{ http_build_query(array_merge(request()->query(), ['job_profile' => null])) }}" class="text-xs font-bold text-orange-600 hover:text-orange-700 bg-orange-50 border border-orange-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 w-fit">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Clear Trade ({{ $jobProfile }})</span>
                            </a>
                        @endif
                        @if($status)
                            <a href="?{{ http_build_query(array_merge(request()->query(), ['status' => null])) }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 border border-rose-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 w-fit">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Clear Status Filter</span>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Beneficiary Details</th>
                                <th class="px-6 py-4">Trade</th>
                                <th class="px-6 py-4 text-center">Verification Status</th>
                                <th class="px-6 py-4 text-center">Account Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                            @forelse($workers as $worker)
                                @php
                                    $worker = (array) $worker;
                                    $wId = $worker['_id'] ?? $worker['id'] ?? '';
                                    $userId = $worker['user_id'] ?? '';
                                    $wUser = $userId ? \App\Models\User::find($userId) : null;
                                    $wStatus = $worker['status'] ?? 'pending';
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-bold">
                                            <span onclick="openViewBenModal({{ json_encode($worker) }})" class="text-indigo-650 hover:text-indigo-800 hover:underline cursor-pointer">
                                                {{ $worker['full_name'] ?? 'N/A' }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 mt-0.5">Father: {{ $worker['father_name'] ?? 'N/A' }}</div>
                                        @if(isset($worker['dob']))
                                            <div class="text-[10px] text-gray-400 mt-0.5">DOB: {{ explode(' ', is_string($worker['dob']) ? $worker['dob'] : (isset($worker['dob']->date) ? $worker['dob']->date : 'N/A'))[0] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded w-fit">{{ $worker['job_profile'] ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider
                                            @if($wStatus === 'approved' || $wStatus === 'verified') bg-green-50 text-green-700 border border-green-200
                                            @elseif($wStatus === 'rejected') bg-red-50 text-red-700 border border-red-200
                                            @else bg-amber-50 text-amber-700 border border-amber-200
                                            @endif">
                                            {{ $wStatus }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider
                                            @if($wUser && $wUser->is_active) bg-emerald-55 text-emerald-800 border border-emerald-200
                                            @else bg-rose-55 text-rose-800 border border-rose-200
                                            @endif">
                                            {{ ($wUser && $wUser->is_active) ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-gray-400">No self-registered beneficiaries found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($workers instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                    <div class="p-6 border-t border-gray-100">
                        {{ $workers->links() }}
                    </div>
                @endif
                </div>
            @endif
        </div>
    @endif

    @if($isOwnerDept)
        <div id="main-tab-content-master" class="main-tab-panel mt-6 hidden">
            <!-- Owner Department Master Registry Console -->
            <div id="master-registry-section" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between w-full">
            <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Master Registry Console</h3>
                    <p class="text-xs text-gray-400 mt-1">View list of all Government Departments, NGOs, Employers, and Contractors registered on the system.</p>
                </div>

                <!-- Tabs Selector -->
                <div class="flex bg-gray-100 p-1 rounded-xl text-xs font-bold text-gray-500 gap-1">
                    <button type="button" onclick="switchRegistryTab('dept')" id="registry-tab-dept" class="px-3 py-1.5 rounded-lg bg-white text-gray-850 font-extrabold shadow-sm transition">Govt Depts</button>
                    <button type="button" onclick="switchRegistryTab('ngo')" id="registry-tab-ngo" class="px-3 py-1.5 rounded-lg hover:text-gray-800 transition">NGOs</button>
                    <button type="button" onclick="switchRegistryTab('emp')" id="registry-tab-emp" class="px-3 py-1.5 rounded-lg hover:text-gray-800 transition">Employers</button>
                    <button type="button" onclick="switchRegistryTab('cnt')" id="registry-tab-cnt" class="px-3 py-1.5 rounded-lg hover:text-gray-800 transition">Contractors</button>
                </div>
            </div>

            <!-- List 1: Departments -->
            <div id="registry-content-dept" class="overflow-x-auto flex-1 registry-panel p-4">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Department Name</th>
                            <th class="px-6 py-4">Code</th>
                            <th class="px-6 py-4">Official Email</th>
                            <th class="px-6 py-4">Office Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($departments as $dept)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4 text-xs font-bold text-gray-800">{{ $dept->department_name }}</td>
                                <td class="px-6 py-4 text-xs font-mono text-indigo-600 font-extrabold">{{ $dept->department_code }}</td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ $dept->email }}</td>
                                <td class="px-6 py-4 text-xs text-gray-500">{{ $dept->office_address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-8 text-gray-400">No departments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- List 2: NGOs -->
            <div id="registry-content-ngo" class="overflow-x-auto flex-1 registry-panel p-4 hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">NGO Name</th>
                            <th class="px-6 py-4">Registration No</th>
                            <th class="px-6 py-4">Contact Details</th>
                            <th class="px-6 py-4">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($ngos as $ngo)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4 text-xs font-bold text-gray-800">
                                    <div>{{ $ngo->ngo_name }}</div>
                                    <div class="text-[10px] text-gray-400 font-medium mt-0.5">{{ $ngo->office_address }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-600">{{ $ngo->registration_no }}</td>
                                <td class="px-6 py-4 text-xs">{{ $ngo->contact_person }} ({{ $ngo->mobile }})</td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ $ngo->email }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-8 text-gray-400">No NGOs onboarded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- List 3: Employers -->
            <div id="registry-content-emp" class="overflow-x-auto flex-1 registry-panel p-4 hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Employer/Factory</th>
                            <th class="px-6 py-4">Registration No</th>
                            <th class="px-6 py-4">Contact Details</th>
                            <th class="px-6 py-4">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($employers as $emp)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4 text-xs font-bold text-gray-800">
                                    <div>{{ $emp->employer_name }}</div>
                                    <div class="text-[10px] text-gray-400 font-medium mt-0.5">{{ $emp->office_address }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-600">{{ $emp->registration_no }}</td>
                                <td class="px-6 py-4 text-xs">{{ $emp->contact_person }} ({{ $emp->mobile }})</td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ $emp->email }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-8 text-gray-400">No employers onboarded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- List 4: Contractors -->
            <div id="registry-content-cnt" class="overflow-x-auto flex-1 registry-panel p-4 hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Contractor/Thekedar</th>
                            <th class="px-6 py-4">License No</th>
                            <th class="px-6 py-4">Mobile</th>
                            <th class="px-6 py-4">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                        @forelse($contractors as $cnt)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4 text-xs font-bold text-gray-800">
                                    <div>{{ $cnt->contractor_name }}</div>
                                    <div class="text-[10px] text-gray-400 font-medium mt-0.5">{{ $cnt->office_address }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-600">{{ $cnt->license_no }}</td>
                                <td class="px-6 py-4 text-xs">{{ $cnt->mobile }}</td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ $cnt->email }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-8 text-gray-400">No contractors onboarded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    @endif

    <!-- Quick Administration Banner -->
    <div class="bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 text-white rounded-2xl p-6 shadow-md flex flex-col md:flex-row items-center justify-between gap-6 mt-6">
        <div class="space-y-2">
            <h3 class="text-lg font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-question text-indigo-400"></i>
                <span>Quick Administration</span>
            </h3>
            <p class="text-xs text-indigo-200 leading-relaxed max-w-2xl">
                You can manage internal verifiers and approvers. They will have access to process beneficiary registrations tagged with department code <strong>{{ Auth::user()->department_code }}</strong>.
            </p>
            <div class="flex items-center gap-4 text-xs pt-1">
                <div>
                    <span class="text-indigo-300">My Department Code:</span>
                    <span class="font-mono font-bold px-2 py-0.5 bg-indigo-800 rounded ml-1">{{ Auth::user()->department_code }}</span>
                </div>
                <div class="h-4 border-l border-indigo-800"></div>
                <div>
                    <span class="text-indigo-300">Created Users:</span>
                    <span class="font-bold text-white ml-1">{{ $verifierCount + $approverCount }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Beneficiary Details Modal -->
<div id="viewBenModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full border border-gray-100 shadow-xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-indigo-900 text-white">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full border border-white/20 overflow-hidden flex items-center justify-center bg-white/10 shrink-0">
                    <img id="view_ben_photo" src="" alt="Photo" class="w-full h-full object-cover hidden" onerror="this.src='https://placehold.co/100x100?text=Profile';">
                    <i id="view_ben_avatar_icon" class="fa-solid fa-user text-lg text-indigo-200"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white" id="view_ben_name">Beneficiary Profile</h3>
                    <p class="text-[10px] text-indigo-200 font-bold uppercase tracking-wider" id="view_ben_status">Verification: Pending</p>
                </div>
            </div>
            <button type="button" onclick="closeViewBenModal()" class="text-white/70 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Body / Accordion List -->
        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto bg-gray-50/50">
            
            <!-- 1. Personal Details Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('personal')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-user text-indigo-500"></i> Personal Details
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-personal"></i>
                </button>
                <div id="acc-personal" class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold text-gray-700 hidden">
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Father's Name</span><p id="view_father_name" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Gender</span><p id="view_gender" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Date of Birth</span><p id="view_dob" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Marital Status</span><p id="view_marital" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Caste / Category</span><p id="view_category" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Mobile Number</span><p id="view_mobile" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">District</span><p id="view_district" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">State</span><p id="view_state" class="font-bold text-gray-900 mt-0.5"></p></div>
                </div>
            </div>

            <!-- 2. Education Details Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('education')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-graduation-cap text-indigo-500"></i> Education Details
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-education"></i>
                </button>
                <div id="acc-education" class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold text-gray-700 hidden">
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Qualification</span><p id="view_qualification" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">School / Institution Name</span><p id="view_school" class="font-bold text-gray-900 mt-0.5"></p></div>
                </div>
            </div>

            <!-- 3. Employment Details Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('employment')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-briefcase text-indigo-500"></i> Employment Details
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-employment"></i>
                </button>
                <div id="acc-employment" class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold text-gray-700 hidden">
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Job Profile / Trade</span><p id="view_job_profile" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Work Experience (in years)</span><p id="view_experience" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Daily Wage (INR)</span><p id="view_wage" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Employment Site Address</span><p id="view_employment_site" class="font-bold text-gray-900 mt-0.5"></p></div>
                </div>
            </div>

            <!-- 4. Bank Details Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('bank')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-building-columns text-indigo-500"></i> Bank Details
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-bank"></i>
                </button>
                <div id="acc-bank" class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold text-gray-700 hidden">
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Account Number</span><p id="view_bank_acc" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Bank Name & Branch</span><p id="view_bank_name" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Bank IFSC</span><p id="view_bank_ifsc" class="font-bold text-gray-900 mt-0.5"></p></div>
                </div>
            </div>

            <!-- 5. BOCW Registration Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('bocw')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-certificate text-indigo-500"></i> BOCW Registration
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-bocw"></i>
                </button>
                <div id="acc-bocw" class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold text-gray-700 hidden">
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">BOCW Card Number</span><p id="view_bocw_card" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Registration Date</span><p id="view_bocw_reg_date" class="font-bold text-gray-900 mt-0.5"></p></div>
                    <div><span class="text-[9px] font-black text-gray-400 uppercase block">Expiry Date / Valid Upto</span><p id="view_bocw_valid" class="font-bold text-gray-900 mt-0.5"></p></div>
                </div>
            </div>

            <!-- 6. PDS Data Accordion (Ration Card) -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('pds')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-users text-indigo-500"></i> PDS Data (Family Members)
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-pds"></i>
                </button>
                <div id="acc-pds" class="p-4 text-xs font-semibold text-gray-700 hidden space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="text-[9px] font-black text-gray-400 uppercase block">Ration Card Number</span><p id="view_ration_card_number" class="font-bold text-gray-900 mt-0.5"></p></div>
                        <div><span class="text-[9px] font-black text-gray-400 uppercase block">Ration Card Address</span><p id="view_ration_address" class="font-bold text-gray-900 mt-0.5"></p></div>
                    </div>
                    <div class="border-t border-gray-150/40 pt-3">
                        <span class="text-[9px] font-black text-gray-400 uppercase block mb-2">Parsed Family Members</span>
                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="w-full text-left border-collapse text-[11px]">
                                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider text-[8px]">
                                    <tr>
                                        <th class="p-2 border-b border-gray-100">Name</th>
                                        <th class="p-2 border-b border-gray-100">Relationship</th>
                                        <th class="p-2 border-b border-gray-100">Gender</th>
                                        <th class="p-2 border-b border-gray-100">Age</th>
                                    </tr>
                                </thead>
                                <tbody id="view_family_members_list" class="divide-y divide-gray-100 text-gray-700 font-medium">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. Signature Upload Accordion -->
            <div class="bg-white border border-gray-200/65 rounded-xl shadow-sm overflow-hidden">
                <button type="button" onclick="toggleAccordion('signature')" class="w-full p-4 flex items-center justify-between font-bold text-xs text-gray-700 hover:bg-gray-50 transition border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-signature text-indigo-500"></i> Signature Upload
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 transform transition duration-200" id="arrow-signature"></i>
                </button>
                <div id="acc-signature" class="p-4 flex flex-col items-center justify-center hidden">
                    <span class="text-[9px] font-black text-gray-400 uppercase mb-2">Beneficiary Signature</span>
                    <img id="view_signature_img" src="" alt="No signature uploaded" class="max-h-32 border border-gray-200 rounded shadow-sm bg-gray-50 p-2 object-contain" />
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
            <a id="view_ben_review_btn" href="" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs shadow transition mr-2 hidden">Review & Approve</a>
            <button type="button" onclick="closeViewBenModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow transition">Close Details</button>
        </div>
    </div>
</div>

@if($isOwnerDept)
@section('scripts')
<script>
    const BASE_URL = "{{ asset('') }}";
    function switchMainTab(tabId, saveToStorage = true) {
        const panels = document.querySelectorAll('.main-tab-panel');
        panels.forEach(p => p.classList.add('hidden'));
        
        const activePanel = document.getElementById('main-tab-content-' + tabId);
        if (activePanel) {
            activePanel.classList.remove('hidden');
        }

        // Update buttons
        const btnWorkers = document.getElementById('main-tab-btn-workers');
        const btnMaster = document.getElementById('main-tab-btn-master');

        const activeClass = "bg-indigo-700 text-white shadow-md font-extrabold";
        const inactiveClass = "text-gray-500 hover:text-gray-800 hover:bg-gray-50 border border-transparent font-bold";

        if (tabId === 'workers') {
            if (btnWorkers) btnWorkers.className = "flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-xs transition duration-200 " + activeClass;
            if (btnMaster) btnMaster.className = "flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-xs transition duration-200 " + inactiveClass;
        } else {
            if (btnWorkers) btnWorkers.className = "flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-xs transition duration-200 " + inactiveClass;
            if (btnMaster) btnMaster.className = "flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-xs transition duration-200 " + activeClass;
        }

        if (saveToStorage) {
            localStorage.setItem('owner_dept_main_tab', tabId);
        }
    }

    function scrollToRegistry(tabId) {
        // Switch main tab first
        switchMainTab('master', true);
        // Switch registry sub-tab
        switchRegistryTab(tabId);
        // Scroll to element smoothly
        setTimeout(() => {
            const element = document.getElementById('master-registry-section');
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }, 50);
    }

    function switchRegistryTab(tabId) {
        const tabs = ['dept', 'ngo', 'emp', 'cnt'];
        tabs.forEach(t => {
            const btn = document.getElementById('registry-tab-' + t);
            if (btn) {
                btn.className = "px-3 py-1.5 rounded-lg hover:text-gray-800 transition";
            }
        });
        const activeBtn = document.getElementById('registry-tab-' + tabId);
        if (activeBtn) {
            activeBtn.className = "px-3 py-1.5 rounded-lg bg-white text-gray-850 font-extrabold shadow-sm transition";
        }

        const panels = document.querySelectorAll('.registry-panel');
        panels.forEach(p => p.classList.add('hidden'));
        const activePanel = document.getElementById('registry-content-' + tabId);
        if (activePanel) {
            activePanel.classList.remove('hidden');
        }
    }

    function openViewBenModal(worker) {
        document.getElementById('view_ben_name').innerText = worker.full_name || 'N/A';
        document.getElementById('view_ben_status').innerText = 'Verification: ' + (worker.status || 'pending').toUpperCase();

        const photoImg = document.getElementById('view_ben_photo');
        const avatarIcon = document.getElementById('view_ben_avatar_icon');
        if (worker.photo_url) {
            const cleanPath = worker.photo_url.startsWith('/') ? worker.photo_url.substring(1) : worker.photo_url;
            photoImg.src = BASE_URL + cleanPath;
            photoImg.classList.remove('hidden');
            avatarIcon.classList.add('hidden');
        } else {
            photoImg.src = '';
            photoImg.classList.add('hidden');
            avatarIcon.classList.remove('hidden');
        }

        // 1. Personal Details
        document.getElementById('view_father_name').innerText = worker.father_name || 'N/A';
        document.getElementById('view_gender').innerText = (worker.gender || 'N/A').toUpperCase();
        
        let dobStr = 'N/A';
        if (worker.dob) {
            if (typeof worker.dob === 'string') {
                dobStr = worker.dob.split('T')[0];
            } else if (worker.dob.date) {
                dobStr = worker.dob.date.split(' ')[0];
            }
        }
        document.getElementById('view_dob').innerText = dobStr;
        document.getElementById('view_marital').innerText = (worker.marital_status || 'N/A').toUpperCase();
        document.getElementById('view_category').innerText = worker.category || 'N/A';
        document.getElementById('view_mobile').innerText = worker.mobile || 'N/A';
        document.getElementById('view_district').innerText = worker.district || 'N/A';
        document.getElementById('view_state').innerText = worker.state || 'N/A';

        // 2. Education Details
        document.getElementById('view_qualification').innerText = worker.qualification || 'N/A';
        document.getElementById('view_school').innerText = worker.school_institution || 'N/A';

        // 3. Employment Details
        document.getElementById('view_job_profile').innerText = worker.job_profile || 'N/A';
        document.getElementById('view_experience').innerText = worker.experience_years !== undefined ? worker.experience_years + ' Years' : 'N/A';
        document.getElementById('view_wage').innerText = worker.daily_wage !== undefined ? '₹' + worker.daily_wage : 'N/A';
        document.getElementById('view_employment_site').innerText = worker.employment_site || 'N/A';

        // 4. Bank Details
        document.getElementById('view_bank_acc').innerText = worker.bank_account_number || 'N/A';
        document.getElementById('view_bank_name').innerText = worker.bank_name_branch || 'N/A';
        document.getElementById('view_bank_ifsc').innerText = worker.bank_ifsc || 'N/A';

        // 5. BOCW Details
        document.getElementById('view_bocw_card').innerText = worker.bocw_card_number || 'N/A';
        
        let bocwReg = 'N/A';
        if (worker.bocw_reg_date) {
            if (typeof worker.bocw_reg_date === 'string') {
                bocwReg = worker.bocw_reg_date.split('T')[0];
            } else if (worker.bocw_reg_date.date) {
                bocwReg = worker.bocw_reg_date.date.split(' ')[0];
            }
        }
        document.getElementById('view_bocw_reg_date').innerText = bocwReg;

        let bocwValid = 'N/A';
        if (worker.bocw_valid_upto) {
            if (typeof worker.bocw_valid_upto === 'string') {
                bocwValid = worker.bocw_valid_upto.split('T')[0];
            } else if (worker.bocw_valid_upto.date) {
                bocwValid = worker.bocw_valid_upto.date.split(' ')[0];
            }
        }
        document.getElementById('view_bocw_valid').innerText = bocwValid;

        // 6. PDS Details
        document.getElementById('view_ration_card_number').innerText = worker.ration_card_number || 'N/A';
        document.getElementById('view_ration_address').innerText = worker.aadhaar_address || 'N/A';

        const familyTable = document.getElementById('view_family_members_list');
        familyTable.innerHTML = '';
        if (worker.family_members && Array.isArray(worker.family_members) && worker.family_members.length > 0) {
            worker.family_members.forEach(member => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="p-2 border-b border-gray-100 font-bold text-gray-800">${member.name || 'N/A'}</td>
                    <td class="p-2 border-b border-gray-100">${member.relation || 'N/A'}</td>
                    <td class="p-2 border-b border-gray-100 uppercase text-[10px]">${member.gender || 'N/A'}</td>
                    <td class="p-2 border-b border-gray-100">${member.age !== undefined ? member.age + ' Years' : 'N/A'}</td>
                `;
                familyTable.appendChild(tr);
            });
        } else {
            familyTable.innerHTML = `
                <tr>
                    <td colspan="4" class="p-4 text-center text-gray-400">No family member records found.</td>
                </tr>
            `;
        }

        // 7. Signature Details
        const sigImg = document.getElementById('view_signature_img');
        if (worker.signature_url) {
            const cleanSigPath = worker.signature_url.startsWith('/') ? worker.signature_url.substring(1) : worker.signature_url;
            sigImg.src = BASE_URL + cleanSigPath;
            sigImg.classList.remove('hidden');
        } else {
            sigImg.src = '';
            sigImg.classList.add('hidden');
        }

        // Action Review Button setup for Pending state
        const reviewBtn = document.getElementById('view_ben_review_btn');
        if (reviewBtn) {
            const wStatus = worker.status || 'pending';
            if (wStatus === 'pending') {
                reviewBtn.href = worker.signed_review_url || '';
                reviewBtn.classList.remove('hidden');
            } else {
                reviewBtn.classList.add('hidden');
            }
        }

        // Collapse all accordions by default on load
        ['personal', 'education', 'employment', 'bank', 'bocw', 'pds', 'signature'].forEach(sec => {
            const accEl = document.getElementById('acc-' + sec);
            const arrowEl = document.getElementById('arrow-' + sec);
            if (accEl) accEl.classList.add('hidden');
            if (arrowEl) arrowEl.classList.remove('rotate-180');
        });

        // Open Modal
        const modal = document.getElementById('viewBenModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeViewBenModal() {
        const modal = document.getElementById('viewBenModal');
        if (modal) modal.classList.add('hidden');
    }

    function toggleAccordion(sectionId) {
        const acc = document.getElementById('acc-' + sectionId);
        const arrow = document.getElementById('arrow-' + sectionId);
        if (acc && arrow) {
            if (acc.classList.contains('hidden')) {
                acc.classList.remove('hidden');
                arrow.classList.add('rotate-180');
            } else {
                acc.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        }
    }

    // Auto-init main tab on page load
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        let activeTab = 'workers';

        if (urlParams.has('status')) {
            activeTab = 'workers';
            localStorage.setItem('owner_dept_main_tab', 'workers');
        } else if (urlParams.has('tab')) {
            activeTab = urlParams.get('tab');
            localStorage.setItem('owner_dept_main_tab', activeTab);
        } else {
            const savedTab = localStorage.getItem('owner_dept_main_tab');
            if (savedTab === 'workers' || savedTab === 'master') {
                activeTab = savedTab;
            }
        }
        switchMainTab(activeTab, false);
    });
</script>
@endsection
@endif

@endsection
