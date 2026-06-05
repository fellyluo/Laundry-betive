@extends('layouts.auth')
@section('title', 'Pendaftaran Berhasil')

@section('content')
<div class="text-center space-y-4">
    <div class="mx-auto w-16 h-16 bg-emerald-500/10 border border-emerald-500/20 rounded-full flex items-center justify-center text-emerald-400">
        <i data-lucide="check-circle-2" class="h-9 w-9"></i>
    </div>
    <div>
        <h2 class="text-lg font-bold text-white">Pendaftaran Berhasil!</h2>
        <p class="text-slate-400 text-xs mt-1">Terima kasih, <span class="font-semibold text-slate-200">{{ $customer->nama }}</span>. Data Anda sudah tersimpan.</p>
    </div>

    <div class="text-left bg-slate-950/60 border border-slate-850 rounded-xl p-4 space-y-2 text-xs">
        <div class="flex justify-between gap-3"><span class="text-slate-500">Nama</span><span class="font-semibold text-slate-200 text-right">{{ $customer->nama }}</span></div>
        <div class="flex justify-between gap-3"><span class="text-slate-500">No. HP</span><span class="font-mono text-slate-200">{{ $customer->no_hp }}</span></div>
        @if($customer->alamat)<div class="flex justify-between gap-3"><span class="text-slate-500">Alamat</span><span class="text-slate-300 text-right max-w-[60%]">{{ $customer->alamat }}</span></div>@endif
        @if($customer->metode_bayar)<div class="flex justify-between gap-3"><span class="text-slate-500">Metode Bayar</span><span class="font-semibold text-accent">{{ $customer->metode_bayar }}</span></div>@endif
    </div>

    @if(isset($order) && $order)
        <div class="text-left bg-accent/5 border border-accent/20 rounded-xl p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-accent uppercase tracking-wider flex items-center gap-1.5"><i data-lucide="clipboard-list" class="h-4 w-4"></i>Pesanan Diterima</span>
                <span class="font-mono text-xs font-bold text-slate-200">{{ $order->nomor_nota }}</span>
            </div>
            <div class="space-y-1 pt-1">
                @foreach($order->items as $it)
                    <div class="flex justify-between text-xs text-slate-300"><span>{{ $it->service->nama ?? 'Layanan' }} <span class="text-slate-500">({{ rtrim(rtrim(number_format($it->qty,2,'.',''),'0'),'.') }} {{ $it->service->satuan ?? '' }})</span></span><span class="font-mono">{{ format_rupiah($it->subtotal) }}</span></div>
                @endforeach
            </div>
            <div class="flex justify-between text-sm font-bold text-white border-t border-accent/20 pt-2"><span>Estimasi Total</span><span class="text-accent">{{ format_rupiah($order->total) }}</span></div>
            <p class="text-[10px] text-slate-500">Estimasi — total final dihitung setelah ditimbang di outlet. Estimasi selesai: {{ format_date($order->estimasi_selesai, true) }}.</p>
        </div>
    @endif

    <p class="text-[11px] text-slate-500">Tunjukkan halaman ini ke kasir, atau sebutkan nama/HP Anda saat datang. Selamat datang sebagai member! 🎉</p>

    <a href="{{ route('register.show', $customer->user_id) }}" class="inline-block text-xs text-accent hover:text-accent/90 font-semibold">Daftarkan pelanggan lain &rarr;</a>
</div>
@endsection
