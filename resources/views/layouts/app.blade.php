<!DOCTYPE html>
<html lang="id" class="h-full antialiased {{ ($appTheme['mode'] ?? 'dark') === 'dark' ? 'dark' : 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $appTheme['accent_bg'] ?? '#0d9488' }}">
    <title>{{ $appSettings['branding']['nama_laundry'] ?? 'LaundryPro' }} — Manajemen Laundry</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/icon.svg">
    <link rel="apple-touch-icon" href="/icon.svg">

    <!-- Tailwind (Play CDN, no build / no Vite) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', 'sans-serif'],
                        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Consolas', 'monospace'],
                    },
                    colors: {
                        background: 'var(--background)',
                        slate: {
                            350: '#b0bccd', 405: '#909cad', 450: '#7c8a9e',
                            550: '#556173', 650: '#3e4a5e', 750: '#293548',
                            850: '#172033', 855: '#161e30', 955: '#070b14',
                        },
                        amber: { 450: '#f8af18', 550: '#e88f0b' },
                        emerald: { 450: '#22c389', 550: '#0baf78' },
                        rose: { 450: '#f75a72', 550: '#e02749' },
                        teal: { 450: '#20c6b3' },
                    },
                    spacing: { '4.5': '1.125rem', '7.5': '1.875rem' },
                    scale: { '102': '1.02' },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="/css/app.css?v=4">
    <style>
        :root {
            --accent-font: {{ $appTheme['accent_font'] ?? '#0d9488' }};
            --accent-bg: {{ $appTheme['accent_bg'] ?? '#0d9488' }};
            --accent-hover-bg: {{ $appTheme['accent_hover'] ?? '#0f766e' }};
            --background: {{ $appTheme['background'] ?? '#0b0f19' }};
        }
    </style>
</head>
<body class="min-h-full text-slate-100 flex flex-col md:flex-row font-sans" style="background-color: var(--background);">
    @include('partials.navbar')

    <main class="flex-1 flex flex-col min-h-screen overflow-y-auto pt-14 md:pt-0 pb-20 md:pb-0">
        <div class="flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto">
            @yield('content')
        </div>
    </main>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        // CSRF for fetch
        window.CSRF_TOKEN = document.querySelector('meta[name=csrf-token]').getAttribute('content');

        function renderIcons() { if (window.lucide) lucide.createIcons(); }
        renderIcons();

        // Theme mode toggle (instant + persist via AJAX)
        function toggleThemeMode() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            const newMode = isDark ? 'light' : 'dark';
            if (newMode === 'dark') { html.classList.remove('light'); html.classList.add('dark'); }
            else { html.classList.remove('dark'); html.classList.add('light'); }
            // Update toggle visuals if present
            document.querySelectorAll('[data-theme-toggle]').forEach(el => el.dispatchEvent(new Event('theme:changed')));
            fetch('{{ route('settings.themeMode') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ mode: newMode })
            }).then(() => { window.location.reload(); }).catch(() => {});
        }

        // PWA service worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
    @stack('scripts')
    <script>renderIcons();</script>
</body>
</html>
