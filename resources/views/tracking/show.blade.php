@extends('layouts.auth')
@section('title', 'Status Order '.$order->nomor_nota)

@section('content')
@php
    $steps = ['diterima' => 'Diterima', 'diproses' => 'Sedang Dicuci', 'selesai' => 'Selesai & Siap Diambil', 'diambil' => 'Sudah Diambil'];
    $stepKeys = array_keys($steps);
    $isCancelled = $order->status === 'dibatalkan';
    $currentIndex = array_search($order->status, $stepKeys);
    if ($currentIndex === false) $currentIndex = -1;

    $logByStatus = $order->logs->keyBy('status');

    $totalPaid = $order->payments->sum('jumlah');
    $diskonPoin = (int) $order->diskon_poin;
    $diskon = (int) $order->diskon;
    $net = max(0, $order->total - $diskonPoin - $diskon);
    $sisa = max(0, $net - $totalPaid);

    $statusBadge = match($order->status) {
        'diterima' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'diproses' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'selesai' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'diambil' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
        'dibatalkan' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        default => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
    };
@endphp

<div class="space-y-5">
    <div class="text-center space-y-2">
        <span class="font-mono text-sm text-slate-400 block">Nota</span>
        <span class="font-mono text-xl font-black text-white block">{{ $order->nomor_nota }}</span>
        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold capitalize border {{ $statusBadge }}">{{ $order->status }}</span>
    </div>

    @if($isCancelled)
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-xs text-center">Pesanan ini telah <b>dibatalkan</b>. Silakan hubungi outlet untuk informasi lebih lanjut.</div>
    @else
        <!-- Timeline langkah -->
        <div class="relative border-l-2 border-slate-800 pl-5 ml-2 space-y-5 py-1">
            @foreach($stepKeys as $i => $key)
                @php
                    $done = $i <= $currentIndex;
                    $isNow = $i === $currentIndex;
                    $log = $logByStatus->get($key);
                @endphp
                <div class="relative">
                    <span class="absolute -left-[27px] top-0.5 w-4 h-4 rounded-full flex items-center justify-center border-2 {{ $done ? 'bg-accent border-accent' : 'bg-slate-950 border-slate-700' }}">
                        @if($done)<span class="w-1.5 h-1.5 bg-white rounded-full"></span>@endif
                    </span>
                    <div class="space-y-0.5">
                        <span class="text-sm font-bold {{ $done ? 'text-white' : 'text-slate-500' }}">{{ $steps[$key] }}
                            @if($isNow)<span class="text-[9px] bg-accent/10 text-accent px-2 py-0.5 rounded-full border border-accent/20 font-semibold uppercase ml-1">Saat ini</span>@endif
                        </span>
                        @if($log)<span class="text-[11px] text-slate-500 block">{{ format_date($log->created_at, true) }}</span>@endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="bg-slate-950/50 border border-slate-850 rounded-xl p-4 space-y-2 text-xs">
        <div class="flex justify-between text-slate-400"><span>Pelanggan</span><span class="font-semibold text-slate-200">{{ $order->customer->nama ?? 'Umum' }}</span></div>
        <div class="flex justify-between text-slate-400"><span>Estimasi Selesai</span><span class="font-semibold text-slate-200">{{ format_date($order->estimasi_selesai, true) }}</span></div>
    </div>

    <!-- Rincian -->
    <div class="bg-slate-950/50 border border-slate-850 rounded-xl p-4 space-y-2">
        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Rincian</h3>
        @foreach($order->items as $item)
            <div class="flex justify-between text-xs text-slate-400">
                <span>{{ $item->service->nama ?? 'Item' }} <span class="text-slate-600">×{{ rtrim(rtrim(number_format($item->qty,2,'.',''),'0'),'.') }} {{ $item->service->satuan ?? '' }}</span></span>
                <span class="font-mono text-slate-300">{{ format_rupiah($item->subtotal) }}</span>
            </div>
        @endforeach
        <div class="border-t border-slate-800 pt-2 mt-2 space-y-1">
            <div class="flex justify-between text-xs text-slate-400"><span>Total</span><span class="font-mono">{{ format_rupiah($order->total) }}</span></div>
            @if($diskonPoin > 0)
                <div class="flex justify-between text-xs text-amber-400"><span>Potongan Poin</span><span class="font-mono">- {{ format_rupiah($diskonPoin) }}</span></div>
            @endif
            @if($diskon > 0)
                <div class="flex justify-between text-xs text-accent"><span>Diskon{{ $order->voucher_code ? ' ('.$order->voucher_code.')' : '' }}</span><span class="font-mono">- {{ format_rupiah($diskon) }}</span></div>
            @endif
            <div class="flex justify-between text-xs text-slate-400"><span>Dibayar</span><span class="font-mono text-emerald-400">{{ format_rupiah($totalPaid) }}</span></div>
            <div class="flex justify-between text-sm font-bold text-white"><span>Sisa Tagihan</span><span class="font-mono text-accent">{{ format_rupiah($sisa) }}</span></div>
        </div>
    </div>

    <a href="{{ route('track.index') }}" class="block text-center text-xs text-slate-500 hover:text-slate-300">Lacak order lain</a>
</div>
@endsection
