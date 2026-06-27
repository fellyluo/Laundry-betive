@extends('layouts.auth')
@section('title', 'Lupa Password')

@section('content')
<h2 class="text-lg font-bold text-white mb-1">Lupa Password?</h2>
<p class="text-slate-400 text-xs mb-5">Demi keamanan akun, reset password tidak dilakukan sendiri.</p>

<div class="p-4 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 leading-relaxed">
    <div class="flex items-start gap-2">
        <i data-lucide="shield-check" class="h-4 w-4 shrink-0 mt-0.5 text-accent"></i>
        <span>Silakan hubungi <b class="text-white">admin</b> untuk mengatur ulang password Anda. Admin akan mereset password lewat menu <b>Manajemen Pengguna</b>, lalu memberi Anda password sementara.</span>
    </div>
</div>

@if(!empty($adminPhone))
    <a href="{{ wa_link($adminPhone, 'Halo Admin, saya lupa password akun saya dan ingin meminta reset.') }}"
       target="_blank" rel="noopener"
       class="mt-4 w-full py-3 bg-accent hover:bg-accent-hover text-white font-bold rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 text-sm flex items-center justify-center gap-2">
        <i data-lucide="message-circle" class="h-4 w-4"></i>
        Hubungi Admin via WhatsApp
    </a>
@endif

<a href="{{ route('login') }}" class="mt-4 block text-center text-xs text-slate-400 hover:text-slate-200 font-semibold">&larr; Kembali ke halaman masuk</a>
@endsection
