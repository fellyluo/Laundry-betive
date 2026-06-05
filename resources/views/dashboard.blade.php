@extends('layouts.app')

@section('content')
@php
    $namaLaundry = $appSettings['branding']['nama_laundry'] ?? 'LaundryPro';
    function dash_status_badge($s) {
        return match($s) {
            'diterima' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
            'diproses' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
            'selesai' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
            'diambil' => 'bg-slate-500/10 text-slate-400 border border-slate-500/20',
            'dibatalkan' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
            default => 'bg-slate-500/10 text-slate-400',
        };
    }
    $revToday = $revTodayLaundry + $revTodaySabun;
    $maxVal = max(array_map(fn($d) => $d['total'], $daily) ?: [0]);
    if ($maxVal < 1) $maxVal = 1;
    $last7Laundry = array_sum(array_map(fn($d) => $d['laundry'], $daily));
    $last7Sabun = array_sum(array_map(fn($d) => $d['sabun'], $daily));
    $maxPopular = $popular ? max(array_map(fn($p) => $p['count'], $popular)) : 1;
    if ($maxPopular < 1) $maxPopular = 1;
@endphp

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="flex flex-col md:flex-row md:items-center gap-0 md:gap-2 text-2xl md:text-3xl font-extrabold tracking-tight text-white leading-tight">
                <span>Selamat Datang di</span>
                <span class="text-accent flex items-center gap-1.5">
                    {{ $namaLaundry }}
                    <i data-lucide="washing-machine" class="h-6 w-6 text-accent animate-pulse shrink-0"></i>
                </span>
            </h1>
            <p class="text-slate-400 text-sm mt-0.5">
                Ringkasan operasional bisnis laundry Anda hari ini, {{ format_date(now()) }}.
            </p>
        </div>
        <a href="{{ route('orders.create') }}" class="flex items-center gap-2 bg-accent hover:bg-accent-hover text-white font-medium px-5 py-3 rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 active:translate-y-0 w-full sm:w-auto justify-center">
            <i data-lucide="plus" class="h-5 w-5"></i>
            <span>Buat Order Baru</span>
        </a>
    </div>

    <!-- Stats Cards (clickable → menuju bagiannya). Angka di baris sendiri (full width, tidak terpotong) -->
    <div class="grid grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6 gap-4 lg:gap-6">
        <a href="{{ route('orders.index') }}" class="bg-slate-900/60 border border-slate-800/80 p-4 sm:p-5 rounded-xl hover:border-accent/30 hover:-translate-y-0.5 transition-all group shadow-lg flex flex-col justify-between min-h-[110px] sm:min-h-[120px]">
            <div class="flex justify-between items-start gap-2">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate">Omzet Laundry</p>
                <div class="p-2 bg-accent/10 text-accent rounded-lg group-hover:bg-accent/20 transition-colors shrink-0"><i data-lucide="wallet" class="h-4.5 w-4.5"></i></div>
            </div>
            <h3 class="text-lg sm:text-xl font-bold text-white mt-1 leading-tight group-hover:text-accent transition-colors">{{ format_rupiah($revTodayLaundry) }}</h3>
            <div class="flex items-center gap-1 text-[11px] font-semibold text-slate-400 mt-3 border-t border-slate-800/50 pt-2 shrink-0">
                <i data-lucide="trending-up" class="h-3 w-3 text-accent shrink-0"></i><span class="truncate">Jasa cuci hari ini</span>
            </div>
        </a>

        <a href="{{ route('orders.index') }}" class="bg-slate-900/60 border border-slate-800/80 p-4 sm:p-5 rounded-xl hover:border-amber-500/30 hover:-translate-y-0.5 transition-all group shadow-lg flex flex-col justify-between min-h-[110px] sm:min-h-[120px]">
            <div class="flex justify-between items-start gap-2">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate">Omzet Sabun</p>
                <div class="p-2 bg-amber-500/10 text-amber-500 rounded-lg group-hover:bg-amber-500/20 transition-colors shrink-0"><i data-lucide="shopping-bag" class="h-4.5 w-4.5"></i></div>
            </div>
            <h3 class="text-lg sm:text-xl font-bold text-white mt-1 leading-tight group-hover:text-amber-400 transition-colors">{{ format_rupiah($revTodaySabun) }}</h3>
            <div class="flex items-center gap-1 text-[11px] font-semibold text-slate-400 mt-3 border-t border-slate-800/50 pt-2 shrink-0">
                <i data-lucide="trending-up" class="h-3 w-3 text-amber-500 shrink-0"></i><span class="truncate">Jual sabun hari ini</span>
            </div>
        </a>

        <a href="{{ route('orders.index', ['status' => 'diproses']) }}" class="bg-slate-900/60 border border-slate-800/80 p-4 sm:p-5 rounded-xl hover:border-amber-500/30 hover:-translate-y-0.5 transition-all group shadow-lg flex flex-col justify-between min-h-[110px] sm:min-h-[120px]">
            <div class="flex justify-between items-start gap-2">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate">Diproses</p>
                <div class="p-2 bg-amber-600/10 text-amber-400 rounded-lg group-hover:bg-amber-600/20 transition-colors shrink-0"><i data-lucide="clock" class="h-4.5 w-4.5"></i></div>
            </div>
            <h3 class="text-lg sm:text-xl font-bold text-white mt-1 leading-tight">{{ $activeCount }} <span class="text-xs font-normal text-slate-400">Order</span></h3>
            <div class="text-[11px] font-semibold text-slate-400 mt-3 border-t border-slate-800/50 pt-2 truncate shrink-0">Cucian sedang dicuci/setrika</div>
        </a>

        <a href="{{ route('orders.index', ['status' => 'selesai']) }}" class="bg-slate-900/60 border border-slate-800/80 p-4 sm:p-5 rounded-xl hover:border-emerald-500/30 hover:-translate-y-0.5 transition-all group shadow-lg flex flex-col justify-between min-h-[110px] sm:min-h-[120px]">
            <div class="flex justify-between items-start gap-2">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate">Siap Diambil</p>
                <div class="p-2 bg-emerald-600/10 text-emerald-400 rounded-lg group-hover:bg-emerald-600/20 transition-colors shrink-0"><i data-lucide="check-circle-2" class="h-4.5 w-4.5"></i></div>
            </div>
            <h3 class="text-lg sm:text-xl font-bold text-white mt-1 leading-tight">{{ $readyCount }} <span class="text-xs font-normal text-slate-400">Order</span></h3>
            <div class="text-[11px] font-semibold text-slate-400 mt-3 border-t border-slate-800/50 pt-2 truncate shrink-0">Selesai &amp; siap diserahkan</div>
        </a>

        <a href="{{ route('customers.index') }}" class="bg-slate-900/60 border border-slate-800/80 p-4 sm:p-5 rounded-xl hover:border-blue-500/30 hover:-translate-y-0.5 transition-all group shadow-lg flex flex-col justify-between min-h-[110px] sm:min-h-[120px]">
            <div class="flex justify-between items-start gap-2">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate">Pelanggan</p>
                <div class="p-2 bg-blue-600/10 text-blue-400 rounded-lg group-hover:bg-blue-600/20 transition-colors shrink-0"><i data-lucide="users-2" class="h-4.5 w-4.5"></i></div>
            </div>
            <h3 class="text-lg sm:text-xl font-bold text-white mt-1 leading-tight">{{ $customersCount }} <span class="text-xs font-normal text-slate-400">Orang</span></h3>
            <div class="text-[11px] font-semibold text-slate-400 mt-3 border-t border-slate-800/50 pt-2 truncate shrink-0">Pelanggan terdaftar aktif</div>
        </a>

        <a href="{{ route('expenses.index') }}" class="bg-slate-900/60 border border-slate-800/80 p-4 sm:p-5 rounded-xl hover:border-rose-500/30 hover:-translate-y-0.5 transition-all group shadow-lg flex flex-col justify-between min-h-[110px] sm:min-h-[120px]">
            <div class="flex justify-between items-start gap-2">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate">Pengeluaran</p>
                <div class="p-2 bg-rose-500/10 text-rose-400 rounded-lg group-hover:bg-rose-500/20 transition-colors shrink-0"><i data-lucide="receipt" class="h-4.5 w-4.5"></i></div>
            </div>
            <h3 class="text-lg sm:text-xl font-bold text-rose-400 mt-1 leading-tight">{{ format_rupiah($expensesToday) }}</h3>
            <div class="flex items-center gap-1 text-[11px] font-semibold text-slate-400 mt-3 border-t border-slate-800/50 pt-2 shrink-0">
                <i data-lucide="trending-down" class="h-3 w-3 text-rose-400 shrink-0"></i><span class="truncate">Biaya hari ini</span>
            </div>
        </a>
    </div>

    @if($orders->isEmpty())
        <div class="bg-slate-900/40 border border-slate-850 p-12 rounded-3xl text-center max-w-2xl mx-auto space-y-6 mt-8 shadow-xl">
            <div class="mx-auto w-20 h-20 bg-slate-800/50 rounded-full flex items-center justify-center text-accent border border-slate-700/50"><i data-lucide="shopping-bag" class="h-10 w-10"></i></div>
            <div>
                <h3 class="text-xl font-bold text-white">Belum Ada Transaksi</h3>
                <p class="text-slate-400 text-sm mt-2 max-w-sm mx-auto">Mulai kelola bisnis laundry Anda dengan membuat order pertama! Pastikan Anda sudah menambahkan master layanan.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('services.index') }}" class="bg-slate-800 hover:bg-slate-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-all">Kelola Layanan</a>
                <a href="{{ route('orders.create') }}" class="bg-accent hover:bg-accent-hover text-white font-semibold px-5 py-2.5 rounded-xl transition-all">Tambah Order Pertama</a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Financial report -->
                <div class="bg-slate-900/40 border border-slate-850 rounded-2xl p-6 shadow-xl space-y-6">
                    <div>
                        <h3 class="font-bold text-lg text-white flex items-center gap-2"><i data-lucide="dollar-sign" class="h-5 w-5 text-accent"></i><span>Laporan Pendapatan &amp; Keuangan</span></h3>
                        <p class="text-slate-400 text-xs mt-0.5">Analisis kinerja keuangan berkala bisnis laundry Anda</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-slate-950/40 border border-slate-800/80 rounded-xl flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pendapatan Hari Ini</span>
                                <h4 class="text-xl font-bold text-white mt-1.5">{{ format_rupiah($revToday) }}</h4>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-850 flex items-center justify-between text-[10px] text-slate-500">
                                <span class="truncate">Kemarin: {{ format_rupiah($revYesterday) }}</span>
                                @php $diff = $revToday - $revYesterday; $pct = $revYesterday > 0 ? round($diff / $revYesterday * 100) : 0; @endphp
                                @if($diff > 0)<span class="text-emerald-400 font-semibold shrink-0">+{{ $pct }}%</span>
                                @elseif($diff < 0)<span class="text-rose-400 font-semibold shrink-0">{{ $pct }}%</span>
                                @else<span class="text-slate-500 font-semibold shrink-0">0%</span>@endif
                            </div>
                        </div>

                        <div class="p-4 bg-slate-950/40 border border-slate-800/80 rounded-xl flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">7 Hari Terakhir</span>
                                <h4 class="text-xl font-bold text-white mt-1.5">{{ format_rupiah($revLast7) }}</h4>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-850 flex items-center justify-between text-[10px] text-slate-500">
                                <span class="truncate">L: {{ format_rupiah($last7Laundry) }}</span>
                                <span class="truncate">S: {{ format_rupiah($last7Sabun) }}</span>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-950/40 border border-slate-800/80 rounded-xl flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pendapatan Bulan Ini</span>
                                <h4 class="text-xl font-bold text-white mt-1.5">{{ format_rupiah($revThisMonth) }}</h4>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-850 flex items-center justify-between text-[10px] text-slate-500">
                                <span class="truncate">Bulan Lalu: {{ format_rupiah($revLastMonth) }}</span>
                                @php $diffM = $revThisMonth - $revLastMonth; $pctM = $revLastMonth > 0 ? round($diffM / $revLastMonth * 100) : 0; @endphp
                                @if($diffM > 0)<span class="text-emerald-400 font-semibold shrink-0">+{{ $pctM }}%</span>
                                @elseif($diffM < 0)<span class="text-rose-400 font-semibold shrink-0">{{ $pctM }}%</span>
                                @else<span class="text-slate-500 font-semibold shrink-0">0%</span>@endif
                            </div>
                        </div>
                    </div>

                    <!-- Keuangan Bulan Ini: Pendapatan - Pengeluaran = Laba Bersih -->
                    <div class="pt-4 border-t border-slate-800/60 space-y-2">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5"><i data-lucide="wallet" class="h-3.5 w-3.5 text-accent"></i><span>Keuangan Bulan Ini (Laba Bersih)</span></h4>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="p-3 bg-slate-950/40 border border-slate-800/80 rounded-xl">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Pendapatan</span>
                                <div class="text-sm sm:text-base font-bold text-emerald-450 mt-1 truncate">{{ format_rupiah($revThisMonth) }}</div>
                            </div>
                            <a href="{{ route('expenses.index') }}" class="p-3 bg-slate-950/40 border border-slate-800/80 rounded-xl hover:border-rose-500/30 transition-all block">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Pengeluaran</span>
                                <div class="text-sm sm:text-base font-bold text-rose-400 mt-1 truncate">{{ format_rupiah($expensesThisMonth) }}</div>
                            </a>
                            <div class="p-3 bg-slate-950/40 border rounded-xl {{ $netThisMonth >= 0 ? 'border-emerald-500/20' : 'border-rose-500/20' }}">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Laba Bersih</span>
                                <div class="text-sm sm:text-base font-black mt-1 truncate {{ $netThisMonth >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ format_rupiah($netThisMonth) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- 7-day chart -->
                    <div class="pt-4 border-t border-slate-800/60">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5"><i data-lucide="bar-chart-3" class="h-3.5 w-3.5 text-accent"></i><span>Tren Omzet Harian (7 Hari Terakhir)</span></h4>
                            <div class="flex items-center gap-3 text-[9px] font-semibold">
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-accent inline-block"></span>Jasa Laundry</span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>Sabun / Produk</span>
                            </div>
                        </div>
                        <div class="h-36 flex items-end justify-between gap-1.5 sm:gap-3 pt-6 pb-2">
                            @foreach($daily as $day)
                                @php
                                    $heightPercent = min($day['total'] / $maxVal * 100, 100);
                                    $laundryPercent = $day['total'] > 0 ? $day['laundry'] / $day['total'] * 100 : 0;
                                    $soapPercent = $day['total'] > 0 ? $day['sabun'] / $day['total'] * 100 : 0;
                                    $shortTotal = $day['total'] > 0 ? str_replace('Rp ', '', format_rupiah($day['total'])) : '-';
                                @endphp
                                <div class="flex-1 flex flex-col items-center group cursor-pointer relative">
                                    <div class="absolute bottom-full mb-2 bg-slate-950 border border-slate-800 text-[10px] font-semibold text-white p-2.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap shadow-2xl z-20 space-y-1">
                                        <p class="text-slate-400 font-bold border-b border-slate-800 pb-1">{{ $day['dateStr'] }}</p>
                                        <p class="text-white">Omzet: <span class="font-bold text-accent">{{ format_rupiah($day['total']) }}</span></p>
                                        @if($day['total'] > 0)
                                        <div class="text-[9px] text-slate-400 space-y-0.5">
                                            <p class="flex justify-between gap-4"><span>Laundry:</span> <span class="font-medium text-slate-200">{{ format_rupiah($day['laundry']) }}</span></p>
                                            <p class="flex justify-between gap-4"><span>Sabun:</span> <span class="font-medium text-amber-400">{{ format_rupiah($day['sabun']) }}</span></p>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="w-full bg-slate-800/20 hover:bg-slate-850/60 transition-all rounded-t-lg h-24 flex flex-col justify-end overflow-hidden relative border border-slate-800/20 shadow-inner">
                                        <div class="w-full rounded-t-sm flex flex-col transition-all group-hover:scale-y-105 duration-200 origin-bottom" style="height: {{ $heightPercent }}%">
                                            <div class="w-full bg-amber-500" style="height: {{ $soapPercent }}%"></div>
                                            <div class="w-full bg-accent" style="height: {{ $laundryPercent }}%"></div>
                                        </div>
                                    </div>
                                    <span class="text-[9px] font-bold text-slate-400 mt-2 group-hover:text-white transition-colors">{{ $day['dayName'] }}</span>
                                    <span class="text-[8px] font-mono text-slate-550 mt-0.5 truncate max-w-full text-center">{{ $shortTotal }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Recent orders -->
                <div class="bg-slate-900/40 border border-slate-850 rounded-2xl p-6 shadow-xl space-y-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-lg text-white">Order Terbaru</h3>
                            <p class="text-slate-400 text-xs mt-0.5">Daftar 5 transaksi terbaru masuk</p>
                        </div>
                        <a href="{{ route('orders.index') }}" class="text-xs text-accent hover:text-accent/90 flex items-center gap-1 transition-colors font-semibold"><span>Lihat Semua</span><i data-lucide="arrow-right" class="h-3.5 w-3.5"></i></a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-800 text-slate-400 text-xs font-semibold">
                                    <th class="pb-3 w-28">Nota</th><th class="pb-3">Pelanggan</th><th class="pb-3 w-28">Total</th><th class="pb-3 w-28">Status</th><th class="pb-3 w-20 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-850">
                                @foreach($recentOrders as $order)
                                    <tr class="text-sm hover:bg-slate-800/10 transition-colors group">
                                        <td class="py-4 font-mono font-semibold text-slate-300">{{ $order->nomor_nota }}</td>
                                        <td class="py-4">
                                            <div class="font-medium text-white group-hover:text-accent transition-colors">{{ $order->customer->nama ?? 'Umum' }}</div>
                                            <div class="text-xs text-slate-500 mt-0.5">{{ $order->customer->no_hp ?? '-' }}</div>
                                        </td>
                                        <td class="py-4 font-bold text-slate-200">{{ format_rupiah($order->total) }}</td>
                                        <td class="py-4"><span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize {{ dash_status_badge($order->status) }}">{{ $order->status }}</span></td>
                                        <td class="py-4 text-right">
                                            <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center justify-center p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-lg transition-all" title="Detail Order"><i data-lucide="arrow-right" class="h-4 w-4"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right column -->
            <div class="space-y-8">
                <div class="bg-slate-900/40 border border-slate-850 rounded-2xl p-6 shadow-xl space-y-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-lg text-white">Layanan Terpopuler</h3>
                            <p class="text-slate-400 text-xs mt-0.5">Layanan paling sering dipesan</p>
                        </div>
                        <a href="{{ route('services.index') }}" class="text-xs text-accent hover:text-accent/90 flex items-center gap-1 font-semibold shrink-0"><span>Kelola</span><i data-lucide="arrow-right" class="h-3.5 w-3.5"></i></a>
                    </div>
                    @if(empty($popular))
                        <p class="text-slate-500 text-sm py-4 text-center">Belum ada statistik layanan</p>
                    @else
                        <div class="space-y-4">
                            @foreach($popular as $service)
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-slate-200">{{ $service['name'] }}</span>
                                        <span class="text-slate-400 text-xs font-mono">{{ $service['count'] }}x dipesan</span>
                                    </div>
                                    <div class="w-full bg-slate-850 h-2 rounded-full overflow-hidden">
                                        <div class="bg-gradient-to-r from-teal-600 to-teal-400 h-full rounded-full" style="width: {{ $service['count'] / $maxPopular * 100 }}%"></div>
                                    </div>
                                    <div class="text-[10px] text-slate-500 text-right">Akumulasi omzet: <span class="font-semibold">{{ format_rupiah($service['revenue']) }}</span></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="bg-slate-900/40 border border-slate-850 rounded-2xl p-6 shadow-xl space-y-6">
                    <div>
                        <h3 class="font-bold text-lg text-white flex items-center gap-2"><i data-lucide="shopping-bag" class="h-5 w-5 text-amber-500"></i><span>Penjualan Sabun &amp; Produk</span></h3>
                        <p class="text-slate-400 text-xs mt-0.5">Ringkasan akumulasi produk sabun terjual</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl">
                            <div class="text-xs text-amber-450 font-semibold">Total Terjual</div>
                            <div class="text-xl font-black text-white mt-1">{{ $soapCount }} <span class="text-xs font-normal text-slate-400">Pcs</span></div>
                        </div>
                        <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                            <div class="text-xs text-emerald-450 font-semibold">Total Pendapatan</div>
                            <div class="text-xl font-black text-white mt-1">{{ format_rupiah($soapRevenue) }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900/40 border border-slate-850 rounded-2xl p-6 shadow-xl space-y-6">
                    <div>
                        <h3 class="font-bold text-lg text-white">Status Pembayaran</h3>
                        <p class="text-slate-400 text-xs mt-0.5">Pembagian tagihan transaksi saat ini</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <a href="{{ route('orders.index', ['bayar' => 'belum']) }}" class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl block hover:-translate-y-0.5 transition-all">
                            <div class="text-xs text-rose-400 font-semibold">Belum Bayar</div>
                            <div class="text-lg font-black text-white mt-1">{{ $belumCount }}</div>
                        </a>
                        <a href="{{ route('orders.index', ['bayar' => 'lunas']) }}" class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl block hover:-translate-y-0.5 transition-all">
                            <div class="text-xs text-emerald-400 font-semibold">Lunas</div>
                            <div class="text-lg font-black text-white mt-1">{{ $lunasCount }}</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
