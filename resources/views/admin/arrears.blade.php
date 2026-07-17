@extends('layouts.admin')

@section('title', 'Arrears')

@section('content')
<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-[24px] font-bold tracking-[-0.025em]">Arrears</h1>
        <p class="text-[13px] text-[#787E88] mt-1">Financed devices past their payment date, longest overdue first.</p>
    </div>
    <div class="flex items-center gap-2 flex-none">
        <span class="px-3 h-8 inline-flex items-center gap-1.5 rounded-full bg-[#FBF3E2] border border-[#F0E2C0] text-[12px] font-semibold text-[#8A6A14] tnum">{{ $graceCount }} in grace</span>
        <span class="px-3 h-8 inline-flex items-center gap-1.5 rounded-full bg-[#FBEAE8] border border-[#F1C9C4] text-[12px] font-semibold text-[#B23A30] tnum">{{ $overdueCount }} overdue</span>
        @if ($lockedCount)
            <span class="px-3 h-8 inline-flex items-center gap-1.5 rounded-full bg-[#F1F0FC] border border-[#E4E3F6] text-[12px] font-semibold text-brand tnum">{{ $lockedCount }} locked</span>
        @endif
    </div>
</div>

@if ($devices->isEmpty())
    <div class="bg-white border border-[#E9EBEF] rounded-[16px] p-12 text-center">
        <div class="w-12 h-12 rounded-full bg-[#EAF7F0] flex items-center justify-center mx-auto mb-4">
            <x-icon name="check" class="w-5 h-5 text-[#2E8B57]" sw="2.2" />
        </div>
        <div class="text-[15px] font-semibold">Nobody is behind</div>
        <p class="text-[13px] text-[#787E88] mt-1">Every financed device is inside its payment window.</p>
    </div>
@else
    <div class="bg-white border border-[#E9EBEF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="text-left text-[11.5px] font-semibold text-[#9AA0AA] border-b border-[#EEF0F3]">
                        <th class="px-5 py-3">Device</th>
                        <th class="px-5 py-3">Client</th>
                        <th class="px-5 py-3">Contact</th>
                        <th class="px-5 py-3">Due</th>
                        <th class="px-5 py-3 text-right">Days past</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($devices as $device)
                        @php
                            [$badgeBg, $badgeText] = match ($device->status) {
                                \App\Models\Device::STATUS_GRACE => ['bg-[#FBF3E2] border-[#F0E2C0]', 'text-[#8A6A14]'],
                                \App\Models\Device::STATUS_LOCKED => ['bg-[#F1F0FC] border-[#E4E3F6]', 'text-brand'],
                                default => ['bg-[#FBEAE8] border-[#F1C9C4]', 'text-[#B23A30]'],
                            };
                        @endphp
                        <tr class="border-b border-[#F4F5F7] last:border-0 hover:bg-[#FAFBFC]">
                            <td class="px-5 py-3.5">
                                <a href="{{ route('admin.devices.show', $device) }}" class="font-semibold tnum text-brand hover:underline">{{ $device->account_number }}</a>
                                <div class="text-[11.5px] text-[#9AA0AA] truncate max-w-[180px]">{{ $device->model ?: '—' }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-medium">{{ $device->client?->name ?? '—' }}</div>
                                @if ($device->client?->national_id)
                                    <div class="text-[11.5px] text-[#9AA0AA] tnum">ID {{ $device->client->national_id }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($device->client?->phone)
                                    <a href="tel:{{ $device->client->phone }}" class="tnum text-[#1A1D23] hover:text-brand">{{ $device->client->phone }}</a>
                                @elseif (! $device->client?->hasAltContact())
                                    <span class="text-[#9AA0AA]">—</span>
                                @endif
                                {{-- The fallback number is the whole point of this column once a
                                     client stops answering, so it sits here rather than a click away. --}}
                                @if ($device->client?->hasAltContact())
                                    <a href="tel:{{ $device->client->alt_contact_phone }}" class="flex items-center gap-1 text-[11.5px] tnum text-[#787E88] hover:text-brand mt-0.5">
                                        <x-icon name="phone" class="w-3 h-3 flex-none" /> {{ $device->client->alt_contact_phone }}
                                        <span class="text-[#9AA0AA] truncate max-w-[110px]">· {{ $device->client->altContactLabel() }}</span>
                                    </a>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 tnum text-[#565b64]">{{ $device->next_due_at?->format('d M Y') ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-right tnum font-semibold {{ $device->days_past_due > 30 ? 'text-[#B23A30]' : 'text-[#565b64]' }}">{{ $device->days_past_due }}</td>
                            <td class="px-5 py-3.5">
                                <span class="px-2.5 h-6 inline-flex items-center rounded-full border {{ $badgeBg }} {{ $badgeText }} text-[11px] font-semibold capitalize">{{ $device->status }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
