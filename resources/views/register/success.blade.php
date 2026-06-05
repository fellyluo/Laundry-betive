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

    <p class="text-[11px] text-slate-500">Tunjukkan halaman ini ke kasir, atau sebutkan nama/HP Anda saat datang. Selamat datang sebagai member! 🎉</p>

    <a href="{{ route('register.show') }}" class="inline-block text-xs text-accent hover:text-accent/90 font-semibold">Daftarkan pelanggan lain &rarr;</a>
</div>
@endsection
