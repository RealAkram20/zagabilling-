@php
    $map = [
        'payment.received' => ['payments', '#E6F4EE', '#0F7B54'],
        'device.locked' => ['lock', '#FBEAE8', '#C2453D'],
        'device.unlocked' => ['unlock', '#EDECFB', '#4B45C7'],
        'unlock.issued' => ['unlock', '#EDECFB', '#4B45C7'],
        'device.enrolled' => ['clients', '#EDECFB', '#4B45C7'],
        'device.registered' => ['devices', '#EDECFB', '#4B45C7'],
        'device.uninstall' => ['trash', '#FBEAE8', '#C2453D'],
    ];
    [$icon, $bg, $fg] = $map[$type] ?? ['bell', '#EFF1F4', '#6B7280'];
    $box = $size ?? 34;
@endphp
<span class="rounded-lg flex items-center justify-center flex-none" style="width: {{ $box }}px; height: {{ $box }}px; background: {{ $bg }}; color: {{ $fg }};">
    <x-icon :name="$icon" class="w-4 h-4" sw="1.9" />
</span>
