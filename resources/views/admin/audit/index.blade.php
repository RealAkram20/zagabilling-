@extends('layouts.admin')

@section('title', 'Audit log')

@php
    $icons = [
        'unlock_code.issue' => ['unlock', '#EDECFB', '#4B45C7'],
        'payment.verified' => ['payments', '#E6F4EE', '#0F7B54'],
        'device.lock' => ['lock', '#FBEAE8', '#C2453D'],
        'device.unlock' => ['unlock', '#EDECFB', '#4B45C7'],
        'device.register' => ['devices', '#EDECFB', '#4B45C7'],
        'device.uninstall_auth' => ['trash', '#FBEAE8', '#C2453D'],
        'vault.reveal' => ['shield', '#FBF1DD', '#8A6410'],
    ];
@endphp

@section('content')
<x-page-header title="Audit log" subtitle="Immutable record of every lock, unlock, payment, and admin action.">
    <x-slot name="actions">
        <form method="GET">
            <select name="action" onchange="this.form.submit()" class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] text-[#4A4F58] outline-none">
                <option value="">All events</option>
                @foreach (array_keys($icons) as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </form>
    </x-slot>
</x-page-header>

<div class="bg-white border border-[#E9EBEF] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] px-4 sm:px-6 py-2">
    @forelse ($logs as $log)
        @php [$icon, $bg, $fg] = $icons[$log->action] ?? ['settings', '#EFF1F4', '#6B7280']; @endphp
        <div class="flex items-start gap-3.5 py-3.5 border-b border-[#F4F5F7] last:border-0">
            <span class="w-[34px] h-[34px] rounded-[9px] flex items-center justify-center flex-none" style="background: {{ $bg }}; color: {{ $fg }};">
                <x-icon :name="$icon" class="w-4 h-4" sw="1.9" />
            </span>
            <div class="flex-1 min-w-0">
                <div class="text-[13.5px] text-[#1A1D23]">{{ $log->description }}</div>
                <div class="text-[11.5px] text-[#9AA0AA] mt-0.5">{{ $log->user->name ?? 'System' }} · {{ $log->action }}</div>
            </div>
            <span class="tnum text-[11.5px] text-[#A6ABB4] flex-none whitespace-nowrap">{{ $log->created_at?->format('M j, H:i') }}</span>
        </div>
    @empty
        <div class="py-10 text-center text-[13px] text-[#9AA0AA]">No audit entries yet.</div>
    @endforelse
</div>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection
