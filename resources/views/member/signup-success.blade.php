@extends('layouts.auth')
@section('title', 'Pendaftaran Member Berhasil')

@section('content')
<div class="text-center space-y-4">
    <div class="mx-auto w-16 h-16 bg-amber-500/10 border border-amber-500/20 rounded-full flex items-center justify-center text-amber-450">
        <i data-lucide="clock" class="h-9 w-9"></i>
    </div>
    <div>
        <h2 class="text-lg font-bold text-white">Pendaftaran Diterima!</h2>
        <p class="text-slate-400 text-xs mt-1">Terima kasih, <span class="font-semibold text-slate-200">{{ $user->name }}</span>. Akun Anda sedang <b class="text-amber-450">menunggu aktivasi</b>.</p>
    </div>

    <div class="text-left bg-slate-950/60 border border-slate-850 rounded-xl p-4 space-y-2 text-xs">
        <div class="flex justify-between gap-3"><span class="text-slate-500">Username</span><span class="font-mono font-semibold text-slate-200">{{ $user->username }}</span></div>
        <div class="flex justify-between gap-3"><span class="text-slate-500">Kontak</span><span class="font-mono text-slate-200">{{ $user->phone }}</span></div>
        <div class="flex justify-between gap-3"><span class="text-slate-500">Status</span><span class="font-semibold text-amber-450">Menunggu Aktivasi</span></div>
    </div>

    <p class="text-[11px] text-slate-500">Super Admin akan menghubungi Anda untuk pengaturan langganan/sewa. Setelah diaktifkan, Anda bisa langsung login dan mengelola laundry Anda.</p>

    <a href="{{ route('login') }}" class="block w-full py-3 bg-accent hover:bg-accent-hover text-white font-bold rounded-xl transition-all text-sm flex items-center justify-center gap-2"><i data-lucide="log-in" class="h-4.5 w-4.5"></i>Halaman Masuk</a>
</div>
@endsection
