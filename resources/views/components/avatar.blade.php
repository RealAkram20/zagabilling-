@props(['name' => '', 'size' => 36, 'variant' => 'brand', 'image' => null])

@php
    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper($part[0]))
        ->implode('');

    $variants = [
        'brand' => ['#4B45C7', '#ffffff'],
        'purple' => ['#EDECFB', '#4B45C7'],
        'red' => ['#FBEAE8', '#B23A30'],
        'orange' => ['#FBF1DD', '#8A6410'],
    ];
    [$bg, $fg] = $variants[$variant] ?? $variants['brand'];
@endphp

@if ($image)
    <span {{ $attributes->merge(['class' => 'inline-flex rounded-full overflow-hidden flex-none bg-[#EFF1F4]']) }}
          style="width: {{ $size }}px; height: {{ $size }}px;">
        <img src="{{ $image }}" alt="{{ $name }}" class="w-full h-full object-cover">
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full font-semibold flex-none']) }}
          style="width: {{ $size }}px; height: {{ $size }}px; background: {{ $bg }}; color: {{ $fg }}; font-size: {{ round($size * 0.36) }}px;">
        {{ $initials ?: '—' }}
    </span>
@endif
