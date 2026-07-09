@extends('layouts.admin')

@section('title', 'Activity Logs')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Activity Logs</h2>
            <p class="text-gray-500 text-xs mt-1">Audit trail tracking all modifications, additions, deletions, verifications, and approvals.</p>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
        <form action="{{ route('admin.activity-logs') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search Query -->
            <div class="space-y-1">
                <label for="search" class="block text-xs font-black text-gray-400 uppercase tracking-wider">Search</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                        class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                        placeholder="Operator Name, Email, or Target...">
                </div>
            </div>

            <!-- Action Filter -->
            <div class="space-y-1">
                <label for="action" class="block text-xs font-black text-gray-400 uppercase tracking-wider">Action</label>
                <select name="action" id="action"
                    class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                            {{ $action }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Resource Type Filter -->
            <div class="space-y-1">
                <label for="resource_type" class="block text-xs font-black text-gray-400 uppercase tracking-wider">Resource Type</label>
                <select name="resource_type" id="resource_type"
                    class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                    <option value="">All Types</option>
                    @foreach($resourceTypes as $type)
                        <option value="{{ $type }}" {{ request('resource_type') == $type ? 'selected' : '' }}>
                            {{ ucfirst($type) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date Filter -->
            <div class="space-y-1">
                <label for="date" class="block text-xs font-black text-gray-400 uppercase tracking-wider">Date</label>
                <div class="flex gap-2">
                    <input type="date" name="date" id="date" value="{{ request('date') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                    
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-sm transition">
                        Apply
                    </button>
                    @if(request()->anyFilled(['search', 'action', 'resource_type', 'date']))
                        <a href="{{ route('admin.activity-logs') }}" class="bg-gray-150 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-xl text-sm font-bold border border-gray-200 flex items-center justify-center transition" title="Clear Filters">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Logs Table Card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">Operator</th>
                        <th class="px-6 py-4">Action</th>
                        <th class="px-6 py-4">Resource</th>
                        <th class="px-6 py-4">Metadata / Details</th>
                        <th class="px-6 py-4">Context</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                    @forelse($logs as $log)
                        @php
                            // Determine action badge styling
                            $actionBadge = 'bg-gray-100 text-gray-800 border-gray-200';
                            $action = strtoupper($log->action);
                            if (in_array($action, ['CREATE', 'IMPORT'])) {
                                $actionBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            } elseif ($action === 'DELETE') {
                                $actionBadge = 'bg-rose-50 text-rose-700 border-rose-200';
                            } elseif ($action === 'TOGGLE_STATUS') {
                                $actionBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                            } elseif (in_array($action, ['VERIFY', 'APPROVE'])) {
                                $actionBadge = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                            } elseif ($action === 'REJECT') {
                                $actionBadge = 'bg-red-50 text-red-700 border-red-200';
                            } elseif ($action === 'CHANGE_PASSWORD') {
                                $actionBadge = 'bg-blue-50 text-blue-700 border-blue-200';
                            }
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <!-- Timestamp -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-xs text-gray-800">
                                    {{ $log->created_at ? $log->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'N/A' }}
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    {{ $log->created_at ? $log->created_at->diffForHumans() : '' }}
                                </div>
                            </td>

                            <!-- Operator -->
                            <td class="px-6 py-4">
                                <div class="text-xs font-black text-gray-800">{{ $log->user_name }}</div>
                                <div class="text-[10px] text-gray-400 font-mono select-all">{{ $log->user_email }}</div>
                                <span class="inline-block px-1.5 py-0.5 text-[8px] font-black uppercase rounded mt-1 bg-gray-100 text-gray-600 tracking-wider">
                                    {{ $log->user_role }}
                                </span>
                            </td>

                            <!-- Action -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-lg text-xs font-extrabold border {{ $actionBadge }} uppercase tracking-wide">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <!-- Target Resource -->
                            <td class="px-6 py-4">
                                <div class="text-xs text-gray-800">
                                    {{ $log->resource_name }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-1 text-[10px]">
                                    <span class="font-extrabold uppercase text-gray-400">
                                        {{ $log->resource_type }}
                                    </span>
                                    @if($log->resource_id)
                                        <span class="text-gray-300">|</span>
                                        <span class="font-mono text-gray-400 select-all" title="Resource ID">
                                            ID: {{ $log->resource_id }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Metadata / Details -->
                            <td class="px-6 py-4 text-xs font-medium">
                                @if(!empty($log->details))
                                    <div class="space-y-1 max-w-xs">
                                        @foreach($log->details as $key => $val)
                                            <div class="flex items-start gap-1">
                                                <span class="text-gray-400 font-extrabold text-[9px] uppercase tracking-wider">{{ str_replace('_', ' ', $key) }}:</span>
                                                <span class="text-gray-700 text-xs">
                                                    @if(is_array($val))
                                                        {{ json_encode($val) }}
                                                    @else
                                                        {{ $val }}
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-300 italic text-xs">No extra metadata</span>
                                @endif
                            </td>

                            <!-- Context (IP & User Agent) -->
                            <td class="px-6 py-4 text-[10px] text-gray-400">
                                <div class="flex items-center gap-1">
                                    <i class="fa-solid fa-network-wired text-[8px]"></i>
                                    <span class="font-mono">{{ $log->ip_address }}</span>
                                </div>
                                <div class="mt-1 max-w-[150px] truncate" title="{{ $log->user_agent }}">
                                    <i class="fa-solid fa-laptop text-[8px] mr-1"></i>{{ $log->user_agent }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fa-solid fa-list-check text-4xl text-gray-250"></i>
                                    <p class="font-bold">No activity logs found matching the selected criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="p-6 border-t border-gray-100 bg-gray-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
