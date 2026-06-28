@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white flex items-center gap-2"><i data-lucide="layout-dashboard" class="h-7 w-7 text-accent"></i><span>Dashboard Super Admin</span></h1>
            <p class="text-slate-400 text-sm mt-1">Pantau berjalannya bisnis semua member, {{ format_date(now()) }}.</p>
        </div>
        <a href="{{ route('members.index') }}" class="flex items-center gap-2 bg-accent hover:bg-accent-hover text-white font-semibold px-5 py-3 rounded-xl transition-all shadow-lg hover:-translate-y-0.5 w-full sm:w-auto justify-center"><i data-lucide="shield-check" class="h-5 w-5"></i><span>Kelola Member</span></a>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 lg:gap-6">
        <a href="{{ route('members.index') }}" class="bg-slate-900/60 border border-slate-800/80 p-4 sm:p-5 rounded-xl hover:border-accent/30 hover:-translate-y-0.5 transition-all group shadow-lg flex flex-col justify-between min-h-[110px]">
            <div class="flex justify-between items-start"><p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Total Member</p><div class="p-2 bg-accent/10 text-accent rounded-lg"><i data-lucide="users" class="h-4.5 w-4.5"></i></div></div>
            <h3 class="text-lg sm:text-xl font-bold text-white">{{ $totalMembers }}</h3>
        </a>
        <div class="bg-slate-900/60 border border-slate-800/80 p-4 sm:p-5 rounded-xl shadow-lg flex flex-col justify-between min-h-[110px]">
            <div class="flex justify-between items-start"><p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Member Aktif</p><div class="p-2 bg-emerald-500/10 text-emerald-450 rounded-lg"><i data-lucide="check-circle-2" class="h-4.5 w-4.5"></i></div></div>
            <h3 class="text-lg sm:text-xl font-bold text-emerald-450">{{ $aktif }}</h3>
        </div>
        <div class="bg-slate-900/60 border border-slate-800/80 p-4 sm:p-5 rounded-xl shadow-lg flex flex-col justify-between min-h-[110px]">
            <div class="flex justify-between items-start"><p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Total Order</p><div class="p-2 bg-blue-500/10 text-blue-400 rounded-lg"><i data-lucide="clipboard-list" class="h-4.5 w-4.5"></i></div></div>
            <h3 class="text-lg sm:text-xl font-bold text-white">{{ $totOrders }}</h3>
        </div>
        <div class="bg-slate-900/60 border border-slate-800/80 p-4 sm:p-5 rounded-xl shadow-lg flex flex-col justify-between min-h-[110px] col-span-2 lg:col-span-1">
            <div class="flex justify-between items-start"><p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Total Omzet</p><div class="p-2 bg-accent/10 text-accent rounded-lg"><i data-lucide="wallet" class="h-4.5 w-4.5"></i></div></div>
            <h3 class="text-lg sm:text-xl font-bold text-accent leading-tight">{{ format_rupiah($totOmzet) }}</h3>
        </div>
        <div class="bg-slate-900/60 border border-slate-800/80 p-4 sm:p-5 rounded-xl shadow-lg flex flex-col justify-between min-h-[110px]">
            <div class="flex justify-between items-start"><p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Total Pelanggan</p><div class="p-2 bg-indigo-500/10 text-indigo-400 rounded-lg"><i data-lucide="users-2" class="h-4.5 w-4.5"></i></div></div>
            <h3 class="text-lg sm:text-xl font-bold text-white">{{ $totCustomers }}</h3>
        </div>
    </div>

    <!-- Per-member -->
    <div class="bg-slate-900/40 border border-slate-850 rounded-2xl p-5 sm:p-6 shadow-xl space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-bold text-lg text-white">Bisnis per Member</h3>
                <p class="text-slate-400 text-xs mt-0.5">Diurutkan dari omzet tertinggi</p>
            </div>
            <a href="{{ route('members.index') }}" class="text-xs text-accent hover:text-accent/90 flex items-center gap-1 font-semibold"><span>Kelola</span><i data-lucide="arrow-right" class="h-3.5 w-3.5"></i></a>
        </div>

        @if(empty($rows))
            <p class="text-slate-500 text-sm py-6 text-center">Belum ada member. Tambahkan lewat <a href="{{ route('members.index') }}" class="text-accent font-semibold">Kelola Member</a>.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[640px]">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 text-xs font-semibold">
                            <th class="pb-3">Member</th>
                            <th class="pb-3 w-24">Status</th>
                            <th class="pb-3 w-20 text-right">Order</th>
                            <th class="pb-3 w-36 text-right">Omzet</th>
                            <th class="pb-3 w-24 text-right">Pelanggan</th>
                            <th class="pb-3 w-32">Sewa s/d</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-850">
                        @foreach($rows as $r)
                            @php $m = $r['m']; $days = $m->daysLeft(); @endphp
                            <tr class="text-sm hover:bg-slate-800/10 transition-colors">
                                <td class="py-3.5">
                                    <div class="font-bold text-white">{{ $m->username }}</div>
                                    @if($m->name)<div class="text-[11px] text-slate-500">{{ $m->name }}</div>@endif
                                </td>
                                <td class="py-3.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $r['blocked'] ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-emerald-500/10 text-emerald-450 border border-emerald-500/20' }}">{{ $r['blocked'] ? 'Nonaktif' : 'Aktif' }}</span>
                                </td>
                                <td class="py-3.5 text-right font-mono text-slate-300">{{ $r['orders'] }}</td>
                                <td class="py-3.5 text-right font-mono font-bold text-accent">{{ format_rupiah($r['omzet']) }}</td>
                                <td class="py-3.5 text-right font-mono text-slate-300">{{ $r['customers'] }}</td>
                                <td class="py-3.5 text-xs">
                                    @if($m->subscribed_until)
                                        <span class="text-slate-400">{{ format_date($m->subscribed_until) }}</span>
                                        @if($days !== null)<span class="block text-[10px] {{ $days < 0 ? 'text-rose-400' : ($days <= 7 ? 'text-amber-450' : 'text-slate-500') }}">{{ $days < 0 ? 'lewat '.abs($days).' hr' : $days.' hr lagi' }}</span>@endif
                                    @else
                                        <span class="text-slate-500">Tanpa batas</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
