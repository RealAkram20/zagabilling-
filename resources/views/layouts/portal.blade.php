<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Unlock your device') · {{ $appName }}</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            colors: { brand: { DEFAULT: '{{ $brandTints['DEFAULT'] }}', 50: '{{ $brandTints['50'] }}', 100: '{{ $brandTints['100'] }}' } },
            fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
        } } }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style> body { -webkit-font-smoothing: antialiased; } .tnum { font-variant-numeric: tabular-nums; } [x-cloak]{display:none!important} </style>
</head>
<body class="min-h-screen bg-[#F4F5F8] text-[#1A1D23] font-sans flex flex-col">
    <header class="h-[62px] flex-none bg-white border-b border-[#E9EBEF] flex items-center justify-between px-5 sm:px-7">
        <div class="flex items-center gap-2.5">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $appName }}" class="w-8 h-8 rounded-lg object-contain">
            @else
                <div class="w-8 h-8 rounded-lg bg-brand flex items-center justify-center shadow-[0_2px_6px_rgba(75,69,199,.35)]">
                    <x-icon name="lock" class="w-4 h-4 text-white" sw="2.2" />
                </div>
            @endif
            <span class="font-bold text-[15px] tracking-[-0.02em]">{{ $appName }}</span>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-[12.5px] text-[#787E88] hover:text-brand font-medium">← Back to admin</a>
    </header>
    <main class="flex-1 flex items-center justify-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-[440px]">
            @yield('content')
        </div>
    </main>
</body>
</html>
