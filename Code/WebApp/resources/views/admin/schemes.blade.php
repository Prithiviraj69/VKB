@extends('layouts.admin')

@section('title', 'Board Schemes')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-gray-800 tracking-wide">Board Schemes</h2>
            <p class="text-gray-500 text-xs mt-1">Explore all welfare and grant assistance schemes available for registered workers.</p>
        </div>
        <div>
            <a href="{{ Auth::user()->role === 'verifier' ? route('verifier.index') : route('approver.index') }}" 
               class="bg-gray-200 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-gray-300 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Schemes Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @php
            $schemesList = [
                ['title' => 'Educational Assistance', 'desc' => 'Financial grants for children studying in schools, colleges, and professional courses.'],
                ['title' => 'Marriage Assistance', 'desc' => 'Marriage financial grant for self, son, or daughter of the registered worker.'],
                ['title' => 'Ex-gratia for Death', 'desc' => 'Ex-gratia relief and financial compensation in case of natural or accidental death of a worker.'],
                ['title' => 'Funeral Benefit', 'desc' => 'Funeral relief assistance provided to the nominee or dependent of the deceased worker.'],
                ['title' => 'Purchase of Tools / Protective Gear', 'desc' => 'Grant for purchase of professional trade tools, instruments, and workplace protective safety gear.'],
                ['title' => 'Medical Benefit', 'desc' => 'Reimbursement of medical treatment and surgery costs incurred in Government hospitals.'],
                ['title' => 'Maternity Benefit', 'desc' => 'Maternity financial assistance and leave relief for female registered workers.'],
                ['title' => 'Wage Loss Assistance', 'desc' => 'Compensation for wage losses during periods of hospitalization or medical rest advised by doctors.'],
                ['title' => 'Retirement Relief', 'desc' => 'Retirement relief and pension benefits upon quitting service after attaining 60 years of age.'],
                ['title' => 'House Construction Grant', 'desc' => 'Token amount grant for purchasing land or constructing a house for registered board members.'],
                ['title' => 'Atal Pension Yojana (APY) Reimbursement', 'desc' => 'Reimbursement of subscription premiums paid under the Central APY scheme.'],
                ['title' => 'Distance Education Reimbursement', 'desc' => 'Reimbursement of course and admission fees for workers undergoing distance learning.'],
                ['title' => 'Surgery / Operation Financial Assistance', 'desc' => 'Special financial assistance for major surgeries and complex clinical operations.'],
                ['title' => 'Skill Development Stipend', 'desc' => 'Reimbursement of course fee and stipend allowance for skill enhancement and training programs.'],
            ];
        @endphp

        @foreach($schemesList as $index => $sch)
            <div class="bg-white rounded-2xl p-6 border border-gray-150 shadow-sm flex flex-col justify-between hover:shadow-md hover:border-indigo-200 transition duration-200">
                <div class="space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                        {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                    </div>
                    <h3 class="font-black text-gray-800 text-sm tracking-wide">{{ $sch['title'] }}</h3>
                    <p class="text-xs text-gray-400 leading-relaxed font-semibold">{{ $sch['desc'] }}</p>
                </div>
                <div class="mt-6 border-t pt-4 flex items-center justify-between text-[10px] uppercase font-bold tracking-wider text-indigo-600">
                    <span>Eligible for all workers</span>
                    <i class="fa-solid fa-circle-info text-sm"></i>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
