@extends('layouts.app')

@section('content')
@php
    function saldo_type_badge($type) {
        return match($type) {
            'topup'   => ['bg-emerald-500/10 text-emerald-400 border border-emerald-500/20', 'Top-up'],
            'payment' => ['bg-amber-500/10 text-amber-400 border border-amber-500/20', 'Bayar Order'],
            'refund'  => ['bg-blue-500/10 text-blue-400 border border-blue-500/20', 'Refund'],
            'adjust'  => ['bg-slate-500/10 text-slate-400 border border-slate-500/20', 'Penyesuaian'],
            default   => ['bg-slate-500/10 text-slate-400 border border-slate-500/20', $type],
        };
    }
@endphp

<div class="space-y-8 max-w-3xl" x-data>
    <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-200 transition-colors text-sm font-semibold"><i data-lucide="arrow-left" class="h-4 w-4"></i><span>Kembali ke Master Pelanggan</span></a>

    @if(session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-2 animate-in"><i data-lucide="check-circle-2" class="h-5 w-5 shrink-0"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-2"><i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i><span>{{ $errors->first() }}</span></div>
    @endif

    <!-- Header + saldo -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-accent/10 text-accent rounded-xl"><i data-lucide="wallet" class="h-7 w-7"></i></div>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white">{{ $customer->nama }}</h1>
                <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $customer->no_hp }}</p>
            </div>
        </div>
        <div class="text-right">
            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Saldo Dompet</span>
            <span class="text-2xl font-black text-emerald-400 font-mono">{{ format_rupiah($customer->saldo) }}</span>
        </div>
    </div>

    <!-- Form top-up -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
        <h2 class="font-bold text-white text-sm flex items-center gap-2"><i data-lucide="plus-circle" class="h-4 w-4 text-accent"></i><span>Isi Saldo (Top-up)</span></h2>
        <form method="POST" action="{{ route('customers.topup', $customer) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            @csrf
            <div class="sm:col-span-1">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nominal (Rp)</label>
                <input type="number" name="jumlah" min="1" step="1000" placeholder="50000" required class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white font-bold focus:outline-none transition-all text-sm font-mono">
            </div>
            <div class="sm:col-span-1">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Metode</label>
                <select name="metode" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white font-semibold text-sm focus:outline-none transition-all">
                    @foreach($methods as $m)<option value="{{ $m['nama'] }}">{{ $m['nama'] }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="sm:col-span-1 py-2.5 bg-accent hover:bg-accent-hover text-white font-bold rounded-xl transition-all shadow-lg text-sm">Tambah Saldo</button>
        </form>
    </div>

    <!-- Riwayat -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
        <h2 class="font-bold text-white text-sm flex items-center gap-2"><i data-lucide="history" class="h-4 w-4 text-accent"></i><span>Riwayat Mutasi Saldo</span></h2>

        @if($transactions->isEmpty())
            <div class="text-center py-12 text-slate-500 text-sm italic">
                <i data-lucide="wallet" class="h-10 w-10 mx-auto mb-3 text-slate-700"></i>
                Belum ada mutasi saldo untuk pelanggan ini.
            </div>
        @else
            <div class="space-y-3">
                @foreach($transactions as $trx)
                    @php([$badgeClass, $badgeLabel] = saldo_type_badge($trx->type))
                    <div class="flex items-center justify-between gap-4 p-4 bg-slate-950/60 border border-slate-850 rounded-xl">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                @if($trx->metode)<span class="text-[10px] text-slate-500 uppercase">{{ $trx->metode }}</span>@endif
                                @if($trx->order)
                                    <a href="{{ route('orders.show', $trx->order_id) }}" class="text-[11px] font-mono text-slate-400 hover:text-accent hover:underline">Nota {{ $trx->order->nomor_nota }}</a>
                                @endif
                            </div>
                            @if($trx->note)<p class="text-xs text-slate-400 truncate">{{ $trx->note }}</p>@endif
                            <span class="text-[11px] text-slate-500 block">{{ format_date($trx->created_at, true) }}</span>
                        </div>
                        <span class="font-mono font-extrabold text-sm shrink-0 {{ $trx->amount >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ $trx->amount >= 0 ? '+' : '- ' }}{{ format_rupiah(abs($trx->amount)) }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="pt-2">{{ $transactions->links() }}</div>
        @endif
    </div>
</div>
@endsection
