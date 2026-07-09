@extends('layouts.admin')

@section('title', 'NGO Approver — Review Beneficiary')

@section('content')
<div class="max-w-4xl space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('ngo-approver.beneficiaries') }}" class="text-gray-450 hover:text-gray-600 transition">
            <i class="fa-solid fa-arrow-left text-lg text-ngo"></i>
        </a>
        <div>
            <h2 class="text-2xl font-black text-gray-800">Review Beneficiary Profile</h2>
            <p class="text-xs text-gray-500 mt-0.5">Verify and Approve this beneficiary registration for your NGO.</p>
        </div>
    </div>

    {{-- Worker Profile Card --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-purple-50/50 border-b border-purple-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100/50 flex items-center justify-center">
                    <i class="fa-solid fa-user text-ngo text-base"></i>
                </div>
                <div>
                    <h3 class="font-black text-gray-800 text-sm">{{ $worker['full_name'] ?? 'N/A' }}</h3>
                    <p class="text-[10px] text-purple-750 font-bold">NGO Registered Beneficiary</p>
                </div>
            </div>
            <span class="px-3 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider
                @php $workerStatus = $worker['status'] ?? 'pending'; @endphp
                @if($workerStatus === 'approved') bg-emerald-100 text-emerald-800 border border-emerald-250
                @elseif($workerStatus === 'rejected') bg-red-100 text-red-800 border border-red-200
                @else bg-amber-100 text-amber-800 border border-amber-200
                @endif">
                {{ $workerStatus }}
            </span>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-xs">
            <div><span class="font-black text-gray-400 uppercase tracking-wider text-[10px]">Father's Name</span><p class="font-bold text-gray-800 mt-0.5">{{ $worker['father_name'] ?? 'N/A' }}</p></div>
            <div><span class="font-black text-gray-400 uppercase tracking-wider text-[10px]">Date of Birth</span><p class="font-bold text-gray-800 mt-0.5">{{ is_string($worker['dob'] ?? null) ? explode(' ', $worker['dob'])[0] : 'N/A' }}</p></div>
            <div><span class="font-black text-gray-400 uppercase tracking-wider text-[10px]">Gender</span><p class="font-bold text-gray-800 mt-0.5">{{ $worker['gender'] ?? 'N/A' }}</p></div>
            <div><span class="font-black text-gray-400 uppercase tracking-wider text-[10px]">Marital Status</span><p class="font-bold text-gray-800 mt-0.5">{{ $worker['marital_status'] ?? 'N/A' }}</p></div>
            <div><span class="font-black text-gray-400 uppercase tracking-wider text-[10px]">Aadhaar Number</span><p class="font-bold text-gray-800 mt-0.5 font-mono">{{ $worker['aadhaar_number'] ?? 'N/A' }}</p></div>
            <div><span class="font-black text-gray-400 uppercase tracking-wider text-[10px]">Job Trade</span><p class="font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded w-fit mt-0.5">{{ $worker['job_profile'] ?? 'N/A' }}</p></div>
            <div class="col-span-2 border-t border-gray-50 pt-2"><span class="font-black text-gray-400 uppercase tracking-wider text-[10px]">Address</span><p class="font-bold text-gray-800 mt-0.5">{{ $worker['aadhaar_address'] ?? 'N/A' }}</p></div>
            <div class="border-t border-gray-50 pt-2"><span class="font-black text-gray-400 uppercase tracking-wider text-[10px]">Bank Account No</span><p class="font-bold text-gray-800 mt-0.5 font-mono">{{ $worker['bank_account_number'] ?? 'N/A' }}</p></div>
            <div class="border-t border-gray-50 pt-2"><span class="font-black text-gray-400 uppercase tracking-wider text-[10px]">IFSC Code</span><p class="font-bold text-gray-800 mt-0.5 font-mono">{{ $worker['bank_ifsc'] ?? 'N/A' }}</p></div>
            <div class="col-span-2"><span class="font-black text-gray-400 uppercase tracking-wider text-[10px]">Bank Name & Branch</span><p class="font-bold text-gray-800 mt-0.5">{{ $worker['bank_name_branch'] ?? 'N/A' }}</p></div>
        </div>
    </div>

    {{-- Actions --}}
    @php $wId = $worker['_id'] ?? $worker['id'] ?? ''; @endphp

    @if(in_array($workerStatus, ['pending', 'rejected']))
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Approve Card --}}
        <div class="bg-white rounded-2xl border border-emerald-250 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <h4 class="font-black text-emerald-800 text-sm mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i> Approve Registration
                </h4>
                <p class="text-xs text-gray-400 mb-4">Confirm profile details and issue registration number.</p>
            </div>
            <form action="{{ route('ngo-approver.approve-beneficiary', $wId) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[9px] font-black text-gray-500 uppercase mb-1">Remarks (optional)</label>
                    <textarea name="remarks" rows="2" placeholder="Approval notes..." class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"></textarea>
                </div>
                <button type="submit" class="w-full py-2.5 bg-emerald-600 text-white font-bold rounded-xl text-xs hover:bg-emerald-700 transition">
                    Approve Profile
                </button>
            </form>
        </div>

        {{-- Reject Card --}}
        <div class="bg-white rounded-2xl border border-red-250 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <h4 class="font-black text-red-800 text-sm mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-circle-xmark"></i> Reject & Return
                </h4>
                <p class="text-xs text-gray-400 mb-4">Decline this registration with specific feedback for correction.</p>
            </div>
            <form action="{{ route('ngo-approver.reject-beneficiary', $wId) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[9px] font-black text-gray-500 uppercase mb-1">Rejection Reason *</label>
                    <textarea name="remarks" rows="2" placeholder="Explain why rejected..." required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-red-500 transition"></textarea>
                </div>
                <button type="submit" class="w-full py-2.5 bg-red-650 text-white font-bold rounded-xl text-xs hover:bg-red-750 transition">
                    Reject Profile
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Previous Remarks --}}
    @if(!empty($worker['approver_remarks']))
    <div class="bg-gray-50 rounded-2xl border border-gray-200 p-5 space-y-3">
        <h4 class="font-black text-gray-700 text-xs uppercase tracking-wider">Previous Remarks</h4>
        <div>
            <span class="text-[10px] font-bold text-purple-700 uppercase">NGO Approver:</span>
            <p class="text-xs text-gray-700 mt-0.5">{{ $worker['approver_remarks'] }}</p>
        </div>
    </div>
    @endif

</div>
@endsection
