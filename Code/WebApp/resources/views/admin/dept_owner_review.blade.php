@extends('layouts.admin')

@section('title', 'Review Self-Registration — Department Owner')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('dept-owner.self-registrations') }}" class="text-gray-400 hover:text-gray-600 transition">
            <i class="fa-solid fa-arrow-left text-lg"></i>
        </a>
        <div>
            <h2 class="text-2xl font-black text-gray-800">Review Self-Registration</h2>
            <p class="text-xs text-gray-500 mt-0.5">Verify or Approve this self-registered worker profile.</p>
        </div>
    </div>

    {{-- Worker Profile Card --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-yellow-50 border-b border-yellow-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-yellow-100 flex items-center justify-center">
                    <i class="fa-solid fa-user text-yellow-600 text-base"></i>
                </div>
                <div>
                    <h3 class="font-black text-gray-800 text-sm">{{ $worker['full_name'] ?? 'N/A' }}</h3>
                    <p class="text-[10px] text-yellow-700">Self-Registered Worker</p>
                </div>
            </div>
            <span class="px-3 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider
                @php $workerStatus = $worker['status'] ?? 'pending'; @endphp
                @if($workerStatus === 'approved') bg-blue-100 text-blue-800
                @elseif($workerStatus === 'verified') bg-green-100 text-green-800
                @elseif($workerStatus === 'rejected') bg-red-100 text-red-800
                @else bg-amber-100 text-amber-800
                @endif">
                {{ $workerStatus }}
            </span>
        </div>

        <div class="p-6 grid grid-cols-2 gap-x-8 gap-y-4 text-xs">
            <div><span class="font-black text-gray-500 uppercase tracking-wider text-[10px]">Father's Name</span><p class="font-semibold text-gray-800 mt-0.5">{{ $worker['father_name'] ?? 'N/A' }}</p></div>
            <div><span class="font-black text-gray-500 uppercase tracking-wider text-[10px]">Date of Birth</span><p class="font-semibold text-gray-800 mt-0.5">{{ is_string($worker['dob'] ?? null) ? explode(' ', $worker['dob'])[0] : 'N/A' }}</p></div>
            <div><span class="font-black text-gray-500 uppercase tracking-wider text-[10px]">Gender</span><p class="font-semibold text-gray-800 mt-0.5">{{ $worker['gender'] ?? 'N/A' }}</p></div>
            <div><span class="font-black text-gray-500 uppercase tracking-wider text-[10px]">Marital Status</span><p class="font-semibold text-gray-800 mt-0.5">{{ $worker['marital_status'] ?? 'N/A' }}</p></div>
            <div><span class="font-black text-gray-500 uppercase tracking-wider text-[10px]">Aadhaar Number</span><p class="font-semibold text-gray-800 mt-0.5 font-mono">{{ $worker['aadhaar_number'] ?? 'N/A' }}</p></div>
            <div><span class="font-black text-gray-500 uppercase tracking-wider text-[10px]">Job Trade</span><p class="font-semibold text-yellow-700 bg-yellow-50 px-2 py-0.5 rounded w-fit mt-0.5">{{ $worker['job_profile'] ?? 'N/A' }}</p></div>
            <div class="col-span-2"><span class="font-black text-gray-500 uppercase tracking-wider text-[10px]">Address</span><p class="font-semibold text-gray-800 mt-0.5">{{ $worker['aadhaar_address'] ?? 'N/A' }}</p></div>
            <div><span class="font-black text-gray-500 uppercase tracking-wider text-[10px]">Bank Account No</span><p class="font-semibold text-gray-800 mt-0.5 font-mono">{{ $worker['bank_account_number'] ?? 'N/A' }}</p></div>
            <div><span class="font-black text-gray-500 uppercase tracking-wider text-[10px]">IFSC Code</span><p class="font-semibold text-gray-800 mt-0.5 font-mono">{{ $worker['bank_ifsc'] ?? 'N/A' }}</p></div>
            <div class="col-span-2"><span class="font-black text-gray-500 uppercase tracking-wider text-[10px]">Bank Name & Branch</span><p class="font-semibold text-gray-800 mt-0.5">{{ $worker['bank_name_branch'] ?? 'N/A' }}</p></div>
        </div>
    </div>

    {{-- Actions --}}
    @php $wId = $worker['_id'] ?? $worker['id'] ?? ''; @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Verify --}}
        @if(in_array($workerStatus, ['pending', 'rejected']))
        <div class="bg-white rounded-2xl border border-green-200 shadow-sm p-5">
            <h4 class="font-black text-green-700 text-sm mb-3 flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i> Verify Profile
            </h4>
            <form action="{{ route('dept-owner.verify', $wId) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Remarks (optional)</label>
                    <textarea name="remarks" rows="2" placeholder="Verification notes..." class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-green-400 transition"></textarea>
                </div>
                <button type="submit" class="w-full py-2.5 bg-green-600 text-white font-bold rounded-xl text-xs hover:bg-green-500 transition">
                    ✅ Mark as Verified
                </button>
            </form>
        </div>
        @endif

        {{-- Approve --}}
        @if($workerStatus === 'verified')
        <div class="bg-white rounded-2xl border border-blue-200 shadow-sm p-5">
            <h4 class="font-black text-blue-700 text-sm mb-3 flex items-center gap-2">
                <i class="fa-solid fa-gavel"></i> Approve Profile
            </h4>
            <form action="{{ route('dept-owner.approve', $wId) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Remarks (optional)</label>
                    <textarea name="remarks" rows="2" placeholder="Approval notes..." class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-400 transition"></textarea>
                </div>
                <button type="submit" class="w-full py-2.5 bg-blue-600 text-white font-bold rounded-xl text-xs hover:bg-blue-500 transition">
                    ⚖️ Approve Registration
                </button>
            </form>
        </div>
        @endif

        {{-- Reject --}}
        @if(in_array($workerStatus, ['pending', 'verified']))
        <div class="bg-white rounded-2xl border border-red-200 shadow-sm p-5">
            <h4 class="font-black text-red-700 text-sm mb-3 flex items-center gap-2">
                <i class="fa-solid fa-times-circle"></i> Reject & Return
            </h4>
            <form action="{{ route('dept-owner.reject', $wId) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-[10px] font-black text-gray-600 uppercase mb-1">Rejection Reason *</label>
                    <textarea name="remarks" rows="2" placeholder="Explain why rejected..." required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-red-400 transition"></textarea>
                </div>
                <button type="submit" class="w-full py-2.5 bg-red-600 text-white font-bold rounded-xl text-xs hover:bg-red-500 transition">
                    ❌ Reject Registration
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Previous Remarks --}}
    @if(!empty($worker['verifier_remarks']) || !empty($worker['approver_remarks']))
    <div class="bg-gray-50 rounded-2xl border border-gray-200 p-5 space-y-3">
        <h4 class="font-black text-gray-700 text-xs uppercase tracking-wider">Previous Remarks</h4>
        @if(!empty($worker['verifier_remarks']))
            <div><span class="text-[10px] font-bold text-green-700 uppercase">Verifier:</span><p class="text-xs text-gray-700 mt-0.5">{{ $worker['verifier_remarks'] }}</p></div>
        @endif
        @if(!empty($worker['approver_remarks']))
            <div><span class="text-[10px] font-bold text-blue-700 uppercase">Approver:</span><p class="text-xs text-gray-700 mt-0.5">{{ $worker['approver_remarks'] }}</p></div>
        @endif
    </div>
    @endif

</div>
@endsection
