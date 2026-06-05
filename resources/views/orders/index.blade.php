@extends('layouts.app')

@section('content')
@php
    function ord_status_badge($s) {
        return match($s) {
            'diterima' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
            'diproses' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
            'selesai' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
            'diambil' => 'bg-slate-500/10 text-slate-400 border border-slate-500/20',
            'dibatalkan' => 'bg-rose-500/10 text-rose-450 border border-rose-500/20',
            default => 'bg-slate-550/10 text-slate-400',
        };
    }
    function pay_status_badge($s) {
        return match($s) {
            'belum' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
            'dp' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
            'lunas' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
            default => 'bg-slate-550/10 text-slate-400',
        };
    }
@endphp

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2"><i data-lucide="clipboard-list" class="h-8 w-8 text-accent"></i><span>Daftar Order Laundry</span></h1>
            <p class="text-slate-400 text-sm mt-1">Pantau cucian pelanggan, kelola alur pengerjaan, dan status transaksi pembayaran.</p>
        </div>
        <a href="{{ route('orders.create') }}" class="flex items-center gap-2 bg-accent hover:bg-accent-hover text-white font-semibold px-5 py-3 rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 active:translate-y-0 w-full sm:w-auto justify-center"><i data-lucide="plus" class="h-5 w-5"></i><span>Buat Order Baru</span></a>
    </div>

    <!-- Filter bar -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500"><i data-lucide="search" class="h-4.5 w-4.5"></i></span>
                <input type="text" id="orderSearch" oninput="filterOrders()" placeholder="Cari nota, pelanggan, HP..." class="w-full bg-slate-950 border border-slate-850 hover:border-slate-800 focus:border-accent focus:outline-none rounded-xl pl-10 pr-4 py-2.5 text-slate-200 placeholder-slate-600 transition-all text-sm">
            </div>
            <div class="flex items-center gap-2">
                <span class="text-slate-400 text-xs shrink-0 font-medium">Status:</span>
                <select id="orderStatus" onchange="filterOrders()" class="w-full bg-slate-950 border border-slate-855 focus:border-accent focus:outline-none rounded-xl px-3 py-2.5 text-slate-200 text-sm transition-all">
                    <option value="semua">Semua Status Pengerjaan</option>
                    <option value="diterima">Diterima</option>
                    <option value="diproses">Diproses (Sedang Dicuci)</option>
                    <option value="selesai">Selesai (Siap Diambil)</option>
                    <option value="diambil">Diambil (Sudah Diserahkan)</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-slate-400 text-xs shrink-0 font-medium">Bayar:</span>
                <select id="orderBayar" onchange="filterOrders()" class="w-full bg-slate-950 border border-slate-855 focus:border-accent focus:outline-none rounded-xl px-3 py-2.5 text-slate-200 text-sm transition-all">
                    <option value="semua">Semua Status Bayar</option>
                    <option value="belum">Belum Bayar</option>
                    <option value="lunas">Lunas</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end pt-1 hidden" id="orderResetWrap">
            <button onclick="resetOrderFilters()" class="flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-300 font-semibold transition-colors"><i data-lucide="x" class="h-3.5 w-3.5"></i><span>Reset Filter</span></button>
        </div>
    </div>

    @if($orders->isEmpty())
        <div class="bg-slate-900/40 border border-slate-850 p-12 rounded-3xl text-center max-w-md mx-auto space-y-4">
            <div class="mx-auto w-16 h-16 bg-slate-850 rounded-full flex items-center justify-center text-accent border border-slate-800"><i data-lucide="clipboard-list" class="h-8 w-8"></i></div>
            <div>
                <h3 class="text-lg font-bold text-white">Order Tidak Ditemukan</h3>
                <p class="text-slate-400 text-sm mt-1">Belum ada nota transaksi. Buat order pertama untuk memulai.</p>
            </div>
            <a href="{{ route('orders.create') }}" class="inline-block bg-accent hover:bg-accent-hover text-white font-semibold px-4 py-2 rounded-xl text-xs transition-all">Buat Order Baru</a>
        </div>
    @else
        <div class="space-y-4" id="orderList">
            @foreach($orders as $order)
                @php
                    $itemsSummary = $order->items->map(fn($i) => ($i->service->nama ?? 'Item').' ('.rtrim(rtrim(number_format($i->qty,2,'.',''),'0'),'.').($i->service->satuan ?? 'satuan').')')->implode(', ');
                @endphp
                <div class="order-card bg-slate-900/60 border border-slate-800 hover:border-slate-700 rounded-2xl p-5 shadow-xl transition-all duration-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4 group"
                     data-search="{{ strtolower($order->nomor_nota.' '.($order->customer->nama ?? '').' '.($order->customer->no_hp ?? '')) }}"
                     data-status="{{ $order->status }}" data-bayar="{{ $order->status_bayar }}">
                    <div class="flex flex-col md:flex-row md:items-center gap-4 md:gap-8 flex-1">
                        <div class="space-y-1">
                            <div class="font-mono font-bold text-slate-350 tracking-wide text-sm flex items-center gap-2">
                                <span>{{ $order->nomor_nota }}</span>
                                <span class="md:hidden flex items-center gap-1">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold capitalize {{ ord_status_badge($order->status) }}">{{ $order->status }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold capitalize {{ pay_status_badge($order->status_bayar) }}">{{ $order->status_bayar }}</span>
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-slate-500"><i data-lucide="calendar" class="h-3.5 w-3.5 text-slate-600"></i><span>Masuk: {{ format_date($order->tanggal_masuk, true) }}</span></div>
                        </div>
                        <div>
                            <div class="font-bold text-white group-hover:text-accent transition-colors">{{ $order->customer->nama ?? 'Umum' }}</div>
                            <div class="text-xs text-slate-450 mt-0.5 font-mono">{{ $order->customer->no_hp ?? '-' }}</div>
                        </div>
                        <div class="hidden lg:block text-xs text-slate-500 max-w-xs truncate">{{ $itemsSummary }}</div>
                    </div>
                    <div class="flex items-center justify-between md:justify-end gap-6 border-t md:border-t-0 border-slate-850/80 pt-3 md:pt-0">
                        <div class="text-left md:text-right">
                            <span class="text-[10px] text-slate-500 block">Total Biaya</span>
                            <span class="font-black text-white text-base">{{ format_rupiah($order->total) }}</span>
                        </div>
                        <div class="hidden md:flex flex-col gap-1.5">
                            <span class="px-3 py-1 rounded-full text-xs font-bold text-center capitalize {{ ord_status_badge($order->status) }}">{{ $order->status }}</span>
                            <span class="px-3 py-0.5 rounded-full text-[10px] font-bold text-center capitalize {{ pay_status_badge($order->status_bayar) }}">{{ $order->status_bayar }}</span>
                        </div>
                        <a href="{{ route('orders.show', $order) }}" class="flex items-center gap-1 bg-slate-800 hover:bg-accent hover:text-white text-slate-200 font-semibold px-4 py-2.5 rounded-xl transition-all duration-200 text-xs shadow-md border border-slate-750/30 shrink-0"><span>Detail</span><i data-lucide="arrow-right" class="h-3.5 w-3.5"></i></a>
                    </div>
                </div>
            @endforeach
        </div>
        <div id="orderNoMatch" class="hidden bg-slate-900/40 border border-slate-850 p-12 rounded-3xl text-center max-w-md mx-auto space-y-4">
            <div class="mx-auto w-16 h-16 bg-slate-850 rounded-full flex items-center justify-center text-accent border border-slate-800"><i data-lucide="clipboard-list" class="h-8 w-8"></i></div>
            <div>
                <h3 class="text-lg font-bold text-white">Order Tidak Ditemukan</h3>
                <p class="text-slate-400 text-sm mt-1">Tidak ada nota transaksi yang cocok dengan filter atau pencarian Anda.</p>
            </div>
            <button onclick="resetOrderFilters()" class="bg-slate-800 hover:bg-slate-700 text-white font-semibold px-4 py-2 rounded-xl text-xs transition-all">Reset Pencarian</button>
        </div>
    @endif
</div>

@push('scripts')
<script>
    function filterOrders() {
        const q = document.getElementById('orderSearch').value.toLowerCase().trim();
        const st = document.getElementById('orderStatus').value;
        const by = document.getElementById('orderBayar').value;
        const cards = document.querySelectorAll('.order-card');
        let visible = 0;
        cards.forEach(c => {
            let ok = (!q || c.dataset.search.includes(q));
            if (ok && st !== 'semua') ok = c.dataset.status === st;
            if (ok && by !== 'semua') ok = c.dataset.bayar === by;
            c.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });
        const noMatch = document.getElementById('orderNoMatch');
        const list = document.getElementById('orderList');
        if (noMatch && list) {
            noMatch.classList.toggle('hidden', visible !== 0);
        }
        const active = q || st !== 'semua' || by !== 'semua';
        document.getElementById('orderResetWrap').classList.toggle('hidden', !active);
    }
    function resetOrderFilters() {
        document.getElementById('orderSearch').value = '';
        document.getElementById('orderStatus').value = 'semua';
        document.getElementById('orderBayar').value = 'semua';
        filterOrders();
    }
</script>
@endpush
@endsection
