@extends('layouts.auth')
@section('title', 'Akses Dibekukan')

@section('content')
<div class="text-center space-y-4">
    <div class="mx-auto w-16 h-16 bg-rose-500/10 border border-rose-500/20 rounded-full flex items-center justify-center text-rose-400">
        <i data-lucide="lock" class="h-9 w-9"></i>
    </div>
    <div>
        <h2 class="text-lg font-bold text-white">
            @if(! $user->is_active) Akun Di-suspend @else Masa Sewa Berakhir @endif
        </h2>
        <p class="text-slate-400 text-xs mt-1">
            Halo <span class="font-semibold text-slate-200">{{ $user->username }}</span>,
            @if(! $user->is_active)
                akses akun Anda sedang dibekukan oleh Super Admin.
            @else
                masa langganan/sewa Anda telah berakhir{{ $user->subscribed_until ? ' pada '.format_date($user->subscribed_until) : '' }}.
            @endif
        </p>
    </div>

    <div class="bg-slate-950/60 border border-slate-850 rounded-xl p-4 text-left text-xs space-y-2">
        @if($user->plan)<div class="flex justify-between gap-3"><span class="text-slate-500">Paket</span><span class="font-semibold text-slate-200">{{ $user->plan }}</span></div>@endif
        @if($user->subscribed_until)<div class="flex justify-between gap-3"><span class="text-slate-500">Berlaku s/d</span><span class="font-semibold text-slate-200">{{ format_date($user->subscribed_until) }}</span></div>@endif
        <div class="flex justify-between gap-3"><span class="text-slate-500">Status</span><span class="font-semibold text-rose-400">{{ ! $user->is_active ? 'Suspend' : 'Kedaluwarsa' }}</span></div>
    </div>

    <p class="text-[11px] text-slate-500">Silakan hubungi Super Admin / pemilik aplikasi untuk memperpanjang langganan atau mengaktifkan kembali akun Anda.</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full py-3 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl transition-all text-sm flex items-center justify-center gap-2">
            <i data-lucide="log-out" class="h-4.5 w-4.5"></i><span>Keluar</span>
        </button>
    </form>
</div>
@endsection
