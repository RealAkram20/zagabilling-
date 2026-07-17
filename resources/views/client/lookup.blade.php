@extends('layouts.portal')

@section('title', 'Unlock your device')

@section('content')
<div class="text-center">
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $appName }}" class="h-12 w-auto max-w-[220px] object-contain mx-auto mb-5">
    @elseif ($iconUrl)
        <img src="{{ $iconUrl }}" alt="{{ $appName }}" class="w-14 h-14 rounded-[15px] object-contain mx-auto mb-5">
    @else
        <div class="w-14 h-14 rounded-[15px] bg-brand flex items-center justify-center mx-auto mb-5 shadow-[0_6px_20px_rgba(75,69,199,.3)]">
            <x-icon name="unlock" class="w-[26px] h-[26px] text-white" sw="2" />
        </div>
    @endif
    <h1 class="text-[24px] font-bold tracking-[-0.025em]">Unlock your device</h1>
    <p class="text-[14px] text-[#787E88] leading-[1.55] mt-2 mb-6">Enter the device account number shown on your locked screen to continue.</p>
</div>

<div class="bg-white border border-[#E9EBEF] rounded-[16px] p-6 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
    @if ($errors->any())
        <div class="mb-4 px-4 py-3 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[13px] text-[#B23A30]">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('portal.find') }}">
        @csrf
        <label class="block text-[12px] font-medium text-[#565b64] mb-1.5">Device account number</label>
        <input name="account_number" value="{{ old('account_number') }}" placeholder="ZG-40000" required autofocus
               class="w-full h-[52px] border border-[#E4E6EB] rounded-[11px] px-3 text-[20px] font-semibold tnum tracking-[0.08em] text-center outline-none focus:border-brand mb-4">
        <button class="w-full h-12 rounded-[11px] bg-brand text-white text-[13px] font-semibold shadow-[0_2px_8px_rgba(75,69,199,.32)]">Continue</button>
    </form>
</div>

<p class="text-center text-[12px] text-[#A6ABB4] mt-5">Locked out and need help? Call <span class="font-semibold text-[#565b64] tnum">+1 800 555 0100</span></p>
@endsection
