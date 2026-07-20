@extends('layouts.portal')

@section('title', 'Payment received')

@section('content')
@include('client._dots', ['active' => 4])

<div class="text-center mb-6">
    <div class="w-[60px] h-[60px] rounded-full bg-[#E6F4EE] flex items-center justify-center mx-auto mb-4">
        <x-icon name="check" class="w-[30px] h-[30px] text-[#0F7B54]" sw="2.4" />
    </div>
    <h1 class="text-[24px] font-bold tracking-[-0.025em]">Payment received</h1>
    <p class="text-[14px] text-[#787E88] mt-1">Your device is ready to unlock. Enter the code below on the locked laptop.</p>
</div>

<div class="bg-white border border-[#E9EBEF] rounded-[16px] p-6 shadow-[0_1px_2px_rgba(16,20,28,.03)]"
     x-data="{ copied: false, copy() { navigator.clipboard.writeText(@js($unlockCode?->code)); this.copied = true; setTimeout(() => this.copied = false, 1500); } }">
    @if ($unlockCode)
        <div class="text-[11.5px] uppercase tracking-[0.06em] text-[#9AA0AA] mb-2">Your unlock code</div>
        <div class="flex items-center justify-between gap-3">
            <span class="tnum text-[28px] sm:text-[32px] font-bold tracking-[0.05em] text-brand font-mono break-all">{{ $unlockCode->code }}</span>
            <button @click="copy()" class="w-[38px] h-[38px] rounded-lg border border-[#E4E6EB] flex items-center justify-center text-[#787E88] flex-none">
                <x-icon name="copy" class="w-4 h-4" x-show="!copied" />
                <x-icon name="check" class="w-4 h-4 text-[#0F7B54]" x-show="copied" x-cloak />
            </button>
        </div>
        <div class="text-[12px] text-[#9AA0AA] mt-2">Valid until {{ $unlockCode->expires_at->format('M j, g:i A') }} — enter it before then; the paid time is yours either way.</div>
        @if ($device->next_due_at)
            <div class="mt-4 flex items-center justify-between px-4 py-3 rounded-[11px] bg-[#E6F4EE] border border-[#CBE7DA]">
                <span class="text-[12px] text-[#0F7B54]">
                    @if (($installments = $unlockCode->payment?->installments_count) > 1)
                        {{ $installments }} installments paid — covered through
                    @else
                        Paid through
                    @endif
                </span>
                <span class="text-[13px] font-bold tnum text-[#0F7B54]">{{ $device->next_due_at->format('M j, Y') }}</span>
            </div>
        @endif
        <div class="mt-5 rounded-[11px] bg-[#FBFBFC] border border-[#EEF0F3] p-4 text-[12.5px] text-[#565b64] space-y-2">
            <div>① On the locked laptop, click <b>Enter unlock code</b>.</div>
            <div>② Type the code above exactly as shown.</div>
            <div>③ Your device unlocks in a few seconds — no restart needed.</div>
        </div>
    @else
        <p class="text-[13px] text-[#B23A30] text-center">No active unlock code found. Please contact support.</p>
    @endif
</div>

<div class="text-center mt-6"><a href="{{ route('portal.lookup') }}" class="text-[12.5px] text-[#9AA0AA] font-medium">Done</a></div>
@endsection
