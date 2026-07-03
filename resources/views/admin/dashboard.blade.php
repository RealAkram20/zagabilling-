@extends('layouts.admin')

@section('title', 'Dashboard')

@php
    $money = fn ($n) => $n >= 1000 ? '$' . number_format($n / 1000, 1) . 'k' : '$' . number_format($n, 0);

    $trend = $metrics['revenue_trend'];
    $trendText = $trend === null ? 'vs previous period' : (($trend >= 0 ? '▲ ' : '▼ ') . abs($trend) . '% vs previous');
    $trendColor = $trend === null ? '#8A909A' : ($trend >= 0 ? '#0F7B54' : '#C2453D');

    $stops = [];
    $acc = 0;
    foreach ($distribution['segments'] as $seg) {
        $start = $acc;
        $acc += $seg['percent'];
        $stops[] = "{$seg['color']} {$start}% {$acc}%";
    }
    $donut = $distribution['total'] > 0 ? 'conic-gradient(' . implode(',', $stops) . ')' : '#C3C7CE';
@endphp

@section('content')
<x-page-header title="Dashboard" subtitle="Portfolio health across every financed device.">
    <x-slot name="actions">
        <div class="flex bg-white border border-[#E4E6EB] rounded-[9px] p-[3px]">
            @foreach ($periods as $p)
                <a href="{{ route('admin.dashboard', ['period' => $p]) }}"
                   class="px-3 py-1.5 rounded-md text-[12.5px] font-{{ $period === $p ? 'semibold' : 'medium' }} {{ $period === $p ? 'bg-brand-50 text-brand' : 'text-[#787E88]' }}">{{ $p }} days</a>
            @endforeach
        </div>
    </x-slot>
</x-page-header>

<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3.5 mb-4">
    <x-stat-tile label="Total devices" :value="number_format($metrics['total_devices'])"
                 sub="+{{ number_format($metrics['new_devices']) }} this period" subColor="#0F7B54" icon="devices" />
    <x-stat-tile label="Active" :value="number_format($metrics['active'])" sub="{{ $metrics['active_share'] }}% of fleet">
        <span class="w-2 h-2 rounded-full" style="background:#2FA372"></span>
    </x-stat-tile>
    <x-stat-tile label="Locked" :value="number_format($metrics['locked'])" sub="Needs attention" subColor="#C2453D">
        <span class="w-2 h-2 rounded-full" style="background:#C2453D"></span>
    </x-stat-tile>
    <x-stat-tile label="Overdue" :value="number_format($metrics['overdue'])" sub="In grace or past due">
        <span class="w-2 h-2 rounded-full" style="background:#C69214"></span>
    </x-stat-tile>
    <x-stat-tile label="Revenue" :value="$money($metrics['revenue'])" :sub="$trendText" :subColor="$trendColor" icon="dollar" />
    <x-stat-tile label="Payments today" :value="number_format($metrics['today']['count'])"
                 sub="{{ $money((float) $metrics['today']['amount']) }} collected" icon="payments" />
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="lg:col-span-2 bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
        <div class="flex items-start justify-between flex-wrap gap-3 mb-5">
            <div>
                <div class="text-[14.5px] font-semibold">Collections over time</div>
                <div class="flex items-baseline gap-2 mt-2">
                    <span class="tnum text-[26px] font-bold tracking-[-0.02em]">{{ $money($collections['total']) }}</span>
                    <span class="text-[12px] text-[#787E88] font-medium">last 12 months</span>
                </div>
            </div>
            <div class="flex gap-3.5 items-center">
                <span class="flex items-center gap-1.5 text-[11.5px] text-[#787E88]"><span class="w-[9px] h-[9px] rounded-[2px] bg-brand"></span>Collected</span>
                <span class="flex items-center gap-1.5 text-[11.5px] text-[#787E88]"><span class="w-[9px] h-[9px] rounded-[2px] bg-brand-100"></span>Scheduled</span>
            </div>
        </div>
        <div class="flex items-end gap-2.5 h-[190px]">
            <div class="flex flex-col justify-between h-full text-[10px] text-[#B0B5BD] tnum text-right pr-1 pb-5">
                <span>{{ $money($collections['max']) }}</span>
                <span>{{ $money($collections['max'] / 2) }}</span>
                <span>0</span>
            </div>
            <div class="flex-1 flex items-end gap-1.5 sm:gap-2.5 h-full">
                @foreach ($collections['series'] as $point)
                    @php
                        $bg = $collections['max'] > 0 ? max(round(max($point['scheduled'], $point['collected']) / $collections['max'] * 100), 2) : 2;
                        $col = $bg > 0 && max($point['scheduled'], $point['collected']) > 0 ? round($point['collected'] / max($point['scheduled'], $point['collected']) * 100) : 0;
                    @endphp
                    <div class="flex-1 flex flex-col items-center justify-end gap-2 h-full">
                        <div class="w-full max-w-[26px] rounded-t-[5px] bg-brand-100 relative" style="height: {{ $bg }}%">
                            <div class="absolute bottom-0 inset-x-0 rounded-t-[5px] bg-brand" style="height: {{ $col }}%"></div>
                        </div>
                        <span class="text-[10px] text-[#A6ABB4]">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
        <div class="text-[14.5px] font-semibold">Device status</div>
        <div class="text-[12px] text-[#8A909A] mb-4">Fleet distribution</div>
        <div class="flex justify-center my-4">
            <div class="w-[150px] h-[150px] rounded-full flex items-center justify-center" style="background: {{ $donut }};">
                <div class="w-[98px] h-[98px] rounded-full bg-white flex flex-col items-center justify-center">
                    <span class="tnum text-[22px] font-bold tracking-[-0.02em]">{{ number_format($distribution['total']) }}</span>
                    <span class="text-[10.5px] text-[#9AA0AA]">devices</span>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-2.5">
            @foreach ($distribution['segments'] as $seg)
                <div class="flex items-center justify-between text-[12.5px]">
                    <span class="flex items-center gap-2 text-[#565b64]"><span class="w-2 h-2 rounded-[2px]" style="background: {{ $seg['color'] }}"></span>{{ $seg['label'] }}</span>
                    <span class="tnum font-semibold">{{ number_format($seg['count']) }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white border border-[#E9EBEF] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden">
        <div class="px-5 py-4 border-b border-[#EEF0F3] flex items-center justify-between">
            <div class="text-[14.5px] font-semibold">Recent payments</div>
            <a href="{{ route('admin.payments.index') }}" class="text-[12px] text-brand font-medium">View all</a>
        </div>
        <div class="overflow-x-auto">
            <div class="min-w-[420px]">
                <div class="grid grid-cols-[1.3fr_1fr_0.9fr_auto] gap-3 px-5 py-2.5 text-[10.5px] font-semibold uppercase tracking-wide text-[#A6ABB4] border-b border-[#F1F2F4]">
                    <span>Client</span><span>Account</span><span class="text-right">Amount</span><span class="text-right">Status</span>
                </div>
                @forelse ($recentPayments as $payment)
                    <div class="grid grid-cols-[1.3fr_1fr_0.9fr_auto] gap-3 px-5 py-3 text-[13px] items-center border-b border-[#F4F5F7] last:border-0">
                        <span class="font-medium truncate">{{ $payment->client->name ?? '—' }}</span>
                        <span class="tnum text-[#787E88]">{{ $payment->device->account_number ?? '—' }}</span>
                        <span class="tnum text-right font-semibold">${{ number_format((float) $payment->amount, 2) }}</span>
                        <span class="text-right"><x-status-badge :status="$payment->status" /></span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-[13px] text-[#9AA0AA]">No payments yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white border border-[#E9EBEF] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden">
        <div class="px-5 py-4 border-b border-[#EEF0F3] flex items-center justify-between">
            <div class="text-[14.5px] font-semibold">Unlock codes issued</div>
            <a href="{{ route('admin.audit.index') }}" class="text-[12px] text-brand font-medium">View all</a>
        </div>
        <div class="p-2">
            @forelse ($recentUnlockCodes as $code)
                <div class="flex items-center gap-3 px-3 py-3">
                    <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center flex-none text-brand">
                        <x-icon name="unlock" class="w-[15px] h-[15px]" sw="2" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[13px] font-medium truncate">{{ $code->device?->account_number ?? '—' }} · {{ $code->device?->client?->name ?? '—' }}</div>
                        <div class="text-[11.5px] text-[#9AA0AA] mt-0.5">{{ ucfirst($code->type) }} unlock · {{ $code->issuer->name ?? 'System' }}</div>
                    </div>
                    <span class="text-[11px] text-[#A6ABB4] flex-none">{{ $code->created_at->diffForHumans(null, true) }}</span>
                </div>
            @empty
                <div class="px-3 py-8 text-center text-[13px] text-[#9AA0AA]">No unlock codes yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
