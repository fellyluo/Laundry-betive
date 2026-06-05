@extends('layouts.auth')
@section('title', 'Daftar Member')

@section('content')
<h2 class="text-lg font-bold text-white mb-1">Daftar Jadi Member</h2>
<p class="text-slate-400 text-xs mb-5">Gunakan aplikasi ini untuk mengelola laundry Anda. Buat akun, lalu admin akan mengaktifkan langganan Anda.</p>

@if($errors->any())
    <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-xs flex items-center gap-2"><i data-lucide="alert-triangle" class="h-4 w-4 shrink-0"></i><span>{{ $errors->first() }}</span></div>
@endif

<form method="POST" action="{{ route('member.signup.store') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama / Nama Usaha</label>
        <input type="text" name="name" value="{{ old('name') }}" autofocus placeholder="cth: Laundry Bersih Jaya"
               class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all text-sm">
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">No. HP / WhatsApp</label>
        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx"
               class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all text-sm font-mono">
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Username (untuk login)</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500"><i data-lucide="user" class="h-4 w-4"></i></span>
            <input type="text" name="username" value="{{ old('username') }}" placeholder="username"
                   class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl pl-10 pr-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all text-sm font-mono">
        </div>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Password</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500"><i data-lucide="lock" class="h-4 w-4"></i></span>
            <input type="password" name="password" id="msPass" placeholder="Minimal 6 karakter"
                   class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl pl-10 pr-10 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all text-sm">
            <button type="button" onclick="togglePass('msPass', this)" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-500 hover:text-slate-300"><i data-lucide="eye" class="h-4 w-4"></i></button>
        </div>
    </div>

    <div class="flex items-start gap-2 text-[10px] text-slate-550 bg-slate-950/60 border border-slate-850 rounded-xl p-2.5">
        <i data-lucide="info" class="h-3.5 w-3.5 shrink-0 mt-0.5"></i>
        <span>Setelah daftar, akun Anda <b>menunggu aktivasi</b> oleh Super Admin. Anda akan dihubungi untuk pengaturan langganan/sewa.</span>
    </div>

    <button type="submit" class="w-full py-3 bg-accent hover:bg-accent-hover text-white font-bold rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 text-sm flex items-center justify-center gap-2">
        <i data-lucide="user-plus" class="h-4.5 w-4.5"></i><span>Daftar Sekarang</span>
    </button>
    <p class="text-center text-xs text-slate-500">Sudah punya akun? <a href="{{ route('login') }}" class="text-accent hover:text-accent/90 font-semibold">Masuk di sini</a></p>
</form>

<script>
    function togglePass(id, btn) {
        const inp = document.getElementById(id); const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        btn.innerHTML = show ? '<i data-lucide="eye-off" class="h-4 w-4"></i>' : '<i data-lucide="eye" class="h-4 w-4"></i>';
        if (window.lucide) lucide.createIcons();
    }
</script>
@endsection
