@extends('layouts.admin')

@section('title', 'Devices')

@section('content')
<x-page-header title="Devices" subtitle="{{ number_format($devices->total()) }} devices · {{ number_format($inventory) }} in inventory (unassigned).">
    <x-slot name="actions">
        @can('manage-devices')
            <a href="{{ route('admin.devices.bulk') }}"
               class="flex items-center gap-2 border border-[#E4E6EB] bg-white text-[#4A4F58] rounded-[9px] px-3.5 py-2.5 text-[13px] font-medium hover:bg-[#FBFBFC]">
                <x-icon name="plus" class="w-[15px] h-[15px]" sw="2.2" /> Bulk add
            </a>
            <a href="{{ route('admin.devices.create') }}"
               class="flex items-center gap-2 bg-brand text-white rounded-[9px] px-4 py-2.5 text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">
                <x-icon name="plus" class="w-[15px] h-[15px]" sw="2.2" /> Add device
            </a>
        @endcan
    </x-slot>
</x-page-header>

@if ($inventoryByModel->isNotEmpty())
    <div class="mb-5">
        <div class="text-[11.5px] font-semibold uppercase tracking-wide text-[#A6ABB4] mb-2">In stock by model</div>
        <div class="flex gap-2 flex-wrap">
            @foreach ($inventoryByModel as $item)
                <a href="{{ route('admin.devices.index', ['status' => 'unassigned', 'model' => $item['model'] === 'Unspecified' ? null : $item['model']]) }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg border border-[#E9EBEF] bg-white hover:bg-[#FBFAFF] transition">
                    <span class="text-[12.5px] font-medium">{{ $item['model'] }}</span>
                    <span class="tnum text-[12px] font-bold text-brand bg-brand-50 rounded-md px-1.5 py-0.5">{{ $item['total'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif

<form method="GET" class="flex flex-wrap items-center gap-2.5 mb-3.5">
    <div class="relative flex-1 min-w-[200px] max-w-xs">
        <x-icon name="search" class="w-[15px] h-[15px] text-[#A6ABB4] absolute left-3 top-1/2 -translate-y-1/2" sw="2" />
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search account, serial, client…"
               class="w-full h-9 border border-[#E4E6EB] bg-white rounded-lg pl-9 pr-3 text-[13px] outline-none focus:border-brand">
    </div>
    <select name="status" class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] text-[#4A4F58] outline-none">
        <option value="">All statuses</option>
        @foreach (['unassigned', 'active', 'grace', 'overdue', 'locked', 'closed'] as $status)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <select name="plan_id" class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] text-[#4A4F58] outline-none">
        <option value="">All plans</option>
        @foreach ($plans as $plan)
            <option value="{{ $plan->id }}" @selected((string) ($filters['plan_id'] ?? '') === (string) $plan->id)>{{ $plan->name }}</option>
        @endforeach
    </select>
    <select name="model" class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] text-[#4A4F58] outline-none">
        <option value="">All models</option>
        @foreach ($models as $m)
            <option value="{{ $m }}" @selected(($filters['model'] ?? '') === $m)>{{ $m }}</option>
        @endforeach
    </select>
    <button class="h-9 px-3.5 rounded-lg bg-brand text-white text-[12.5px] font-medium">Filter</button>
</form>

<div class="bg-white border border-[#E9EBEF] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden">
    <div class="overflow-x-auto">
        <div class="min-w-[860px]">
            <div class="grid grid-cols-[1.1fr_1.1fr_1.3fr_1fr_0.95fr_0.9fr_0.9fr] gap-3.5 px-5 py-3 text-[10.5px] font-semibold uppercase tracking-wide text-[#A6ABB4] bg-[#FBFBFC] border-b border-[#EEF0F3]">
                <span>Account</span><span>Serial</span><span>Client</span><span>Plan</span>
                <span class="text-right">Balance</span><span>Status</span><span>Next due</span>
            </div>
            @forelse ($devices as $device)
                <a href="{{ route('admin.devices.show', $device) }}"
                   class="grid grid-cols-[1.1fr_1.1fr_1.3fr_1fr_0.95fr_0.9fr_0.9fr] gap-3.5 px-5 py-3.5 text-[13px] items-center border-b border-[#F4F5F7] last:border-0 hover:bg-[#FBFAFF] transition">
                    <span class="tnum font-semibold text-brand">{{ $device->account_number }}</span>
                    <span class="tnum text-[#787E88] truncate">{{ $device->serial }}</span>
                    <span class="font-medium truncate">{{ $device->client->name ?? 'Unassigned' }}</span>
                    <span class="text-[#565b64] truncate">{{ $device->plan->name ?? '—' }}</span>
                    <span class="tnum text-right font-semibold">${{ number_format((float) $device->balance, 0) }}</span>
                    <span><x-status-badge :status="$device->status" /></span>
                    <span class="tnum text-[#787E88]">{{ $device->next_due_at?->format('M j') ?? '—' }}</span>
                </a>
            @empty
                <div class="px-5 py-10 text-center text-[13px] text-[#9AA0AA]">No devices match your filters.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="mt-4">{{ $devices->links() }}</div>
@endsection
