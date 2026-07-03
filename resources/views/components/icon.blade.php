@props(['name' => 'dot', 'sw' => '1.9'])

@php
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'devices' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/>',
        'plans' => '<path d="M4 6h16M4 12h16M4 18h10"/>',
        'clients' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 6.2a3 3 0 0 1 0 5.6M17.5 20a5 5 0 0 0-3-4.6"/>',
        'payments' => '<rect x="3" y="5" width="18" height="14" rx="2.2"/><path d="M3 10h18"/>',
        'audit' => '<path d="M12 8v4l2.5 2"/><circle cx="12" cy="12" r="8.5"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-2.7 1.1V21a2 2 0 1 1-4 0v-.1A1.6 1.6 0 0 0 6.6 19l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.6 1.6 0 0 0 3 12a2 2 0 1 1 0-4 1.6 1.6 0 0 0 1.5-2.5l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.6 1.6 0 0 0 12 3a2 2 0 1 1 4 0 1.6 1.6 0 0 0 2.7 1.1l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1A1.6 1.6 0 0 0 21 9a2 2 0 1 1 0 4 1.6 1.6 0 0 0-1.6 2z"/>',
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="2.2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'unlock' => '<rect x="4" y="11" width="16" height="10" rx="2.2"/><path d="M8 11V8a4 4 0 0 1 8 0"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/>',
        'dollar' => '<path d="M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L8 9.6a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2Z"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'chevron-left' => '<path d="m15 6-6 6 6 6"/>',
        'chevrons-left' => '<path d="m11 17-5-5 5-5M18 17l-5-5 5-5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'download' => '<path d="M12 15V3M7 10l5 5 5-5M4 21h16"/>',
        'external' => '<path d="M14 3h7v7M21 3l-9 9M10 5H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 8 10 8a13.2 13.2 0 0 1-1.67 2.68"/><path d="M6.6 6.6A13.5 13.5 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M2 2l20 20"/>',
        'camera' => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'pencil' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'copy' => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'shield' => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"/>',
        'trash' => '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
        'dots' => '<circle cx="5" cy="12" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="19" cy="12" r="1.4"/>',
        'user-plus' => '<circle cx="9" cy="8" r="3.5"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0M18 8v6M21 11h-6"/>',
        'dot' => '<circle cx="12" cy="12" r="4"/>',
    ];
    $body = $paths[$name] ?? $paths['dot'];
@endphp

<svg {{ $attributes->merge(['class' => 'w-4 h-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $sw }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $body !!}</svg>
