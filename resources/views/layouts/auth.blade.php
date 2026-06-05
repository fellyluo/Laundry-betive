<!DOCTYPE html>
<html lang="id" class="h-full antialiased {{ ($appTheme['mode'] ?? 'dark') === 'dark' ? 'dark' : 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $appTheme['accent_bg'] ?? '#0d9488' }}">
    <title>@yield('title', 'Masuk') — {{ $appSettings['branding']['nama_laundry'] ?? 'LaundryPro' }}</title>
    <link rel="icon" href="/icon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: {
                colors: {
                    background: 'var(--background)',
                    slate: { 350:'#b0bccd',405:'#909cad',450:'#7c8a9e',550:'#556173',650:'#3e4a5e',750:'#293548',850:'#172033',855:'#161e30',955:'#070b14' },
                    amber:{450:'#f8af18',550:'#e88f0b'}, emerald:{450:'#22c389',550:'#0baf78'}, rose:{450:'#f75a72',550:'#e02749'}, teal:{450:'#20c6b3'},
                },
                spacing: { '4.5':'1.125rem','7.5':'1.875rem' },
            } },
        };
    </script>
    <link rel="stylesheet" href="/css/app.css?v=5">
    <style>
        :root {
            --accent-font: {{ $appTheme['accent_font'] ?? '#0d9488' }};
            --accent-bg: {{ $appTheme['accent_bg'] ?? '#0d9488' }};
            --accent-hover-bg: {{ $appTheme['accent_hover'] ?? '#0f766e' }};
            --background: {{ $appTheme['background'] ?? '#0b0f19' }};
        }
    </style>
</head>
<body class="min-h-full font-sans text-slate-100 flex items-center justify-center p-4" style="background-color: var(--background);">
    @php $brand = $appSettings['branding']; $logoUrl = $brand['logo_url'] ?? null; $logoEmoji = $brand['logo_emoji'] ?? '🧺'; @endphp
    <div class="w-full max-w-sm">
        <div class="flex flex-col items-center mb-6">
            <div class="w-16 h-16 bg-accent/10 border border-accent/20 rounded-2xl flex items-center justify-center text-3xl shadow-inner overflow-hidden mb-3">
                @if($logoUrl)<img src="{{ $logoUrl }}" alt="Logo" class="w-full h-full object-cover">@else{{ $logoEmoji }}@endif
            </div>
            <h1 class="text-xl font-extrabold text-accent">{{ $brand['nama_laundry'] ?? 'LaundryPro' }}</h1>
            <span class="text-[10px] text-slate-550 uppercase tracking-widest font-bold mt-0.5">Premium Laundry</span>
        </div>

        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 sm:p-7 shadow-2xl">
            @yield('content')
        </div>

        <p class="text-center text-[11px] text-slate-550 mt-6">&copy; {{ date('Y') }} {{ $brand['nama_laundry'] ?? 'LaundryPro' }}</p>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
