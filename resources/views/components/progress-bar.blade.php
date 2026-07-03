@props(['paid' => 0, 'total' => 1, 'current' => true])

@php
    $paid = max(0, (int) $paid);
    $total = max(1, (int) $total);
    $showCurrent = $current && $paid < $total;
    $remaining = max($total - $paid - ($showCurrent ? 1 : 0), 0);
@endphp

<div class="flex gap-1 items-center">
    @if ($paid > 0)
        <div class="h-2 rounded-[5px] bg-brand" style="flex: {{ $paid }};"></div>
    @endif
    @if ($showCurrent)
        <div class="h-2 rounded-[5px] bg-[#C69214]" style="flex: 1;"></div>
    @endif
    @if ($remaining > 0)
        <div class="h-2 rounded-[5px] bg-[#ECEDF1]" style="flex: {{ $remaining }};"></div>
    @endif
</div>
