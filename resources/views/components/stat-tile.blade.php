@props(['label', 'value', 'sub' => null, 'subColor' => '#8A909A', 'icon' => null])

<div class="bg-white border border-[#E9EBEF] rounded-[13px] p-4 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
    <div class="flex items-center justify-between">
        <span class="text-[11.5px] text-[#8A909A] font-medium">{{ $label }}</span>
        @if ($icon)
            <span class="text-[#B9BEC6]"><x-icon :name="$icon" class="w-[15px] h-[15px]" sw="1.8" /></span>
        @endif
        {{ $slot }}
    </div>
    <div class="tnum text-[27px] font-bold tracking-[-0.02em] mt-2">{{ $value }}</div>
    @if ($sub)
        <div class="text-[11px] mt-1.5 font-medium" style="color: {{ $subColor }};">{!! $sub !!}</div>
    @endif
</div>
