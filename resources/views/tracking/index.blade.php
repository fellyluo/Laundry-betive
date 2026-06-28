@extends('layouts.auth')
@section('title', 'Lacak Status Laundry')

@section('content')
<h2 class="text-lg font-bold text-white mb-1">Lacak Status Laundry</h2>
<p class="text-slate-400 text-xs mb-5">Masukkan nomor nota & nomor HP yang terdaftar untuk melihat status cucian Anda.</p>

@if(session('error'))
    <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-xs flex items-center gap-2"><i data-lucide="alert-triangle" class="h-4 w-4 shrink-0"></i><span>{{ session('error') }}</span></div>
@endif
@if($errors->any())
    <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-xs flex items-center gap-2"><i data-lucide="alert-triangle" class="h-4 w-4 shrink-0"></i><span>{{ $errors->first() }}</span></div>
@endif

<form method="POST" action="{{ route('track.find') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nomor Nota</label>
        <input type="text" name="nomor_nota" value="{{ old('nomor_nota') }}" autofocus placeholder="Contoh: 20260628-001"
               class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all text-sm font-mono">
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nomor HP</label>
        <input type="tel" name="no_hp" value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx"
               class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all text-sm font-mono">
    </div>
    <button type="submit" class="w-full py-3 bg-accent hover:bg-accent-hover text-white font-bold rounded-xl transition-all duration-200 shadow-lg flex items-center justify-center gap-2 text-sm"><i data-lucide="search" class="h-4 w-4"></i><span>Lacak Sekarang</span></button>
</form>
@endsection
