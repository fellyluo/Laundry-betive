@php
    $brand = $appSettings['branding'];
    $logoUrl = $brand['logo_url'] ?? null;
    $logoEmoji = $brand['logo_emoji'] ?? '🧺';
    $namaLaundry = $brand['nama_laundry'] ?? 'LaundryPro';
    $mode = $appTheme['mode'] ?? 'dark';

    $isSuper = auth()->check() && auth()->user()->isSuperAdmin();

    if ($isSuper) {
        // Super admin: hanya Dashboard (monitoring) + Member
        $nav = [
            ['name' => 'Dashboard',  'href' => route('dashboard'),       'icon' => 'layout-dashboard', 'active' => request()->is('dashboard')],
            ['name' => 'Member',     'href' => route('members.index'),   'icon' => 'shield-check',     'active' => request()->is('members*')],
            ['name' => 'Pengaturan', 'href' => route('platform.settings'),'icon' => 'settings',       'active' => request()->is('pengaturan')],
        ];
    } else {
        // Member: operasional laundry
        $nav = [
            ['name' => 'Dashboard',  'href' => route('dashboard'),    'icon' => 'home',            'active' => request()->is('dashboard')],
            ['name' => 'Order',      'href' => route('orders.index'), 'icon' => 'clipboard-list',  'active' => request()->is('orders') || request()->is('orders/*')],
            ['name' => 'Order Baru', 'href' => route('orders.create'),'icon' => 'plus-circle',     'active' => request()->is('orders/baru'), 'highlight' => true],
            ['name' => 'Pelanggan',  'href' => route('customers.index'),'icon' => 'users',         'active' => request()->is('customers*')],
            ['name' => 'Layanan',    'href' => route('services.index'),'icon' => 'washing-machine','active' => request()->is('services*')],
            ['name' => 'Pengeluaran','href' => route('expenses.index'),'icon' => 'receipt',        'active' => request()->is('expenses*')],
            ['name' => 'Pengaturan', 'href' => route('settings.index'),'icon' => 'settings',       'active' => request()->is('settings*')],
        ];
    }

    $brandSub = $isSuper ? 'Super Admin' : 'Premium Laundry';
@endphp

<!-- Mobile Top Header -->
<header class="md:hidden fixed top-0 left-0 right-0 h-14 bg-slate-900 border-b border-slate-800 text-slate-100 flex items-center justify-between px-4 z-50 shadow-sm no-print">
    <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-lg shadow-inner overflow-hidden {{ $isSuper ? 'bg-indigo-500/15 border border-indigo-400/30 text-indigo-300' : 'bg-accent/10 border border-accent/20' }}">
            @if($isSuper)<i data-lucide="shield-check" class="h-4.5 w-4.5"></i>@elseif($logoUrl)<img src="{{ $logoUrl }}" alt="Logo" class="w-full h-full object-cover">@else{{ $logoEmoji }}@endif
        </div>
        <div class="flex flex-col justify-center">
            <h1 class="font-extrabold text-sm leading-none truncate max-w-[180px] {{ $isSuper ? 'text-indigo-300' : 'text-accent' }}">{{ $namaLaundry }}</h1>
            <span class="text-[8px] uppercase tracking-widest font-bold mt-0.5 block leading-none {{ $isSuper ? 'text-indigo-300/70' : 'text-slate-550' }}">{{ $brandSub }}</span>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="toggleThemeMode()" class="flex items-center p-2 bg-slate-950/60 border border-slate-850 rounded-xl hover:border-slate-800 transition-all text-slate-400 cursor-pointer" title="Ubah Tema">
            @if($mode === 'light')<i data-lucide="sun" class="h-4.5 w-4.5 text-amber-500"></i>@else<i data-lucide="moon" class="h-4.5 w-4.5 text-accent"></i>@endif
        </button>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" class="flex items-center p-2 bg-slate-950/60 border border-slate-850 rounded-xl hover:border-rose-500/40 hover:text-rose-400 transition-all text-slate-400 cursor-pointer" title="Keluar"><i data-lucide="log-out" class="h-4.5 w-4.5"></i></button>
        </form>
    </div>
</header>

<!-- Desktop Sidebar -->
<aside class="hidden md:flex flex-col w-64 bg-slate-900 text-slate-100 min-h-screen border-r border-slate-800 shrink-0 no-print">
    <div class="p-6 border-b border-slate-800 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-2xl shadow-inner overflow-hidden {{ $isSuper ? 'bg-indigo-500/15 border border-indigo-400/30 text-indigo-300' : 'bg-accent/10 border border-accent/20' }}">
            @if($isSuper)<i data-lucide="shield-check" class="h-6 w-6"></i>@elseif($logoUrl)<img src="{{ $logoUrl }}" alt="Logo" class="w-full h-full object-cover">@else{{ $logoEmoji }}@endif
        </div>
        <div class="overflow-hidden flex flex-col justify-center">
            <h1 class="font-extrabold text-base leading-tight break-words {{ $isSuper ? 'text-indigo-300' : 'text-accent' }}">{{ $namaLaundry }}</h1>
            <span class="text-[9px] uppercase tracking-widest font-bold mt-1 block leading-none {{ $isSuper ? 'text-indigo-300/70' : 'text-slate-550' }}">{{ $brandSub }}</span>
        </div>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-1">
        @foreach($nav as $item)
            <a href="{{ $item['href'] }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ $item['active'] ? 'bg-accent/10 text-accent-soft font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200' }}">
                <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5 transition-transform duration-200 group-hover:scale-110 {{ $item['active'] ? 'text-accent-soft' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                <span class="font-semibold">{{ $item['name'] }}</span>
                @if(!empty($item['highlight']))<span class="ml-auto w-2 h-2 rounded-full bg-accent animate-pulse"></span>@endif
            </a>
        @endforeach
    </nav>

    <div class="px-6 py-4 border-t border-slate-800/80 flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-400">Mode Tema</span>
        <button onclick="toggleThemeMode()" class="flex items-center gap-1 p-1 bg-slate-950/60 border border-slate-850 rounded-xl hover:border-slate-800 transition-all text-slate-400 group cursor-pointer" title="Ubah Tema">
            <div class="p-1.5 rounded-lg transition-all duration-200 {{ $mode === 'light' ? 'bg-accent text-white shadow' : 'text-slate-500 hover:text-slate-400' }}">
                <i data-lucide="sun" class="h-4 w-4"></i>
            </div>
            <div class="p-1.5 rounded-lg transition-all duration-200 {{ $mode === 'dark' ? 'bg-accent text-white shadow' : 'text-slate-500 hover:text-slate-400' }}">
                <i data-lucide="moon" class="h-4 w-4"></i>
            </div>
        </button>
    </div>

    <div class="px-4 py-3 border-t border-slate-800/80">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $isSuper ? 'bg-indigo-500/15 text-indigo-300' : 'bg-slate-800 text-accent' }}"><i data-lucide="{{ $isSuper ? 'shield-check' : 'store' }}" class="h-4 w-4"></i></div>
                <div class="min-w-0 leading-tight">
                    <div class="text-xs font-semibold text-slate-200 truncate max-w-[120px]">{{ optional(auth()->user())->username }}</div>
                    <div class="text-[9px] uppercase tracking-wider font-bold {{ $isSuper ? 'text-indigo-300/70' : 'text-slate-550' }}">{{ $isSuper ? 'Super Admin' : 'Member' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button type="submit" class="p-2 bg-slate-950/60 border border-slate-850 rounded-xl hover:border-rose-500/40 hover:text-rose-400 text-slate-400 transition-all cursor-pointer" title="Keluar"><i data-lucide="log-out" class="h-4 w-4"></i></button>
            </form>
        </div>
    </div>

    <div class="p-4 border-t border-slate-800 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} {{ $namaLaundry }}
    </div>
</aside>

<!-- Mobile Bottom Nav -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-slate-900 border-t border-slate-800 text-slate-400 flex justify-around items-center py-2 px-1 z-50 shadow-2xl safe-bottom no-print">
    @foreach($nav as $item)
        @continue(!empty($item['super']))
        @if(!empty($item['highlight']))
            <a href="{{ $item['href'] }}" class="flex flex-col items-center justify-center -translate-y-4">
                <div class="p-3 bg-accent text-white rounded-full shadow-lg border-4 border-slate-900 hover:bg-accent/90 transition-colors group">
                    <i data-lucide="{{ $item['icon'] }}" class="h-6 w-6 transition-transform group-hover:scale-110"></i>
                </div>
                <span class="text-[10px] mt-1 text-accent font-medium">{{ $item['name'] }}</span>
            </a>
        @else
            <a href="{{ $item['href'] }}" class="flex flex-col items-center justify-center flex-1 min-w-0 py-1 rounded-xl transition-all duration-200 {{ $item['active'] ? 'text-accent-soft font-semibold' : 'text-slate-400' }}">
                <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5 shrink-0"></i>
                <span class="text-[9px] mt-1 truncate max-w-full px-0.5">{{ $item['name'] }}</span>
            </a>
        @endif
    @endforeach
</nav>
