@extends('layouts.auth')
@section('title', 'Lupa Password')

@section('content')
<h2 class="text-lg font-bold text-white mb-1">Atur Ulang Password</h2>
<p class="text-slate-400 text-xs mb-5">Masukkan username dan password baru Anda.</p>

@if($errors->any())
    <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-xs flex items-center gap-2"><i data-lucide="alert-triangle" class="h-4 w-4 shrink-0"></i><span>{{ $errors->first() }}</span></div>
@endif

<form method="POST" action="{{ route('password.reset') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Username</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500"><i data-lucide="user" class="h-4 w-4"></i></span>
            <input type="text" name="username" value="{{ old('username') }}" autofocus placeholder="username"
                   class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl pl-10 pr-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all text-sm">
        </div>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Password Baru</label>
        <div class="relative">
            <input type="password" name="password" id="fpass1" placeholder="Minimal 6 karakter"
                   class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 pr-10 text-white placeholder-slate-600 focus:outline-none transition-all text-sm">
            <button type="button" onclick="togglePass('fpass1', this)" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-500 hover:text-slate-300"><i data-lucide="eye" class="h-4 w-4"></i></button>
        </div>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Konfirmasi Password Baru</label>
        <div class="relative">
            <input type="password" name="password_confirmation" id="fpass2" placeholder="Ulangi password baru"
                   class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 pr-10 text-white placeholder-slate-600 focus:outline-none transition-all text-sm">
            <button type="button" onclick="togglePass('fpass2', this)" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-500 hover:text-slate-300"><i data-lucide="eye" class="h-4 w-4"></i></button>
        </div>
    </div>

    <button type="submit" class="w-full py-3 bg-accent hover:bg-accent-hover text-white font-bold rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 text-sm">
        Simpan Password Baru
    </button>
    <a href="{{ route('login') }}" class="block text-center text-xs text-slate-400 hover:text-slate-200 font-semibold">&larr; Kembali ke halaman masuk</a>
</form>

<div class="mt-5 pt-4 border-t border-slate-850 text-[10px] text-slate-550 flex items-start gap-2">
    <i data-lucide="info" class="h-3.5 w-3.5 shrink-0 mt-0.5"></i>
    <span>Demi keamanan, sebaiknya reset password dilakukan oleh pemilik. Anda juga bisa mengganti password user lain di <b>Pengaturan &rarr; Manajemen Pengguna</b>.</span>
</div>

<script>
    function togglePass(id, btn) {
        const inp = document.getElementById(id);
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        btn.innerHTML = show ? '<i data-lucide="eye-off" class="h-4 w-4"></i>' : '<i data-lucide="eye" class="h-4 w-4"></i>';
        if (window.lucide) lucide.createIcons();
    }
</script>
@endsection
