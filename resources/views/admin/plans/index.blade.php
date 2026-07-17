@extends('layouts.admin')

@section('title', 'Plans')

@section('content')
<x-page-header title="Plans" subtitle="Installment templates applied to devices at enrollment. The device price sets the amounts; the plan sets the terms." />

@if (session('status'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-[#E9F6EF] border border-[#BFE6D2] text-[13px] text-[#0F7B54]">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 px-4 py-3 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[13px] text-[#B23A30]">{{ $errors->first() }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-3">
        @forelse ($plans as $plan)
            <div class="bg-white border border-[#E9EBEF] rounded-[13px] shadow-[0_1px_2px_rgba(16,20,28,.03)]"
                 x-data="{ editing: false }">
                <div class="px-5 py-4 flex items-center gap-4 flex-wrap">
                    <div class="flex-1 min-w-[160px]">
                        <div class="text-[15px] font-semibold">{{ $plan->name }}</div>
                        <div class="text-[12.5px] text-[#787E88] mt-0.5">{{ $plan->term_months }} installments · {{ $plan->cadenceLabel() }} · <span class="tnum">{{ rtrim(rtrim(number_format((float) $plan->deposit_percentage, 2), '0'), '.') }}%</span> deposit</div>
                    </div>
                    <div class="w-px h-9 bg-[#EEF0F3] hidden sm:block"></div>
                    <div class="text-center">
                        <div class="tnum text-[18px] font-bold">{{ number_format($plan->devices_count) }}</div>
                        <div class="text-[11px] text-[#9AA0AA]">devices</div>
                    </div>
                    @can('manage-plans')
                        <div class="w-px h-9 bg-[#EEF0F3] hidden sm:block"></div>
                        <div class="flex items-center gap-2">
                            <button @click="editing = !editing" class="w-9 h-9 rounded-lg border border-[#E4E6EB] bg-white flex items-center justify-center text-[#787E88] hover:bg-[#FBFBFC]" title="Edit plan">
                                <x-icon name="pencil" class="w-3.5 h-3.5" />
                            </button>
                            <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" data-confirm="Delete the {{ $plan->name }} plan?">
                                @csrf
                                @method('DELETE')
                                <button class="w-9 h-9 rounded-lg border border-[#E7C9C4] bg-white flex items-center justify-center text-[#B23A30] hover:bg-[#FDF6F5]" title="Delete plan">
                                    <x-icon name="trash" class="w-3.5 h-3.5" />
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>

                @can('manage-plans')
                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.plans.update', $plan) }}"
                          class="px-5 pb-5 pt-1 border-t border-[#EEF0F3] space-y-3" x-data="{ cadence: '{{ $plan->cadence }}' }">
                        @csrf
                        @method('PATCH')
                        <input name="name" value="{{ $plan->name }}" required placeholder="Plan name"
                               class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] text-[#787E88] mb-1">Installments</label>
                                <input name="term_months" type="number" min="1" max="120" value="{{ $plan->term_months }}" required
                                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] tnum outline-none focus:border-brand">
                            </div>
                            <div>
                                <label class="block text-[11px] text-[#787E88] mb-1">Deposit %</label>
                                <input name="deposit_percentage" type="number" step="0.01" min="0" max="100" value="{{ rtrim(rtrim(number_format((float) $plan->deposit_percentage, 2), '0'), '.') }}" required
                                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] tnum outline-none focus:border-brand">
                            </div>
                        </div>
                        <div>
                            <div class="text-[11.5px] font-medium text-[#565b64] mb-1.5">Cadence</div>
                            <input type="hidden" name="cadence" :value="cadence">
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (['monthly' => 'Monthly', 'biweekly' => 'Bi-weekly', 'weekly' => 'Weekly'] as $value => $label)
                                    <button type="button" @click="cadence='{{ $value }}'"
                                            class="h-9 rounded-lg text-[12.5px] font-medium border transition"
                                            :class="cadence==='{{ $value }}' ? 'bg-brand-50 text-brand border-brand-100' : 'bg-[#FBFBFC] text-[#787E88] border-[#E4E6EB]'">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button class="h-10 px-4 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Save changes</button>
                            <button type="button" @click="editing=false" class="h-10 px-4 rounded-lg border border-[#E4E6EB] bg-white text-[13px] font-medium text-[#4A4F58]">Cancel</button>
                        </div>
                    </form>
                @endcan
            </div>
        @empty
            <div class="bg-white border border-[#E9EBEF] rounded-[13px] px-5 py-10 text-center text-[13px] text-[#9AA0AA]">No plans yet.</div>
        @endforelse
        <div>{{ $plans->links() }}</div>
    </div>

    @can('manage-plans')
    <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)] h-fit">
        <div class="text-[14.5px] font-semibold">Create plan</div>
        <div class="text-[12px] text-[#8A909A] mb-4">Set the number of installments and the required deposit.</div>
        <form method="POST" action="{{ route('admin.plans.store') }}" class="space-y-3" x-data="{ cadence: '{{ old('cadence', 'monthly') }}' }">
            @csrf
            <input name="name" value="{{ old('name') }}" placeholder="e.g. 24-month Standard" required
                   class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] text-[#787E88] mb-1">Installments</label>
                    <input name="term_months" type="number" min="1" max="120" value="{{ old('term_months') }}" placeholder="e.g. 24" required
                           class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] tnum outline-none focus:border-brand">
                </div>
                <div>
                    <label class="block text-[11px] text-[#787E88] mb-1">Deposit %</label>
                    <input name="deposit_percentage" type="number" step="0.01" min="0" max="100" value="{{ old('deposit_percentage', 0) }}" placeholder="e.g. 20" required
                           class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] tnum outline-none focus:border-brand">
                </div>
            </div>
            <div>
                <div class="text-[11.5px] font-medium text-[#565b64] mb-1.5">Cadence</div>
                <input type="hidden" name="cadence" :value="cadence">
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['monthly' => 'Monthly', 'biweekly' => 'Bi-weekly', 'weekly' => 'Weekly'] as $value => $label)
                        <button type="button" @click="cadence='{{ $value }}'"
                                class="h-9 rounded-lg text-[12.5px] font-medium border transition"
                                :class="cadence==='{{ $value }}' ? 'bg-brand-50 text-brand border-brand-100' : 'bg-[#FBFBFC] text-[#787E88] border-[#E4E6EB]'">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <button class="w-full h-10 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Create plan</button>
        </form>
    </div>
    @endcan
</div>
@endsection
