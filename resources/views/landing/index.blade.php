<!DOCTYPE html>
<html lang="id" class="h-full antialiased {{ ($appTheme['mode'] ?? 'dark') === 'dark' ? 'dark' : 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $appTheme['accent_bg'] ?? '#0d9488' }}">
    @php $brand = $appSettings['branding']; $nama = $brand['nama_laundry'] ?? 'LaundryPro'; $logoUrl = $brand['logo_url'] ?? null; $logoEmoji = $brand['logo_emoji'] ?? '🧺'; @endphp
    <title>{{ $nama }} — Aplikasi Manajemen Laundry All-in-One</title>
    <meta name="description" content="Kelola laundry lebih cepat & untung: order, struk thermal, WhatsApp, QR self-order, laporan laba bersih, multi pembayaran. Bisa diinstall di HP (PWA).">
    <link rel="icon" href="/icon.svg">
    <link rel="manifest" href="/manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: {
            colors: { background:'var(--background)', slate:{350:'#b0bccd',405:'#909cad',450:'#7c8a9e',550:'#556173',650:'#3e4a5e',750:'#293548',850:'#172033',855:'#161e30',955:'#070b14'}, amber:{450:'#f8af18',550:'#e88f0b'}, emerald:{450:'#22c389',550:'#0baf78'}, rose:{450:'#f75a72',550:'#e02749'}, teal:{450:'#20c6b3'} },
            spacing: { '4.5':'1.125rem','7.5':'1.875rem' },
        } } };
    </script>
    <link rel="stylesheet" href="/css/app.css?v=5">
    <style>
        :root { --accent-font: {{ $appTheme['accent_font'] ?? '#0d9488' }}; --accent-bg: {{ $appTheme['accent_bg'] ?? '#0d9488' }}; --accent-hover-bg: {{ $appTheme['accent_hover'] ?? '#0f766e' }}; --background: {{ $appTheme['background'] ?? '#0b0f19' }}; }
        .hero-glow { background: radial-gradient(60% 50% at 50% 0%, color-mix(in srgb, var(--accent-bg) 22%, transparent), transparent 70%); }
        .accent-grad { background: linear-gradient(135deg, var(--accent-bg), color-mix(in srgb, var(--accent-bg) 55%, #38bdf8)); }
        html { scroll-behavior: smooth; }
        .feature-card:hover { transform: translateY(-4px); }
        .feature-card { transition: transform .2s ease, border-color .2s ease; }
    </style>
</head>
<body class="min-h-full font-sans text-slate-100" style="background-color: var(--background);">

    <!-- Navbar -->
    <header class="sticky top-0 z-40 border-b border-slate-800/70 backdrop-blur-md bg-slate-900/70 no-print">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="#top" class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-accent/10 border border-accent/20 rounded-xl flex items-center justify-center text-lg overflow-hidden">
                    @if($logoUrl)<img src="{{ $logoUrl }}" alt="Logo" class="w-full h-full object-cover">@else{{ $logoEmoji }}@endif
                </div>
                <span class="font-extrabold text-accent text-lg">{{ $nama }}</span>
            </a>
            <nav class="hidden md:flex items-center gap-7 text-sm font-semibold text-slate-300">
                <a href="#fitur" class="hover:text-accent transition-colors">Fitur</a>
                <a href="#cara" class="hover:text-accent transition-colors">Cara Kerja</a>
                <a href="#mulai" class="hover:text-accent transition-colors">Mulai</a>
            </nav>
            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="bg-accent hover:bg-accent-hover text-white font-semibold px-4 py-2 rounded-xl text-sm transition-all shadow-lg flex items-center gap-1.5"><i data-lucide="layout-dashboard" class="h-4 w-4"></i>Buka Dashboard</a>
                @else
                    <a href="{{ route('member.signup') }}" class="hidden sm:inline-flex items-center border border-slate-700 hover:border-accent text-slate-200 hover:text-accent font-semibold px-4 py-2 rounded-xl text-sm transition-all">Daftar</a>
                    <a href="{{ route('login') }}" class="bg-accent hover:bg-accent-hover text-white font-semibold px-4 py-2 rounded-xl text-sm transition-all shadow-lg flex items-center gap-1.5"><i data-lucide="log-in" class="h-4 w-4"></i>Masuk</a>
                @endauth
            </div>
        </div>
    </header>

    <main id="top">
        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="hero-glow absolute inset-x-0 top-0 h-[420px] pointer-events-none"></div>
            <div class="relative max-w-6xl mx-auto px-4 sm:px-6 pt-16 pb-12 md:pt-24 md:pb-20 grid lg:grid-cols-2 gap-12 items-center">
                <div class="text-center lg:text-left">
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-accent/10 text-accent border border-accent/20 rounded-full text-xs font-bold mb-5"><i data-lucide="sparkles" class="h-3.5 w-3.5"></i>Aplikasi Manajemen Laundry All-in-One</span>
                    <h1 class="text-4xl sm:text-5xl md:text-6xl font-black tracking-tight text-white leading-[1.05]">
                        Kelola Laundry,<br><span class="text-accent">lebih cepat &amp; untung.</span>
                    </h1>
                    <p class="text-slate-400 text-base sm:text-lg mt-5 max-w-xl mx-auto lg:mx-0">
                        Dari terima order, proses, cetak struk thermal, sampai laporan laba bersih — semua dalam satu aplikasi. Plus pendaftaran pelanggan lewat <b class="text-slate-200">QR</b> &amp; pembayaran <b class="text-slate-200">QRIS/transfer</b>.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 mt-8 justify-center lg:justify-start">
                        @auth
                            <a href="{{ route('dashboard') }}" class="bg-accent hover:bg-accent-hover text-white font-bold px-6 py-3.5 rounded-xl transition-all shadow-lg hover:-translate-y-0.5 text-sm flex items-center justify-center gap-2"><i data-lucide="layout-dashboard" class="h-5 w-5"></i>Buka Dashboard</a>
                            <a href="#fitur" class="bg-slate-800 hover:bg-slate-700 text-slate-100 font-bold px-6 py-3.5 rounded-xl transition-all border border-slate-750/40 text-sm flex items-center justify-center gap-2">Lihat Fitur <i data-lucide="arrow-down" class="h-4 w-4"></i></a>
                        @else
                            <a href="{{ route('member.signup') }}" class="bg-accent hover:bg-accent-hover text-white font-bold px-6 py-3.5 rounded-xl transition-all shadow-lg hover:-translate-y-0.5 text-sm flex items-center justify-center gap-2"><i data-lucide="user-plus" class="h-5 w-5"></i>Daftar Jadi Member</a>
                            <a href="{{ route('login') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-100 font-bold px-6 py-3.5 rounded-xl transition-all border border-slate-750/40 text-sm flex items-center justify-center gap-2"><i data-lucide="log-in" class="h-4.5 w-4.5"></i>Masuk</a>
                        @endauth
                    </div>
                    @guest<p class="mt-4 text-xs text-slate-500 text-center lg:text-left">Gratis daftar · aktivasi langganan oleh admin · <a href="#fitur" class="text-accent font-semibold hover:underline">lihat fitur ↓</a></p>@endguest
                    <div class="flex flex-wrap gap-x-5 gap-y-2 mt-6 justify-center lg:justify-start text-xs text-slate-500 font-semibold">
                        <span class="flex items-center gap-1.5"><i data-lucide="check" class="h-3.5 w-3.5 text-accent"></i>Bisa diinstall di HP (PWA)</span>
                        <span class="flex items-center gap-1.5"><i data-lucide="check" class="h-3.5 w-3.5 text-accent"></i>Mode terang &amp; gelap</span>
                        <span class="flex items-center gap-1.5"><i data-lucide="check" class="h-3.5 w-3.5 text-accent"></i>Responsif di semua layar</span>
                    </div>
                </div>

                <!-- Mockup -->
                <div class="relative">
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
                        <div class="flex items-center gap-1.5 px-4 h-9 bg-slate-950/70 border-b border-slate-800">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500/70"></span><span class="w-2.5 h-2.5 rounded-full bg-amber-500/70"></span><span class="w-2.5 h-2.5 rounded-full bg-emerald-500/70"></span>
                            <span class="ml-3 text-[10px] text-slate-500 font-mono">{{ $nama }} · Dashboard</span>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="grid grid-cols-3 gap-2.5">
                                <div class="bg-slate-950/50 border border-slate-800 rounded-xl p-3"><p class="text-[8px] uppercase font-bold text-slate-500">Omzet</p><p class="text-sm font-black text-white mt-1">Rp 1,6jt</p></div>
                                <div class="bg-slate-950/50 border border-slate-800 rounded-xl p-3"><p class="text-[8px] uppercase font-bold text-slate-500">Order</p><p class="text-sm font-black text-white mt-1">24</p></div>
                                <div class="bg-slate-950/50 border border-slate-800 rounded-xl p-3"><p class="text-[8px] uppercase font-bold text-slate-500">Laba</p><p class="text-sm font-black text-accent mt-1">Rp 980rb</p></div>
                            </div>
                            <div class="bg-slate-950/50 border border-slate-800 rounded-xl p-3">
                                <p class="text-[8px] uppercase font-bold text-slate-500 mb-2">Tren Omzet 7 Hari</p>
                                <div class="flex items-end justify-between gap-1.5 h-20">
                                    @foreach([45,60,40,75,55,90,70] as $h)
                                        <div class="flex-1 rounded-t accent-grad" style="height: {{ $h }}%"></div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex items-center justify-between bg-slate-950/50 border border-slate-800 rounded-xl p-3">
                                <div class="flex items-center gap-2"><span class="w-7 h-7 rounded-lg bg-accent/10 text-accent flex items-center justify-center"><i data-lucide="clipboard-list" class="h-3.5 w-3.5"></i></span><div><p class="text-[10px] font-bold text-white leading-none">Order #20260605</p><p class="text-[8px] text-slate-500 mt-0.5">Cuci Setrika · 3kg</p></div></div>
                                <span class="text-[8px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-450 border border-emerald-500/20">selesai</span>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -bottom-4 -right-3 bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-xl hidden sm:flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center"><i data-lucide="qr-code" class="h-5 w-5 text-slate-900"></i></span>
                        <div><p class="text-[10px] font-bold text-white leading-none">Scan QR</p><p class="text-[8px] text-slate-500 mt-0.5">Pelanggan daftar sendiri</p></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats strip -->
        <section class="border-y border-slate-800/70 bg-slate-900/30">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                @foreach([['layout-dashboard','Dashboard & Laporan'],['printer','Struk 58/80mm'],['qr-code','QR Self-Order'],['credit-card','QRIS & Transfer']] as $s)
                    <div class="flex flex-col items-center gap-1.5">
                        <i data-lucide="{{ $s[0] }}" class="h-6 w-6 text-accent"></i>
                        <span class="text-xs font-bold text-slate-300">{{ $s[1] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Features -->
        <section id="fitur" class="max-w-6xl mx-auto px-4 sm:px-6 py-16 md:py-24">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs font-bold text-accent uppercase tracking-widest">Fitur Lengkap</span>
                <h2 class="text-3xl md:text-4xl font-black text-white mt-2">Semua yang dibutuhkan laundry modern</h2>
                <p class="text-slate-400 mt-3">Satu aplikasi untuk operasional, keuangan, pelanggan, dan pembayaran.</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @php
                    $features = [
                        ['bar-chart-3','Dashboard & Laporan','Omzet harian, grafik tren 7 hari, dan laba bersih (pendapatan − pengeluaran) terhitung otomatis.'],
                        ['clipboard-list','Manajemen Order','Alur status diterima → diproses → selesai → diambil. Edit & batalkan order kapan saja.'],
                        ['printer','Struk Thermal & WhatsApp','Cetak struk 58/80mm dan kirim notifikasi WhatsApp ke pelanggan hanya sekali klik.'],
                        ['users','Pelanggan & Layanan','Master pelanggan dengan poin member, serta layanan kiloan/satuan dengan tarif fleksibel.'],
                        ['qr-code','QR Self-Order','Pelanggan scan QR untuk daftar & memesan sendiri — pesanan langsung masuk ke daftar order.'],
                        ['receipt','Pengeluaran & Profit','Catat biaya operasional; laba bersih dihitung otomatis per hari dan per bulan.'],
                        ['credit-card','Multi Pembayaran','Tunai, QRIS, dan transfer bank — lengkap dengan nomor rekening & barcode QRIS.'],
                        ['palette','Tema Kustom','Ganti warna aksen, logo, dan mode terang/gelap sesuai brand laundry Anda.'],
                        ['shield-check','Multi-User & Langganan','Banyak akun staff, plus Super Admin yang mengatur akses & masa langganan tiap member.'],
                    ];
                @endphp
                @foreach($features as $f)
                    <div class="feature-card bg-slate-900/60 border border-slate-800 hover:border-accent/40 rounded-2xl p-6 shadow-lg">
                        <div class="w-11 h-11 rounded-xl bg-accent/10 text-accent border border-accent/20 flex items-center justify-center mb-4"><i data-lucide="{{ $f[0] }}" class="h-5.5 w-5.5"></i></div>
                        <h3 class="font-bold text-white text-base">{{ $f[1] }}</h3>
                        <p class="text-slate-400 text-sm mt-1.5 leading-relaxed">{{ $f[2] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- How it works -->
        <section id="cara" class="border-y border-slate-800/70 bg-slate-900/30">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 md:py-24">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="text-xs font-bold text-accent uppercase tracking-widest">Cara Kerja</span>
                    <h2 class="text-3xl md:text-4xl font-black text-white mt-2">Empat langkah sederhana</h2>
                </div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @php
                        $steps = [
                            ['1','user-plus','Terima Order','Buat order manual atau pelanggan scan QR untuk memesan sendiri.'],
                            ['2','refresh-cw','Proses & Update','Perbarui status pengerjaan; kabari pelanggan via WhatsApp.'],
                            ['3','printer','Bayar & Cetak','Pilih metode pembayaran lalu cetak struk thermal.'],
                            ['4','trending-up','Pantau Laporan','Lihat omzet, pengeluaran, dan laba bersih secara real-time.'],
                        ];
                    @endphp
                    @foreach($steps as $st)
                        <div class="relative bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                            <span class="absolute -top-3 -left-3 w-8 h-8 rounded-full accent-grad text-white font-black text-sm flex items-center justify-center shadow-lg">{{ $st[0] }}</span>
                            <i data-lucide="{{ $st[1] }}" class="h-6 w-6 text-accent mb-3"></i>
                            <h3 class="font-bold text-white">{{ $st[2] }}</h3>
                            <p class="text-slate-400 text-sm mt-1.5">{{ $st[3] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section id="mulai" class="max-w-6xl mx-auto px-4 sm:px-6 py-16 md:py-24">
            <div class="relative overflow-hidden rounded-3xl border border-accent/20 bg-slate-900/60 p-8 md:p-14 text-center">
                <div class="hero-glow absolute inset-0 opacity-60 pointer-events-none"></div>
                <div class="relative">
                    <h2 class="text-3xl md:text-4xl font-black text-white">Siap bikin laundry Anda lebih rapi &amp; cuan?</h2>
                    <p class="text-slate-400 mt-3 max-w-xl mx-auto">Daftar jadi member sekarang untuk mulai mengelola laundry Anda — atau arahkan pelanggan mendaftar sendiri lewat QR.</p>
                    <div class="flex flex-col sm:flex-row gap-3 mt-8 justify-center">
                        @auth
                            <a href="{{ route('dashboard') }}" class="bg-accent hover:bg-accent-hover text-white font-bold px-7 py-3.5 rounded-xl transition-all shadow-lg hover:-translate-y-0.5 text-sm flex items-center justify-center gap-2"><i data-lucide="layout-dashboard" class="h-5 w-5"></i>Buka Dashboard</a>
                        @else
                            <a href="{{ route('member.signup') }}" class="bg-accent hover:bg-accent-hover text-white font-bold px-7 py-3.5 rounded-xl transition-all shadow-lg hover:-translate-y-0.5 text-sm flex items-center justify-center gap-2"><i data-lucide="user-plus" class="h-5 w-5"></i>Daftar Jadi Member</a>
                            <a href="{{ route('login') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-100 font-bold px-7 py-3.5 rounded-xl transition-all border border-slate-750/40 text-sm flex items-center justify-center gap-2"><i data-lucide="log-in" class="h-5 w-5"></i>Masuk</a>
                        @endauth
                    </div>
                    <p class="relative text-xs text-slate-500 mt-5">Pelanggan laundry? <a href="{{ route('register.show') }}" class="text-accent font-semibold hover:underline">Daftar via QR di sini &rarr;</a></p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/70">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-accent/10 border border-accent/20 rounded-lg flex items-center justify-center overflow-hidden">@if($logoUrl)<img src="{{ $logoUrl }}" alt="Logo" class="w-full h-full object-cover">@else{{ $logoEmoji }}@endif</div>
                <div><p class="font-extrabold text-accent leading-none">{{ $nama }}</p><p class="text-[10px] text-slate-550 mt-0.5">Aplikasi Manajemen Laundry</p></div>
            </div>
            <div class="flex items-center gap-5 text-sm font-semibold text-slate-400">
                <a href="#fitur" class="hover:text-accent transition-colors">Fitur</a>
                @guest<a href="{{ route('member.signup') }}" class="hover:text-accent transition-colors">Daftar Member</a>@endguest
                <a href="{{ route('register.show') }}" class="hover:text-accent transition-colors">Daftar Pelanggan</a>
                @auth<a href="{{ route('dashboard') }}" class="hover:text-accent transition-colors">Dashboard</a>@else<a href="{{ route('login') }}" class="hover:text-accent transition-colors">Masuk</a>@endauth
            </div>
            <p class="text-xs text-slate-550">&copy; {{ date('Y') }} {{ $nama }}</p>
        </div>
    </footer>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
