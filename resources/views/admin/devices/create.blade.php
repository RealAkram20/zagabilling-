@extends('layouts.admin')

@section('title', 'Register device')

@section('content')
<div class="flex items-center gap-2 text-[12.5px] text-[#9AA0AA] mb-4">
    <a href="{{ route('admin.devices.index') }}" class="text-[#787E88] hover:text-brand">Devices</a>
    <span>/</span>
    <span class="text-[#1A1D23]">Register</span>
</div>

<h1 class="text-[24px] font-bold tracking-[-0.025em]">Register device</h1>
<p class="text-[13.5px] text-[#787E88] mt-1 mb-6">Captured when the offline lock client is installed. Assign a client and plan later from the device page.</p>

<div class="max-w-2xl bg-white border border-[#E9EBEF] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] p-6">
    @if ($errors->any())
        <div class="mb-4 px-4 py-3 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[13px] text-[#B23A30]">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.devices.store') }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Device name</label>
                <input name="name" value="{{ old('name') }}" placeholder="e.g. Daniel's MacBook"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Model</label>
                <input name="model" value="{{ old('model') }}" placeholder="e.g. MacBook Pro 14&quot; M3"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Selling price <span class="text-[#C2453D]">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[12px] text-[#9AA0AA]">{{ trim(currency_prefix()) }}</span>
                    <input name="price" type="number" step="0.01" min="0" value="{{ old('price') }}" required placeholder="0.00"
                           class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg pl-12 pr-3 text-[13px] tnum outline-none focus:border-brand">
                </div>
                <p class="text-[11px] text-[#9AA0AA] mt-1">The price this unit is sold for. Deposit and installments are derived from it at enrollment.</p>
            </div>
            <div>
                <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Serial number</label>
                <input name="serial" value="{{ old('serial') }}" placeholder="Read from the device at enrollment"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] tnum outline-none focus:border-brand">
                <p class="text-[11px] text-[#9AA0AA] mt-1.5">Leave blank. The device reports its real serial, model and manufacturer when it enrolls.</p>
            </div>
            <div>
                <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Account number</label>
                <input name="account_number" value="{{ old('account_number') }}" placeholder="Auto-generated if left blank"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] tnum outline-none focus:border-brand">
                <p class="text-[11px] text-[#9AA0AA] mt-1">The number shown on the device lock screen. Leave blank to generate one.</p>
            </div>
        </div>

        <div class="pt-2 border-t border-[#EEF0F3]">
            <div class="flex items-center gap-2 text-[13px] font-semibold text-[#8A2B23] mt-3 mb-1"><x-icon name="shield" class="w-4 h-4" /> Recovery vault</div>
            <p class="text-[11.5px] text-[#9AA0AA] mb-3">Encrypted at rest. Provided by the offline client at install time.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">BIOS password</label>
                    <x-password-input name="bios_password" autocomplete="off" placeholder="e.g. BWHR-VZWV-L8IY" class="tnum" />
                </div>
                <div>
                    <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">BitLocker recovery key</label>
                    <x-password-input name="recovery_key" autocomplete="off" placeholder="e.g. C1EXLK-3YGTCI-P8VFHY-FPBLNY" class="tnum" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Uninstall code <span class="text-[#9AA0AA] font-normal">(from the device app)</span></label>
                    <input name="uninstall_code" value="{{ old('uninstall_code') }}" autocomplete="off" placeholder="Generated by the offline app at install — record it here"
                           class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] tnum outline-none focus:border-brand">
                    <p class="text-[11px] text-[#9AA0AA] mt-1">The offline lock client generates this on the device. Type it in exactly as shown so you can authorize uninstalling later.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 pt-1">
            <button type="submit" class="h-10 px-4 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Register device</button>
            <a href="{{ route('admin.devices.index') }}" class="h-10 px-4 flex items-center rounded-lg border border-[#E4E6EB] bg-white text-[13px] font-medium text-[#4A4F58]">Cancel</a>
        </div>
    </form>
</div>
@endsection
