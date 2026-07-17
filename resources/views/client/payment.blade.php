@extends('layouts.portal')

@section('title', 'Payment')

@php
    $per = $device->installmentAmount();
    $remaining = max((int) $device->progress()['remaining'], 1);
    $label = $device->plan->periodLabel();
    $cadenceDays = $device->plan->cadenceDays();
@endphp

@section('content')
@include('client._dots', ['active' => 3])

<div class="bg-white border border-[#E9EBEF] rounded-[16px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden"
     x-data="{ periods: 1, per: {{ $per }}, max: {{ $remaining }},
         get amount() { return Math.round(this.per * this.periods * 100) / 100; } }">
    <div class="flex items-center justify-between px-5 py-4 bg-[#FBFAFF] border-b border-[#EEF0F3]">
        <span class="text-[13px] text-[#787E88]">Paying installment {{ $device->progress()['current'] }}</span>
        <span class="tnum text-[19px] font-bold" x-text="zagaMoney(amount)"></span>
    </div>
    <form method="POST" action="{{ route('portal.pay', $device) }}" class="p-6 space-y-5">
        @csrf
        <div>
            <label class="block text-[12px] font-medium text-[#565b64] mb-2">How many {{ \Illuminate\Support\Str::plural($label) }} do you want to pay for?</label>
            <div class="flex items-center gap-3">
                <div class="flex items-center border border-[#E4E6EB] bg-[#F7F8FA] rounded-[10px] h-12 overflow-hidden">
                    <button type="button" @click="periods = Math.max(1, periods - 1)" class="w-12 h-full text-[20px] text-[#787E88] hover:bg-[#EFF1F4]">−</button>
                    <input name="installments" type="number" min="1" max="{{ $remaining }}" x-model.number="periods" @input="periods = Math.min(Math.max(1, periods||1), max)"
                           class="w-16 h-full text-center bg-transparent text-[15px] tnum font-semibold outline-none">
                    <button type="button" @click="periods = Math.min(max, periods + 1)" class="w-12 h-full text-[20px] text-[#787E88] hover:bg-[#EFF1F4]">+</button>
                </div>
                <div class="flex-1 text-right">
                    <div class="tnum text-[22px] font-bold" x-text="zagaMoney(amount)"></div>
                    <div class="text-[11px] text-[#9AA0AA]"><span x-text="periods"></span> × {{ money($per) }} / {{ $label }}</div>
                </div>
            </div>
            <p class="text-[11.5px] text-[#9AA0AA] mt-2">Keeps your device unlocked for <span x-text="periods * {{ $cadenceDays }}"></span> more days.</p>
        </div>

        <button class="w-full h-12 rounded-[11px] bg-brand text-white text-[13px] font-semibold shadow-[0_2px_8px_rgba(75,69,199,.32)] flex items-center justify-center gap-2">
            <x-icon name="lock" class="w-4 h-4" sw="2" /> Pay <span x-text="zagaMoney(amount)"></span>
        </button>
        <p class="text-center text-[11.5px] text-[#9AA0AA]">You'll be taken to PesaPal to complete payment securely. In sandbox mode payment is simulated and cleared instantly.</p>
    </form>
</div>
@endsection
