@php
    $sidebarCollapsed = request()->cookie('sidebar_collapsed') === '1';
    $groups = [
        'Overview' => [
            ['admin.dashboard', 'Dashboard', 'dashboard', 'admin.dashboard', null],
        ],
        'Operations' => [
            ['admin.devices.index', 'Devices', 'devices', 'admin.devices.*', null],
            ['admin.plans.index', 'Plans', 'plans', 'admin.plans.*', null],
            ['admin.clients.index', 'Clients', 'clients', 'admin.clients.*', null],
            ['admin.payments.index', 'Payments', 'payments', 'admin.payments.*', null],
            ['admin.arrears', 'Arrears', 'phone', 'admin.arrears', null],
        ],
        'System' => [
            ['admin.notifications.index', 'Notifications', 'bell', 'admin.notifications.*', null],
            ['admin.audit.index', 'Audit log', 'audit', 'admin.audit.*', null],
            ['admin.settings', 'Settings', 'settings', 'admin.settings', 'manage-settings'],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ $appName }}</title>
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
    <script>
        window.zagaCurrencyPrefix = @json($currencyPrefix ?? 'KSh ');
        window.zagaMoney = function (n, d) {
            n = Number(n || 0);
            if (d === undefined) d = (n % 1 === 0) ? 0 : 2;
            return window.zagaCurrencyPrefix + n.toLocaleString(undefined, { minimumFractionDigits: d, maximumFractionDigits: d });
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { -webkit-font-smoothing: antialiased; }
        .tnum { font-variant-numeric: tabular-nums; font-feature-settings: "tnum"; }
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-thumb { background: #D8DBE0; border-radius: 8px; border: 2px solid #F6F7F9; }
    </style>
</head>
<body class="h-full bg-[#F6F7F9] text-[#1A1D23] font-sans"
      x-data="{
        collapsed: {{ $sidebarCollapsed ? 'true' : 'false' }},
        mobileOpen: false,
        notifOpen: false,
        userOpen: false,
        toggle() {
            this.collapsed = !this.collapsed;
            document.cookie = 'sidebar_collapsed=' + (this.collapsed ? '1' : '0') + ';path=/;max-age=31536000';
            try { localStorage.setItem('sidebar_collapsed', this.collapsed ? '1' : '0'); } catch (e) {}
        }
      }">
<div class="flex h-full">
    <div x-show="mobileOpen" x-transition.opacity @click="mobileOpen=false" x-cloak
         class="fixed inset-0 bg-[rgba(20,22,28,.28)] z-40 lg:hidden"></div>

    <aside class="fixed lg:static inset-y-0 left-0 z-50 w-[260px] bg-white border-r border-[#E9EBEF] flex flex-col transition-all duration-200 -translate-x-full lg:translate-x-0"
           :class="{ 'translate-x-0': mobileOpen, 'lg:w-[72px]': collapsed, 'lg:w-[236px]': !collapsed }">
        <div class="h-16 flex items-center gap-3 px-4 flex-none">
            @if ($logoUrl)
                <div class="hidden flex-none" :class="collapsed ? 'lg:flex' : 'lg:hidden'">@include('partials.brand-icon')</div>
                <img src="{{ $logoUrl }}" alt="{{ $appName }}" class="h-9 w-auto max-w-[150px] object-contain" :class="collapsed ? 'lg:hidden' : ''">
            @else
                <span class="flex-none">@include('partials.brand-icon')</span>
                <div class="leading-none" :class="collapsed ? 'lg:hidden' : ''">
                    <div class="font-bold text-[16px] tracking-[-0.02em]">{{ $appName }}</div>
                    <div class="text-[10.5px] text-[#9AA0AA] uppercase tracking-[0.06em] mt-1">Device Lock</div>
                </div>
            @endif
            <button @click="toggle()" class="ml-auto hidden lg:flex w-7 h-7 rounded-md border border-[#E4E6EB] items-center justify-center text-[#9AA0AA] hover:text-brand hover:border-brand transition">
                <x-icon name="chevrons-left" class="w-4 h-4" x-show="!collapsed" />
                <x-icon name="chevron-right" class="w-4 h-4" x-show="collapsed" x-cloak />
            </button>
            <button @click="mobileOpen=false" class="ml-auto lg:hidden w-7 h-7 rounded-md border border-[#E4E6EB] flex items-center justify-center text-[#9AA0AA]">
                <x-icon name="x" class="w-4 h-4" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-3">
            @foreach ($groups as $group => $items)
                <div class="text-[10.5px] font-semibold tracking-[0.07em] uppercase text-[#A6ABB4] px-3 pt-4 pb-2" :class="collapsed ? 'lg:hidden' : ''">{{ $group }}</div>
                @foreach ($items as [$route, $label, $icon, $match, $ability])
                    @continue($ability && auth()->user()->cannot($ability))
                    @php $active = request()->routeIs($match); @endphp
                    <a href="{{ route($route) }}" title="{{ $label }}"
                       @click="mobileOpen=false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-[9px] text-[13.5px] font-medium transition mb-0.5 {{ $active ? 'bg-brand text-white shadow-[0_1px_3px_rgba(75,69,199,.35)]' : 'text-[#565b64] hover:bg-[#F7F8FA]' }}"
                       :class="collapsed ? 'lg:justify-center lg:px-0' : ''">
                        <x-icon :name="$icon" class="w-[17px] h-[17px] flex-none" />
                        <span :class="collapsed ? 'lg:hidden' : ''">{{ $label }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="p-3 border-t border-[#EEF0F3] flex-none">
            <a href="{{ route('portal.lookup') }}" target="_blank" title="View client portal"
               class="flex items-center justify-center gap-2 py-2.5 rounded-[9px] border border-[#E4E6EB] bg-[#FBFBFC] text-[#4A4F58] text-[12.5px] font-medium hover:bg-white transition">
                <x-icon name="external" class="w-3.5 h-3.5 flex-none" sw="2" />
                <span :class="collapsed ? 'lg:hidden' : ''">View client portal</span>
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 h-full">
        <header class="h-16 flex-none bg-white border-b border-[#E9EBEF] flex items-center gap-3 sm:gap-4 px-4 sm:px-6 lg:px-7">
            <button @click="mobileOpen=true" class="lg:hidden w-9 h-9 rounded-lg border border-[#E4E6EB] flex items-center justify-center text-[#565b64] flex-none">
                <x-icon name="menu" class="w-[18px] h-[18px]" />
            </button>
            <div class="relative w-full max-w-md hidden sm:block">
                <x-icon name="search" class="w-4 h-4 text-[#A6ABB4] absolute left-3.5 top-1/2 -translate-y-1/2" sw="2" />
                <input placeholder="Search devices, clients, account numbers…"
                       class="w-full h-9 border border-[#E4E6EB] bg-[#F7F8FA] rounded-[9px] pl-10 pr-3 text-[13px] outline-none focus:border-brand focus:bg-white transition-colors">
            </div>
            <div class="flex-1"></div>
            @php $isLive = ($gatewayEnv ?? 'sandbox') === 'live'; @endphp
            <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg flex-none {{ $isLive ? 'bg-[#E6F4EE] border border-[#BFE3D2]' : 'bg-[#FBF1DD] border border-[#F1E2BE]' }}">
                <span class="w-[7px] h-[7px] rounded-full {{ $isLive ? 'bg-[#2FA372]' : 'bg-[#C69214]' }}"></span>
                <span class="text-[11.5px] font-semibold {{ $isLive ? 'text-[#0F7B54]' : 'text-[#8A6410]' }}">{{ $isLive ? 'LIVE' : 'SANDBOX' }}</span>
            </div>

            <div class="relative flex-none" @click.outside="notifOpen=false">
                <button @click="notifOpen=!notifOpen" class="relative w-9 h-9 rounded-[9px] border border-[#E4E6EB] bg-white flex items-center justify-center text-[#565b64] hover:border-brand transition">
                    <x-icon name="bell" class="w-[17px] h-[17px]" />
                    @if (($notificationCount ?? 0) > 0)
                        <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-[#C2453D] text-white text-[10px] font-semibold flex items-center justify-center border-2 border-white">{{ $notificationCount }}</span>
                    @endif
                </button>
                <div x-show="notifOpen" x-cloak x-transition
                     class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white border border-[#E9EBEF] rounded-xl shadow-[0_4px_20px_rgba(16,20,28,.08)] z-50 overflow-hidden">
                    <div class="px-4 py-3 border-b border-[#EEF0F3] flex items-center justify-between">
                        <span class="text-[13px] font-semibold">Notifications</span>
                        @if (($notificationCount ?? 0) > 0)
                            <span class="text-[11px] text-[#9AA0AA]">{{ $notificationCount }} unread</span>
                        @endif
                    </div>
                    <div class="max-h-80 overflow-auto">
                        @forelse (($notifications ?? []) as $n)
                            <a href="{{ $n->link ?? route('admin.notifications.index') }}"
                               class="flex gap-3 px-4 py-3 border-b border-[#F4F5F7] last:border-0 hover:bg-[#FBFAFF] {{ $n->read_at ? '' : 'bg-[#FBFAFF]' }}">
                                @include('partials.notification-icon', ['type' => $n->type, 'size' => 32])
                                <div class="min-w-0 flex-1">
                                    <div class="text-[13px] {{ $n->read_at ? 'font-medium' : 'font-semibold' }} truncate">{{ $n->title }}</div>
                                    <div class="text-[11.5px] text-[#9AA0AA] truncate">{{ $n->body }}</div>
                                    <div class="text-[11px] text-[#B9BEC6] mt-0.5">{{ $n->created_at->diffForHumans() }}</div>
                                </div>
                                @unless ($n->read_at)<span class="w-2 h-2 rounded-full bg-brand mt-1 flex-none"></span>@endunless
                            </a>
                        @empty
                            <div class="px-4 py-8 text-center text-[13px] text-[#9AA0AA]">You're all caught up.</div>
                        @endforelse
                    </div>
                    <a href="{{ route('admin.notifications.index') }}" class="block text-center px-4 py-3 border-t border-[#EEF0F3] text-[12.5px] text-brand font-medium hover:bg-[#FBFBFC]">View all notifications</a>
                </div>
            </div>

            <div class="w-px h-6 bg-[#E9EBEF] hidden sm:block flex-none"></div>

            <div class="relative flex-none" @click.outside="userOpen=false">
                <button @click="userOpen=!userOpen" class="flex items-center gap-2.5 pl-1 pr-2 py-1 rounded-lg hover:bg-[#F7F8FA] transition-colors">
                    <x-avatar :name="auth()->user()->name" :image="auth()->user()->avatarUrl()" :size="34" />
                    <div class="leading-tight text-left hidden md:block">
                        <div class="text-[13px] font-semibold">{{ auth()->user()->name }}</div>
                        <div class="text-[11px] text-[#9AA0AA]">{{ auth()->user()->roleLabel() }}</div>
                    </div>
                    <x-icon name="chevron-down" class="w-3.5 h-3.5 text-[#9AA0AA] hidden md:block" sw="2.2" />
                </button>
                <div x-show="userOpen" x-cloak x-transition
                     class="absolute right-0 mt-2 w-44 bg-white border border-[#E9EBEF] rounded-xl shadow-[0_4px_20px_rgba(16,20,28,.08)] z-50 overflow-hidden">
                    <a href="{{ route('admin.account') }}" class="block px-4 py-2.5 text-[13px] hover:bg-[#F7F8FA]">My account</a>
                    @can('manage-settings')
                        <a href="{{ route('admin.settings') }}" class="block px-4 py-2.5 text-[13px] hover:bg-[#F7F8FA]">Settings</a>
                    @endcan
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full text-left px-4 py-2.5 text-[13px] hover:bg-[#F7F8FA] text-[#B23A30]">Sign out</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-auto">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-7">
                @if (session('status'))
                    <div class="mb-5 px-4 py-3 rounded-xl bg-[#E6F4EE] border border-[#BFE3D2] text-[13px] text-[#0F7B54]">{{ session('status') }}</div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
</div>
@include('partials.confirm-modal')
</body>
</html>
