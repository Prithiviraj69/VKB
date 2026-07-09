<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'VishwaKarma Bandhu Portal')</title>
    <!-- Tailwind CSS (Local) -->
    <script src="{{ (str_contains(request()->root(), 'localhost') || str_contains(request()->root(), '127.0.0.1')) ? asset('js/tailwind.js') : asset('public/js/tailwind.js') }}"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#E65100', // Orange
                        verifier: '#2E7D32', // Green
                        approver: '#1565C0', // Blue
                        ngo: '#8E24AA', // Purple
                        employer: '#3949AB', // Indigo
                        contractor: '#00897B', // Teal
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/jpg" href="{{ (str_contains(request()->root(), 'localhost') || str_contains(request()->root(), '127.0.0.1')) ? asset('logo.jpg') : asset('public/logo.jpg') }}">
    @yield('styles')
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="min-h-screen flex flex-col md:flex-row relative">
        <!-- Sidebar Mobile Backdrop -->
        <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-30 hidden md:hidden" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed md:static inset-y-0 left-0 z-40 w-64 bg-slate-900 text-white flex flex-col justify-between shrink-0 shadow-xl transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
            <div>
                <!-- Brand Title -->
                <div class="p-4 border-b border-slate-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ (str_contains(request()->root(), 'localhost') || str_contains(request()->root(), '127.0.0.1')) ? asset('logo.jpg') : asset('public/logo.jpg') }}" alt="Vishwakarma Bandhu Logo" class="w-10 h-10 rounded-lg object-cover border border-slate-700 shadow shrink-0" />
                        <div class="flex flex-col min-w-0">
                            <span class="font-black text-sm text-orange-500 tracking-wider">VKB Portal</span>
                            <span class="font-bold text-[9px] text-slate-400 uppercase tracking-tight truncate">
                            @if(Auth::user()->role === 'super-admin')
                                Super Admin
                            @elseif(Auth::user()->role === 'owner-dept-admin')
                                Owner Dept Admin
                            @elseif(Auth::user()->role === 'govt-dept-admin')
                                Govt. Dept Admin
                            @elseif(Auth::user()->role === 'dept-verifier')
                                Dept. Verifier
                            @elseif(Auth::user()->role === 'dept-approver')
                                Dept. Approver
                            @elseif(Auth::user()->role === 'verifier')
                                Verifier Portal
                            @elseif(Auth::user()->role === 'approver')
                                Approver Portal
                            @elseif(Auth::user()->role === 'ngo')
                                NGO Console
                            @elseif(Auth::user()->role === 'employer')
                                Employer Portal
                            @elseif(Auth::user()->role === 'contractor')
                                Contractor Portal
                            @endif
                        </span>
                    </div>
                </div>
                    <!-- Close button for mobile -->
                    <button type="button" onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-white transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Navigation Menu -->
                <nav class="p-4 space-y-2">
                    @if(Auth::user()->role === 'super-admin')
                        <a href="{{ route('super-admin.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('super-admin.index') ? 'bg-brand text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="{{ route('super-admin.onboard') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('super-admin.onboard') ? 'bg-brand text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-square-plus text-lg"></i>
                            <span>Onboard Hub</span>
                        </a>
                        <a href="{{ route('super-admin.trades') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('super-admin.trades') ? 'bg-brand text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-shapes text-lg"></i>
                            <span>Manage Trades</span>
                        </a>
                        <a href="{{ route('admin.activity-logs') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.activity-logs') ? 'bg-brand text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-list-check text-lg"></i>
                            <span>Activity Logs</span>
                        </a>
                    @elseif(in_array(Auth::user()->role, ['govt-dept-admin', 'owner-dept-admin']))
                        <a href="{{ route('govt-dept-admin.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('govt-dept-admin.index') ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="{{ route('owner-dept.onboard') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('owner-dept.onboard') ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-square-plus text-lg"></i>
                            <span>Onboard Hub</span>
                        </a>
                        <a href="{{ route('owner-dept.beneficiaries') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('owner-dept.beneficiaries') ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-users text-lg"></i>
                            <span>Beneficiary Console</span>
                        </a>
                        <a href="{{ route('govt-dept-admin.users') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('govt-dept-admin.users') ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-users-gear text-lg"></i>
                            <span>Manage Users</span>
                            @php
                                $myUsersCount = \App\Models\User::whereIn('role', ['dept-verifier','dept-approver'])->where('created_by', Auth::id())->count();
                            @endphp
                            @if($myUsersCount > 0)
                                <span class="ml-auto bg-indigo-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full">{{ $myUsersCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('govt-dept-admin.schemes') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('govt-dept-admin.schemes') ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-shapes text-lg"></i>
                            <span>Manage Schemes</span>
                        </a>
                        @if(Auth::user()->role === 'owner-dept-admin')
                            <a href="{{ route('admin.activity-logs') }}" 
                               class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.activity-logs') ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <i class="fa-solid fa-list-check text-lg"></i>
                                <span>Activity Logs</span>
                            </a>
                        @endif
                    @elseif(Auth::user()->role === 'dept-verifier')
                        <a href="{{ route('dept-verifier.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('dept-verifier.index') ? 'bg-verifier text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ route('dept-verifier.scheme-applications') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('dept-verifier.scheme-applications') ? 'bg-verifier text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-shapes text-lg"></i>
                            <span>Scheme Applications</span>
                        </a>
                        <a href="{{ route('dept-verifier.grievances') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('dept-verifier.grievances') ? 'bg-verifier text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-envelope text-lg"></i>
                            <span>Grievances</span>
                        </a>
                    @elseif(Auth::user()->role === 'dept-approver')
                        <a href="{{ route('dept-approver.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('dept-approver.index') ? 'bg-approver text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ route('dept-approver.scheme-applications') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('dept-approver.scheme-applications') ? 'bg-approver text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-shapes text-lg"></i>
                            <span>Scheme Applications</span>
                        </a>
                        <a href="{{ route('dept-approver.grievances') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('dept-approver.grievances') ? 'bg-approver text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-envelope text-lg"></i>
                            <span>Grievances</span>
                        </a>
                    @elseif(Auth::user()->role === 'verifier')
                        <a href="{{ route('verifier.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('verifier.index') ? 'bg-verifier text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ route('verifier.scheme-applications') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('verifier.scheme-applications') || request()->routeIs('verifier.schemes') ? 'bg-verifier text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-shapes text-lg"></i>
                            <span>Scheme Applications</span>
                        </a>
                        <a href="{{ route('verifier.grievances') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('verifier.grievances') ? 'bg-verifier text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-envelope text-lg"></i>
                            <span>Grievances</span>
                        </a>
                    @elseif(Auth::user()->role === 'approver')
                        <a href="{{ route('approver.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('approver.index') ? 'bg-approver text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="{{ route('approver.ngos') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('approver.ngos') ? 'bg-approver text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-handshake text-lg"></i>
                            <span>NGOs</span>
                        </a>
                        <a href="{{ route('approver.employers') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('approver.employers') ? 'bg-approver text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-industry text-lg"></i>
                            <span>Employers</span>
                        </a>
                        <a href="{{ route('approver.contractors') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('approver.contractors') ? 'bg-approver text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-helmet-safety text-lg"></i>
                            <span>Contractors</span>
                        </a>

                        <a href="{{ route('approver.scheme-applications') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('approver.scheme-applications') || request()->routeIs('approver.schemes') ? 'bg-approver text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-shapes text-lg"></i>
                            <span>Scheme Applications</span>
                        </a>
                        <a href="{{ route('approver.grievances') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('approver.grievances') ? 'bg-approver text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-envelope text-lg"></i>
                            <span>Grievances</span>
                        </a>
                    @elseif(Auth::user()->role === 'ngo')
                        <a href="{{ route('ngo.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ngo.index') ? 'bg-ngo text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="{{ route('ngo.users') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ngo.users') ? 'bg-ngo text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-user-gear text-lg"></i>
                            <span>Manage Staff</span>
                        </a>
                        <a href="{{ route('ngo.employers') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ngo.employers') ? 'bg-ngo text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-industry text-lg"></i>
                            <span>Employers</span>
                        </a>
                        <a href="{{ route('ngo.contractors') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ngo.contractors') ? 'bg-ngo text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-helmet-safety text-lg"></i>
                            <span>Contractors</span>
                        </a>
                        <a href="{{ route('ngo.beneficiaries') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ngo.beneficiaries') ? 'bg-ngo text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-users text-lg"></i>
                            <span>Beneficiaries</span>
                        </a>
                        <a href="{{ route('ngo.schemes') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ngo.schemes') ? 'bg-ngo text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-shapes text-lg"></i>
                            <span>Schemes</span>
                        </a>
                    @elseif(Auth::user()->role === 'ngo-verifier')
                        <a href="{{ route('ngo-verifier.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ngo-verifier.index') ? 'bg-ngo text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="{{ route('ngo-verifier.scheme-applications') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ngo-verifier.scheme-applications') ? 'bg-ngo text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-file-signature text-lg"></i>
                            <span>Scheme Applications</span>
                        </a>
                    @elseif(Auth::user()->role === 'ngo-approver')
                        <a href="{{ route('ngo-approver.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ngo-approver.index') ? 'bg-ngo text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="{{ route('ngo-approver.beneficiaries') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ngo-approver.beneficiaries') ? 'bg-ngo text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-users text-lg"></i>
                            <span>Beneficiaries</span>
                        </a>
                        <a href="{{ route('ngo-approver.scheme-applications') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ngo-approver.scheme-applications') ? 'bg-ngo text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-file-signature text-lg"></i>
                            <span>Scheme Applications</span>
                        </a>
                    @elseif(Auth::user()->role === 'employer')
                        <a href="{{ route('employer.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('employer.index') ? 'bg-employer text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="{{ route('employer.contractors') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('employer.contractors') ? 'bg-employer text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-helmet-safety text-lg"></i>
                            <span>Contractors</span>
                        </a>
                        <a href="{{ route('employer.beneficiaries') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('employer.beneficiaries') ? 'bg-employer text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-users text-lg"></i>
                            <span>Beneficiaries</span>
                        </a>
                    @elseif(Auth::user()->role === 'contractor')
                        <a href="{{ route('contractor.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('contractor.index') ? 'bg-contractor text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="{{ route('contractor.beneficiaries') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition {{ request()->routeIs('contractor.beneficiaries') ? 'bg-contractor text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-users text-lg"></i>
                            <span>Beneficiaries</span>
                        </a>
                    @endif
                </nav>
            </div>

            <!-- Profile Section -->
            <div class="p-4 bg-slate-950 border-t border-slate-900 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center shrink-0 border border-slate-700">
                    <i class="fa-solid fa-user text-slate-300"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-xs truncate">{{ Auth::user()->name }}</h4>
                    <p class="text-[10px] text-slate-400 truncate">{{ Auth::user()->email }}</p>
                </div>
            </div>
        </aside>

        <!-- Main Body -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Header bar -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:px-8 shadow-sm">
                <div class="flex items-center gap-3">
                    <!-- Mobile Sidebar Toggle Hamburger -->
                    <button type="button" onclick="toggleSidebar()" class="md:hidden p-2 rounded-xl text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition focus:outline-none">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <h1 class="text-sm md:text-xl font-bold text-gray-800 truncate max-w-[150px] sm:max-w-none">
                        VishwaKarma Bandhu Portal
                    </h1>
                </div>
                <div class="flex items-center gap-2 md:gap-4">
                    <span class="px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wider
                        @if(Auth::user()->role === 'super-admin') bg-orange-100 text-orange-800
                        @elseif(Auth::user()->role === 'govt-dept-admin') bg-indigo-100 text-indigo-800
                        @elseif(Auth::user()->role === 'owner-dept-admin') bg-violet-100 text-violet-800
                        @elseif(Auth::user()->role === 'dept-verifier') bg-green-100 text-green-800
                        @elseif(Auth::user()->role === 'dept-approver') bg-blue-100 text-blue-800
                        @elseif(Auth::user()->role === 'verifier') bg-green-100 text-green-800
                        @elseif(Auth::user()->role === 'approver') bg-blue-100 text-blue-800
                        @elseif(Auth::user()->role === 'ngo' || Auth::user()->role === 'ngo-verifier' || Auth::user()->role === 'ngo-approver') bg-purple-100 text-purple-800
                        @elseif(Auth::user()->role === 'employer') bg-orange-100 text-orange-800
                        @elseif(Auth::user()->role === 'contractor') bg-teal-100 text-teal-800
                        @endif">
                        {{ Auth::user()->role }}
                    </span>

                    <!-- Profile Button -->
                    <button type="button" onclick="openProfileModal()" title="My Profile & Institutional Info" class="focus:outline-none flex items-center justify-center">
                        <i class="fa-solid fa-circle-user text-2xl text-gray-500 hover:text-indigo-600 transition"></i>
                    </button>

                    <!-- Logout Button -->
                    <a href="{{ route('logout') }}" 
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-50 border border-rose-100 text-rose-600 hover:bg-rose-100 hover:text-rose-700 transition duration-200 text-xs font-black shadow-sm">
                        <i class="fa-solid fa-right-from-bracket text-sm"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="flex-1 p-4 md:p-8 overflow-y-auto">
                <!-- Alert Messages -->
                @if(session('success'))
                    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 text-sm flex items-center gap-3 shadow-sm animate-fade-in">
                        <i class="fa-solid fa-circle-check text-lg text-emerald-600"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-900 text-sm flex items-center gap-3 shadow-sm">
                        <i class="fa-solid fa-circle-exclamation text-lg text-rose-600"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
    @php
        $profileInst = null;
        if (in_array(Auth::user()->role, ['govt-dept-admin', 'owner-dept-admin'])) {
            $profileInst = \App\Models\Department::where('department_code', Auth::user()->department_code)->first();
        } elseif (in_array(Auth::user()->role, ['ngo', 'ngo-verifier', 'ngo-approver'])) {
            $ngoId = Auth::user()->ngo_id;
            if ($ngoId) {
                $profileInst = \App\Models\Ngo::find($ngoId);
            } else {
                $profileInst = \App\Models\Ngo::where('email', Auth::user()->email)->first();
            }
        } elseif (Auth::user()->role === 'employer') {
            $profileInst = \App\Models\Employer::where('email', Auth::user()->email)->first();
        } elseif (Auth::user()->role === 'contractor') {
            $profileInst = \App\Models\Contractor::where('email', Auth::user()->email)->first();
        }
    @endphp

    <!-- Global Profile Details Modal -->
    <div id="globalProfileModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeProfileModal()"></div>
        
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl w-full max-w-md p-6 border border-gray-100 shadow-2xl transition-all duration-300 transform scale-95 opacity-0">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-address-card text-indigo-600"></i>
                    <span>Profile & Institutional Info</span>
                </h3>
                <button type="button" onclick="closeProfileModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <div class="mt-4 space-y-4 text-xs">
                <!-- User Basic Details -->
                <div class="bg-indigo-50/50 p-4 rounded-xl space-y-2">
                    <span class="px-2 py-0.5 text-[8px] font-black uppercase rounded bg-indigo-200 text-indigo-800 tracking-wider">Account User</span>
                    <div class="font-bold text-gray-850 text-sm mt-1">{{ Auth::user()->name }}</div>
                    <div class="font-mono text-gray-500">{{ Auth::user()->email }}</div>
                    <div class="text-[10px] text-gray-400 capitalize">Role: <span class="font-bold">{{ str_replace('-', ' ', Auth::user()->role) }}</span></div>
                </div>

                <!-- Institutional / Department Details -->
                @if($profileInst)
                    <div class="border-t border-gray-100 pt-4 space-y-3 text-gray-700">
                        <span class="px-2 py-0.5 text-[8px] font-black uppercase rounded bg-emerald-200 text-emerald-800 tracking-wider">Institutional Registry</span>
                        
                        @if(in_array(Auth::user()->role, ['govt-dept-admin', 'owner-dept-admin']))
                            <div class="grid grid-cols-2 gap-3 mt-1">
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">Dept Name</div>
                                    <div class="font-bold text-gray-850">{{ $profileInst->department_name }}</div>
                                </div>
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">Dept Code</div>
                                    <div class="font-mono font-bold text-indigo-650">{{ $profileInst->department_code }}</div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-[9px] uppercase font-black text-gray-400">Parent Department</div>
                                    <div class="font-bold text-gray-800">{{ $profileInst->parent_department_name ?? 'N/A' }}</div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-[9px] uppercase font-black text-gray-400">Official Email</div>
                                    <div class="font-mono text-gray-800">{{ $profileInst->email }}</div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-[9px] uppercase font-black text-gray-400">Office Address</div>
                                    <div class="text-gray-800">{{ $profileInst->office_address }}</div>
                                </div>
                            </div>
                        @elseif(in_array(Auth::user()->role, ['ngo', 'ngo-verifier', 'ngo-approver']))
                            <div class="grid grid-cols-2 gap-3 mt-1">
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">NGO Name</div>
                                    <div class="font-bold text-gray-850">{{ $profileInst->ngo_name }}</div>
                                </div>
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">Reg No</div>
                                    <div class="font-mono font-bold text-gray-800">{{ $profileInst->registration_no }}</div>
                                </div>
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">Contact Person</div>
                                    <div class="font-bold text-gray-800">{{ $profileInst->contact_person }}</div>
                                </div>
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">Mobile</div>
                                    <div class="font-bold text-gray-800">{{ $profileInst->mobile }}</div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-[9px] uppercase font-black text-gray-400">Office Address</div>
                                    <div class="text-gray-800">{{ $profileInst->office_address }}</div>
                                </div>
                            </div>
                        @elseif(Auth::user()->role === 'employer')
                            <div class="grid grid-cols-2 gap-3 mt-1">
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">Employer Name</div>
                                    <div class="font-bold text-gray-850">{{ $profileInst->employer_name }}</div>
                                </div>
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">Reg No</div>
                                    <div class="font-mono font-bold text-gray-800">{{ $profileInst->registration_no }}</div>
                                </div>
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">Contact Person</div>
                                    <div class="font-bold text-gray-800">{{ $profileInst->contact_person }}</div>
                                </div>
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">Mobile</div>
                                    <div class="font-bold text-gray-800">{{ $profileInst->mobile }}</div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-[9px] uppercase font-black text-gray-400">Office Address</div>
                                    <div class="text-gray-800">{{ $profileInst->office_address }}</div>
                                </div>
                            </div>
                        @elseif(Auth::user()->role === 'contractor')
                            <div class="grid grid-cols-2 gap-3 mt-1">
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">Contractor Name</div>
                                    <div class="font-bold text-gray-850">{{ $profileInst->contractor_name }}</div>
                                </div>
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">License No</div>
                                    <div class="font-mono font-bold text-gray-800">{{ $profileInst->license_no }}</div>
                                </div>
                                <div>
                                    <div class="text-[9px] uppercase font-black text-gray-400">Mobile</div>
                                    <div class="font-bold text-gray-800">{{ $profileInst->mobile }}</div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-[9px] uppercase font-black text-gray-400">Office Address</div>
                                    <div class="text-gray-800">{{ $profileInst->office_address }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                @elseif(Auth::user()->department_code)
                    <div class="border-t border-gray-100 pt-4 space-y-2 text-gray-700">
                        <span class="px-2 py-0.5 text-[8px] font-black uppercase rounded bg-indigo-100 text-indigo-755 tracking-wider">Associated Department</span>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-gray-400 font-bold">Department Code:</span>
                            <span class="font-mono font-bold bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded">{{ Auth::user()->department_code }}</span>
                        </div>
                    </div>
                @endif
            </div>
            
            <div class="flex justify-end pt-3 mt-4 border-t border-gray-100">
                <button type="button" onclick="closeProfileModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">Close</button>
            </div>
        </div>
    </div>

    <!-- Toggle Profile Javascript -->
    <script>
        function openProfileModal() {
            const modal = document.getElementById('globalProfileModal');
            const modalContent = modal.querySelector('.relative');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeProfileModal() {
            const modal = document.getElementById('globalProfileModal');
            const modalContent = modal.querySelector('.relative');
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    </script>
    
    <!-- Prevent Double Form Submission -->
    <script>
        document.addEventListener('submit', function(e) {
            const form = e.target;
            setTimeout(() => {
                if (e.defaultPrevented) return;
                const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                submitButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('opacity-60', 'cursor-not-allowed');
                    btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin mr-2"></i> Processing...';
                });
            }, 0);
        });
    </script>
    
    <!-- Responsive Sidebar Toggle Javascript -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }
    </script>
    @yield('scripts')
</body>
</html>
