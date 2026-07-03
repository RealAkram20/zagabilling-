@extends('layouts.admin')

@section('title', 'Bulk add devices')

@section('content')
<div class="flex items-center gap-2 text-[12.5px] text-[#9AA0AA] mb-4">
    <a href="{{ route('admin.devices.index') }}" class="text-[#787E88] hover:text-brand">Devices</a>
    <span>/</span>
    <span class="text-[#1A1D23]">Bulk add</span>
</div>

<h1 class="text-[24px] font-bold tracking-[-0.025em]">Bulk add to inventory</h1>
<p class="text-[13.5px] text-[#787E88] mt-1 mb-6">Add many units of the same model at once. Each serial becomes one unassigned device with an auto-generated account number. Record each unit's BIOS / BitLocker key later from its device page after the offline client is installed.</p>

<div class="max-w-2xl bg-white border border-[#E9EBEF] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] p-6">
    @if ($errors->any())
        <div class="mb-4 px-4 py-3 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[13px] text-[#B23A30]">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.devices.bulk.store') }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Model <span class="text-[#C2453D]">*</span></label>
                <input name="model" value="{{ old('model') }}" required placeholder="e.g. Lenovo X1 Carbon"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Name label <span class="text-[#9AA0AA] font-normal">(optional)</span></label>
                <input name="name" value="{{ old('name') }}" placeholder="Applied to every unit"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Selling price <span class="text-[#C2453D]">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px] text-[#9AA0AA]">$</span>
                    <input name="price" type="number" step="0.01" min="0" value="{{ old('price') }}" required placeholder="0.00"
                           class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg pl-7 pr-3 text-[13px] tnum outline-none focus:border-brand">
                </div>
                <p class="text-[11px] text-[#9AA0AA] mt-1">Applied to every unit in this batch.</p>
            </div>
        </div>
        <div x-data="{ count() { return (this.$refs.s.value.split(/\r\n|\r|\n/).map(v=>v.trim()).filter(Boolean)).length } }">
            <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Serial numbers <span class="text-[#C2453D]">*</span></label>
            <textarea name="serials" x-ref="s" rows="10" required placeholder="One serial per line&#10;C02XK1YJMD6T&#10;C02GH3AJQ05P&#10;…"
                      class="w-full border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 py-2.5 text-[13px] tnum outline-none focus:border-brand">{{ old('serials') }}</textarea>
            <p class="text-[11.5px] text-[#9AA0AA] mt-1.5">One serial per line. <span x-text="count()"></span> units detected.</p>
        </div>
        <div class="flex items-center gap-2 pt-1">
            <button type="submit" class="h-10 px-4 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Add to inventory</button>
            <a href="{{ route('admin.devices.index') }}" class="h-10 px-4 flex items-center rounded-lg border border-[#E4E6EB] bg-white text-[13px] font-medium text-[#4A4F58]">Cancel</a>
        </div>
    </form>
</div>
@endsection
