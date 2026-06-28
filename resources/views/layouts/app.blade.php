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
<body class="min-h-full text-slate-100 flex flex-col md:flex-row font-sans" style="background-color: var(--background);">
    @include('partials.navbar')

    <main class="flex-1 flex flex-col min-h-screen overflow-y-auto pt-14 md:pt-0 pb-20 md:pb-0">
        <div class="flex-1 p-4 md:p-6 max-w-7xl w-full mx-auto">
            @yield('content')
        </div>
    </main>

    <!-- Global Custom Confirm Modal -->
    <div id="globalConfirmModal" class="fixed inset-0 bg-black/65 backdrop-blur-sm z-[9999] hidden items-center justify-center p-4">
        <div id="globalConfirmContent" class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-sm overflow-hidden shadow-2xl p-6 text-center space-y-6 transition-all duration-200 transform scale-95 opacity-0">
            <!-- Icon -->
            <div id="globalConfirmIconContainer" class="mx-auto w-12 h-12 rounded-full flex items-center justify-center">
                <i id="globalConfirmIcon" data-lucide="trash-2" class="h-6 w-6"></i>
            </div>
            <!-- Text -->
            <div class="space-y-2">
                <h3 id="globalConfirmTitle" class="text-[10px] font-extrabold tracking-wider text-slate-400 uppercase">Konfirmasi Tindakan</h3>
                <p id="globalConfirmMessage" class="text-white text-sm font-semibold leading-relaxed px-2"></p>
            </div>
            <!-- Buttons -->
            <div class="flex gap-3">
                <button type="button" id="globalConfirmCancelBtn" class="flex-1 py-2.5 rounded-xl border border-slate-800 hover:border-slate-700 text-slate-300 font-semibold transition-colors text-xs cursor-pointer">Batal</button>
                <button type="button" id="globalConfirmOkBtn" class="flex-1 py-2.5 rounded-xl text-white font-semibold shadow-lg transition-colors text-xs cursor-pointer">Ya, Hapus</button>
            </div>
        </div>
    </div>

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

        // Global confirmation handler to replace native confirm dialogs
        let activeConfirmCallback = null;

        function showCustomConfirm(message, callback) {
            activeConfirmCallback = callback;
            
            const modal = document.getElementById('globalConfirmModal');
            const content = document.getElementById('globalConfirmContent');
            const msgEl = document.getElementById('globalConfirmMessage');
            const titleEl = document.getElementById('globalConfirmTitle');
            const iconContainer = document.getElementById('globalConfirmIconContainer');
            const iconEl = document.getElementById('globalConfirmIcon');
            const okBtn = document.getElementById('globalConfirmOkBtn');

            msgEl.textContent = message;

            const msgLower = message.toLowerCase();
            const isDestructive = msgLower.includes('hapus') || msgLower.includes('batal') || msgLower.includes('delete') || msgLower.includes('cancel');

            if (isDestructive) {
                titleEl.textContent = 'Hapus / Batalkan?';
                iconContainer.className = 'mx-auto w-12 h-12 rounded-full flex items-center justify-center bg-rose-500/10 text-rose-500 border border-rose-500/20';
                iconEl.setAttribute('data-lucide', 'trash-2');
                okBtn.className = 'flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold shadow-lg transition-colors text-xs cursor-pointer';
                okBtn.textContent = msgLower.includes('batal') ? 'Ya, Batalkan' : 'Ya, Hapus';
            } else {
                titleEl.textContent = 'Konfirmasi Tindakan';
                iconContainer.className = 'mx-auto w-12 h-12 rounded-full flex items-center justify-center bg-accent/10 text-accent border border-accent/20';
                iconEl.setAttribute('data-lucide', 'help-circle');
                okBtn.className = 'flex-1 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-white font-semibold shadow-lg transition-colors text-xs cursor-pointer';
                okBtn.textContent = 'Ya, Lanjutkan';
            }

            if (window.lucide) {
                lucide.createIcons();
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeCustomConfirm(confirmed) {
            const modal = document.getElementById('globalConfirmModal');
            const content = document.getElementById('globalConfirmContent');

            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (confirmed && activeConfirmCallback) {
                    activeConfirmCallback();
                }
                activeConfirmCallback = null;
            }, 150);
        }

        document.getElementById('globalConfirmCancelBtn').addEventListener('click', () => closeCustomConfirm(false));
        document.getElementById('globalConfirmOkBtn').addEventListener('click', () => closeCustomConfirm(true));

        // Intercept native confirm calls inside onsubmit attributes
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const onsubmitAttr = form.getAttribute('onsubmit');
            if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
                e.preventDefault();
                e.stopPropagation();

                const match = onsubmitAttr.match(/confirm\((['"`])(.*?)\1\)/);
                const message = match ? match[2] : 'Apakah Anda yakin?';

                showCustomConfirm(message, function() {
                    const originalOnsubmit = form.onsubmit;
                    form.onsubmit = null;
                    form.submit();
                    form.onsubmit = originalOnsubmit;
                });
            }
        }, true);

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
