<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $appName . ' Device Lock')</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { brand: { DEFAULT: '{{ $brandTints['DEFAULT'] }}', 50: '{{ $brandTints['50'] }}', 100: '{{ $brandTints['100'] }}' } },
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
            } },
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style> body { -webkit-font-smoothing: antialiased; } .tnum { font-variant-numeric: tabular-nums; } [x-cloak]{display:none!important} </style>
</head>
<body class="bg-[#F6F7F9] text-[#1A1D23] font-sans min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="flex items-center justify-center gap-3 mb-6">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $appName }}" class="h-10 w-auto max-w-[200px] object-contain">
            @else
                @if (! empty($iconUrl))
                    <img src="{{ $iconUrl }}" alt="{{ $appName }}" class="w-9 h-9 rounded-lg object-contain">
                @else
                    <div class="w-9 h-9 rounded-lg bg-brand flex items-center justify-center shadow-md shadow-brand/30">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2.2"></rect><path d="M8 11V8a4 4 0 0 1 8 0v3"></path></svg>
                    </div>
                @endif
                <div class="font-bold text-lg tracking-tight">{{ $appName }} Device Lock</div>
            @endif
        </div>
        @yield('content')
    </div>
</body>
</html>
