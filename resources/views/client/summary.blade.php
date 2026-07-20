@extends('layouts.portal')

@section('title', 'Payment summary')

@php $p = $device->progress(); @endphp

@section('content')
@include('client._dots', ['active' => 2])

<div class="bg-white border border-[#E9EBEF] rounded-[16px] p-6 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-[46px] h-[46px] rounded-[12px] bg-brand-50 flex items-center justify-center text-brand flex-none"><x-icon name="monitor" class="w-5 h-5" sw="1.8" /></div>
            <div class="min-w-0">
                <div class="text-[15px] font-semibold truncate">{{ $device->client?->name ?? $device->model ?? 'Device' }}</div>
                <div class="text-[12.5px] text-[#787E88] tnum">{{ $device->account_number }}</div>
            </div>
        </div>
        <x-status-badge :status="$device->status" />
    </div>

    <div class="text-center py-2">
        <div class="tnum text-[38px] font-bold tracking-[-0.02em]">{{ money($device->installmentAmount()) }}</div>
        <div class="text-[12.5px] text-[#787E88]">Installment {{ $p['current'] }} of {{ $p['total'] }}</div>
    </div>

    <div class="flex items-center justify-between text-[12px] mt-4 mb-2">
        <span class="text-[#565b64] font-medium">{{ $p['paid'] }} of {{ $p['total'] }} paid</span>
        <span class="text-[#787E88] tnum">{{ money($device->balance, 0) }} remaining</span>
    </div>
    <x-progress-bar :paid="$p['paid']" :total="$p['total']" :current="false" />

    @if ($device->next_due_at)
        @php $overdue = $device->next_due_at->isPast(); @endphp
        <div class="flex items-center justify-between mt-4 px-4 py-3 rounded-[11px] {{ $overdue ? 'bg-[#FBEAE8] border border-[#F1C9C4]' : 'bg-[#FBFAFF] border border-[#EEF0F3]' }}">
            <span class="text-[12px] {{ $overdue ? 'text-[#B23A30]' : 'text-[#787E88]' }}">{{ $overdue ? 'Payment was due' : 'Paid through' }}</span>
            <span class="text-[13px] font-semibold tnum {{ $overdue ? 'text-[#B23A30]' : '' }}">{{ $device->next_due_at->format('M j, Y') }}</span>
        </div>
    @endif

    @if ($lastCode)
        <a href="{{ route('portal.code', $device) }}" class="mt-4 flex items-center justify-between px-4 py-3 rounded-[11px] bg-brand-50 border border-brand-100">
            <span class="text-[12.5px] text-[#565b64]">Paid but didn't enter your code? <span class="font-semibold text-brand">View your last unlock code</span></span>
            <span class="text-[11px] text-[#9AA0AA] tnum flex-none ml-3">until {{ $lastCode->expires_at->format('M j, g:i A') }}</span>
        </a>
    @endif

    <a href="{{ route('portal.payment', $device) }}" class="mt-6 flex items-center justify-center w-full h-12 rounded-[11px] bg-brand text-white text-[13px] font-semibold shadow-[0_2px_8px_rgba(75,69,199,.32)]">
        Pay {{ money($device->installmentAmount()) }}
    </a>
    <p class="text-center text-[11.5px] text-[#9AA0AA] mt-4">Paying this installment clears your grace period and unlocks the device.</p>
</div>
@endsection
