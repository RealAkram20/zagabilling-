@props(['title', 'subtitle' => null])

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <h1 class="text-[22px] sm:text-[24px] font-bold tracking-[-0.025em]">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-[13.5px] text-[#787E88] mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2 flex-none">{{ $actions }}</div>
    @endisset
</div>
