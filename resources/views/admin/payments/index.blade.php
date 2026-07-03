@extends('layouts.admin')

@section('title', 'Payments')

@section('content')
<x-page-header title="Payments" subtitle="Every transaction settled through the gateway." />

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-4">
    <div class="bg-white border border-[#E9EBEF] rounded-[13px] p-4 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
        <div class="text-[11.5px] text-[#8A909A] font-medium">Collected today</div>
        <div class="tnum text-[23px] font-bold mt-2">${{ number_format((float) $summary['collected_today'], 0) }}</div>
    </div>
    <div class="bg-white border border-[#E9EBEF] rounded-[13px] p-4 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
        <div class="text-[11.5px] text-[#8A909A] font-medium">Pending settlement</div>
        <div class="tnum text-[23px] font-bold mt-2 text-[#8A6410]">${{ number_format((float) $summary['pending'], 0) }}</div>
    </div>
    <div class="bg-white border border-[#E9EBEF] rounded-[13px] p-4 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
        <div class="text-[11.5px] text-[#8A909A] font-medium">Failed (7 days)</div>
        <div class="tnum text-[23px] font-bold mt-2 text-[#B23A30]">${{ number_format((float) $summary['failed_7d'], 0) }}</div>
    </div>
</div>

<form method="GET" class="flex flex-wrap items-center gap-2.5 mb-3.5">
    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] text-[#4A4F58] outline-none">
    <span class="text-[#A6ABB4] text-[12.5px]">to</span>
    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] text-[#4A4F58] outline-none">
    <select name="status" class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] text-[#4A4F58] outline-none">
        <option value="">All statuses</option>
        @foreach (['paid', 'pending', 'failed'] as $status)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <select name="method" class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] text-[#4A4F58] outline-none">
        <option value="">All methods</option>
        @foreach ($methods as $method)
            <option value="{{ $method }}" @selected(($filters['method'] ?? '') === $method)>{{ $method }}</option>
        @endforeach
    </select>
    <button class="h-9 px-3.5 rounded-lg bg-brand text-white text-[12.5px] font-medium">Filter</button>
</form>

<div class="bg-white border border-[#E9EBEF] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden">
    <div class="overflow-x-auto">
        <div class="min-w-[820px]">
            <div class="grid grid-cols-[1fr_1fr_1.3fr_0.9fr_1.1fr_auto] gap-3.5 px-5 py-3 text-[10.5px] font-semibold uppercase tracking-wide text-[#A6ABB4] bg-[#FBFBFC] border-b border-[#EEF0F3]">
                <span>Date</span><span>Account</span><span>Client</span><span class="text-right">Amount</span><span>Method</span><span class="text-right">Gateway</span>
            </div>
            @forelse ($payments as $payment)
                <div class="grid grid-cols-[1fr_1fr_1.3fr_0.9fr_1.1fr_auto] gap-3.5 px-5 py-3.5 text-[13px] items-center border-b border-[#F4F5F7] last:border-0">
                    <span class="tnum text-[#787E88]">{{ ($payment->paid_at ?? $payment->created_at)->format('M j, H:i') }}</span>
                    <span class="tnum text-brand font-semibold">{{ $payment->device->account_number ?? '—' }}</span>
                    <span class="font-medium truncate">{{ $payment->client->name ?? '—' }}</span>
                    <span class="tnum text-right font-semibold">${{ number_format((float) $payment->amount, 2) }}</span>
                    <span class="text-[#565b64] truncate">{{ $payment->method_label ?? 'PesaPal' }}</span>
                    <span class="text-right"><x-status-badge :status="$payment->status" /></span>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-[13px] text-[#9AA0AA]">No payments found.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="mt-4">{{ $payments->links() }}</div>
@endsection
