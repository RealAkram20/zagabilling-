@props(['status'])

@php
    $map = [
        'unassigned' => ['Unassigned', '#4B45C7', '#F1F0FC'],
        'active' => ['Active', '#0F7B54', '#E6F4EE'],
        'paid' => ['Paid', '#0F7B54', '#E6F4EE'],
        'grace' => ['Grace', '#8A6410', '#FBF1DD'],
        'pending' => ['Pending', '#8A6410', '#FBF1DD'],
        'overdue' => ['Overdue', '#B23A30', '#FBEAE8'],
        'locked' => ['Locked', '#B23A30', '#FBEAE8'],
        'failed' => ['Failed', '#B23A30', '#FBEAE8'],
        'closed' => ['Closed', '#6B7280', '#EFF1F4'],
    ];
    [$label, $fg, $bg] = $map[$status] ?? [ucfirst($status), '#6B7280', '#EFF1F4'];
@endphp

<span class="inline-flex items-center text-[11px] font-semibold px-2.5 py-1 rounded-md whitespace-nowrap" style="color: {{ $fg }}; background: {{ $bg }};">{{ $label }}</span>
