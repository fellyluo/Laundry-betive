@extends('layouts.app')

@section('content')
@php
    function poin_type_badge($type) {
        return match($type) {
            'earn'     => ['bg-emerald-500/10 text-emerald-400 border border-emerald-500/20', 'Dapat Poin'],
            'redeem'   => ['bg-amber-500/10 text-amber-400 border border-amber-500/20', 'Tukar Poin'],
            'reversal' => ['bg-rose-500/10 text-rose-400 border border-rose-500/20', 'Pembatalan'],
            'adjust'   => ['bg-blue-500/10 text-blue-400 border border-blue-500/20', 'Penyesuaian'],
            default    => ['bg-slate-500/10 text-slate-400 border border-slate-500/20', $type],
        };
    }
@endphp

<div class="space-y-8 max-w-3xl">
    <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-200 transition-colors text-sm font-semibold"><i data-lucide="arrow-left" class="h-4 w-4"></i><span>Kembali ke Master Pelanggan</span></a>

    <!-- Header kartu pelanggan -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-accent/10 text-accent rounded-xl"><i data-lucide="user" class="h-7 w-7"></i></div>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white">{{ $customer->nama }}</h1>
                <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $customer->no_hp }}</p>
            </div>
        </div>
        <div class="text-right">
            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Saldo Poin Saat Ini</span>
            <span class="inline-flex items-center gap-2 mt-1 px-4 py-1.5 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded-full text-lg font-black shadow-inner"><i data-lucide="award" class="h-5 w-5"></i>{{ $customer->poin }}</span>
        </div>
    </div>

    <!-- Riwayat -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
        <h2 class="font-bold text-white text-sm flex items-center gap-2"><i data-lucide="history" class="h-4 w-4 text-accent"></i><span>Riwayat Mutasi Poin</span></h2>

        @if($transactions->isEmpty())
            <div class="text-center py-12 text-slate-500 text-sm italic">
                <i data-lucide="award" class="h-10 w-10 mx-auto mb-3 text-slate-700"></i>
                Belum ada mutasi poin untuk pelanggan ini.
            </div>
        @else
            <div class="space-y-3">
                @foreach($transactions as $trx)
                    @php([$badgeClass, $badgeLabel] = poin_type_badge($trx->type))
                    <div class="flex items-center justify-between gap-4 p-4 bg-slate-950/60 border border-slate-850 rounded-xl">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                @if($trx->order)
                                    <a href="{{ route('orders.show', $trx->order_id) }}" class="text-[11px] font-mono text-slate-400 hover:text-accent hover:underline">Nota {{ $trx->order->nomor_nota }}</a>
                                @endif
                            </div>
                            @if($trx->note)<p class="text-xs text-slate-400 truncate">{{ $trx->note }}</p>@endif
                            <span class="text-[11px] text-slate-500 block">{{ format_date($trx->created_at, true) }}</span>
                        </div>
                        <span class="font-mono font-extrabold text-base shrink-0 {{ $trx->points >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ $trx->points >= 0 ? '+' : '' }}{{ $trx->points }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="pt-2">{{ $transactions->links() }}</div>
        @endif
    </div>
</div>
@endsection
