@extends('layouts.app')

@section('content')
@php
    function member_status_badge($u) {
        if ($u->isSuperAdmin()) return ['Super Admin', 'bg-accent/10 text-accent border border-accent/20'];
        if (! $u->is_active) return ['Nonaktif', 'bg-rose-500/10 text-rose-400 border border-rose-500/20'];
        if ($u->subscriptionExpired()) return ['Kedaluwarsa', 'bg-rose-500/10 text-rose-400 border border-rose-500/20'];
        return ['Aktif', 'bg-emerald-500/10 text-emerald-450 border border-emerald-500/20'];
    }
@endphp

<div class="space-y-8 pb-10">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2"><i data-lucide="shield-check" class="h-8 w-8 text-accent"></i><span>Kelola Member &amp; Langganan</span></h1>
        <p class="text-slate-400 text-sm mt-1">Panel Super Admin — pantau & kendalikan akses tiap member sesuai masa sewa/langganan.</p>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-2 animate-in"><i data-lucide="check" class="h-5 w-5 shrink-0"></i><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-2"><i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i><span>{{ session('error') }}</span></div>
    @endif

    <!-- Summary -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-slate-900/60 border border-slate-800/80 p-5 rounded-xl shadow-lg"><p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Total Member</p><h3 class="text-2xl font-black text-white mt-1">{{ $stats['total'] }}</h3></div>
        <div class="bg-slate-900/60 border border-slate-800/80 p-5 rounded-xl shadow-lg"><p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Aktif</p><h3 class="text-2xl font-black text-emerald-450 mt-1">{{ $stats['aktif'] }}</h3></div>
        <div class="bg-slate-900/60 border border-slate-800/80 p-5 rounded-xl shadow-lg"><p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Terblokir</p><h3 class="text-2xl font-black text-rose-400 mt-1">{{ $stats['blokir'] }}</h3></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <!-- Daftar member -->
        <div class="lg:col-span-2 space-y-3">
            @foreach($members as $m)
                @php
                    [$label, $cls] = member_status_badge($m);
                    $days = $m->daysLeft();
                    $emData = ['id' => $m->id, 'plan' => $m->plan, 'price' => $m->plan_price, 'until' => optional($m->subscribed_until)->format('Y-m-d'), 'active' => $m->is_active];
                @endphp
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-white">{{ $m->username }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $cls }}">{{ $label }}</span>
                                @if($m->id === auth()->id())<span class="text-[9px] text-slate-500 font-bold uppercase">(Anda)</span>@endif
                            </div>
                            @if($m->name || $m->phone)
                                <div class="text-xs text-slate-500 mt-0.5">{{ $m->name }}@if($m->name && $m->phone) · @endif@if($m->phone)<a href="https://wa.me/{{ wa_number($m->phone) }}" target="_blank" class="font-mono hover:text-accent">{{ $m->phone }}</a>@endif</div>
                            @endif
                            @unless($m->isSuperAdmin())
                            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-xs text-slate-400">
                                <span><span class="text-slate-500">Paket:</span> <b class="text-slate-300">{{ $m->plan ?: '—' }}</b>@if($m->plan_price) <span class="text-slate-500">({{ format_rupiah($m->plan_price) }})</span>@endif</span>
                                <span><span class="text-slate-500">Sewa s/d:</span> <b class="text-slate-300">{{ $m->subscribed_until ? format_date($m->subscribed_until) : 'Tanpa batas' }}</b></span>
                                @if($days !== null)
                                    <span class="{{ $days < 0 ? 'text-rose-400' : ($days <= 7 ? 'text-amber-450' : 'text-emerald-450') }} font-semibold">{{ $days < 0 ? 'Lewat '.abs($days).' hari' : $days.' hari lagi' }}</span>
                                @endif
                            </div>
                            @endunless
                        </div>
                    </div>

                    @unless($m->isSuperAdmin())
                    <div class="flex flex-wrap items-center gap-2 mt-4 pt-3 border-t border-slate-800/80">
                        <button onclick='openEditMember(@json($emData))' class="flex items-center gap-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold transition-colors"><i data-lucide="calendar-clock" class="h-3.5 w-3.5"></i>Atur Langganan</button>
                        <form method="POST" action="{{ route('members.toggle', $m) }}">@csrf
                            <button type="submit" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-colors {{ $m->is_active ? 'bg-rose-500/10 text-rose-400 hover:bg-rose-500/20' : 'bg-emerald-500/10 text-emerald-450 hover:bg-emerald-500/20' }}">
                                <i data-lucide="{{ $m->is_active ? 'pause' : 'play' }}" class="h-3.5 w-3.5"></i>{{ $m->is_active ? 'Suspend' : 'Aktifkan' }}
                            </button>
                        </form>
                        <button onclick='openMemberPass(@json($m->id), @json($m->username))' class="flex items-center gap-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold transition-colors"><i data-lucide="key-round" class="h-3.5 w-3.5"></i>Reset Password</button>
                        <form method="POST" action="{{ route('members.destroy', $m) }}" onsubmit="return confirm('Hapus member {{ $m->username }}?')">@csrf @method('DELETE')
                            <button type="submit" class="p-2 bg-slate-800/50 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors" title="Hapus"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                        </form>
                    </div>
                    @endunless
                </div>
            @endforeach
        </div>

        <!-- Tambah member -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h3 class="font-bold text-white text-base border-b border-slate-850 pb-3 flex items-center gap-2"><i data-lucide="user-plus" class="h-5 w-5 text-accent"></i><span>Tambah Member</span></h3>
            <form method="POST" action="{{ route('members.store') }}" class="space-y-3">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Nama (opsional)" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-3 py-2 text-sm text-white placeholder-slate-600 focus:outline-none transition-all">
                <input type="text" name="username" value="{{ old('username') }}" placeholder="Username *" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-3 py-2 text-sm text-white placeholder-slate-600 focus:outline-none transition-all font-mono">
                <div class="relative">
                    <input type="password" name="password" id="newMemberPass" placeholder="Password * (min. 6)" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-3 py-2 pr-10 text-sm text-white placeholder-slate-600 focus:outline-none transition-all">
                    <button type="button" onclick="togglePass('newMemberPass', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300"><i data-lucide="eye" class="h-4 w-4"></i></button>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="plan" value="{{ old('plan') }}" placeholder="Paket (cth: Pro)" class="w-full bg-slate-950 border border-slate-800 focus:border-accent rounded-xl px-3 py-2 text-sm text-white placeholder-slate-600 focus:outline-none transition-all">
                    <input type="number" name="plan_price" value="{{ old('plan_price') }}" placeholder="Harga sewa" class="w-full bg-slate-950 border border-slate-800 focus:border-accent rounded-xl px-3 py-2 text-sm text-white placeholder-slate-600 focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Sewa berlaku s/d (opsional)</label>
                    <input type="date" name="subscribed_until" value="{{ old('subscribed_until') }}" class="w-full bg-slate-950 border border-slate-800 focus:border-accent rounded-xl px-3 py-2 text-sm text-white focus:outline-none transition-all">
                </div>
                @if($errors->any())<p class="text-xs text-rose-500">{{ $errors->first() }}</p>@endif
                <button type="submit" class="w-full py-2.5 bg-accent hover:bg-accent-hover text-white font-semibold rounded-xl transition-all text-sm">Tambah Member</button>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Atur Langganan -->
<div id="editMemberModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-in">
        <div class="p-5 border-b border-slate-800 flex justify-between items-center">
            <h2 class="text-base font-bold text-white flex items-center gap-2"><i data-lucide="calendar-clock" class="h-5 w-5 text-accent"></i>Atur Langganan</h2>
            <button onclick="closeEditMember()" class="text-slate-400 hover:text-slate-200"><i data-lucide="x" class="h-5 w-5"></i></button>
        </div>
        <form id="editMemberForm" method="POST" class="p-5 space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Paket</label><input type="text" name="plan" id="em_plan" class="w-full bg-slate-950 border border-slate-800 focus:border-accent rounded-xl px-3 py-2 text-sm text-white focus:outline-none"></div>
                <div><label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Harga Sewa</label><input type="number" name="plan_price" id="em_price" class="w-full bg-slate-950 border border-slate-800 focus:border-accent rounded-xl px-3 py-2 text-sm text-white focus:outline-none"></div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Sewa berlaku sampai</label>
                <input type="date" name="subscribed_until" id="em_until" class="w-full bg-slate-950 border border-slate-800 focus:border-accent rounded-xl px-3 py-2 text-sm text-white focus:outline-none">
                <div class="flex gap-2 mt-2">
                    <button type="button" onclick="extendUntil(30)" class="text-[10px] px-2 py-1 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-300 font-semibold">+30 hari</button>
                    <button type="button" onclick="extendUntil(90)" class="text-[10px] px-2 py-1 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-300 font-semibold">+90 hari</button>
                    <button type="button" onclick="extendUntil(365)" class="text-[10px] px-2 py-1 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-300 font-semibold">+1 tahun</button>
                </div>
            </div>
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="is_active" id="em_active" value="1" class="w-4 h-4 rounded text-teal-600 bg-slate-950 border-slate-700">
                <span class="text-sm text-slate-300">Akun aktif (tidak di-suspend)</span>
            </label>
            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeEditMember()" class="px-4 py-2 rounded-xl border border-slate-800 text-slate-300 text-sm font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-accent hover:bg-accent-hover text-white rounded-xl text-sm font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reset Password Member -->
<div id="memberPassModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-sm overflow-hidden shadow-2xl animate-in">
        <div class="p-5 border-b border-slate-800 flex justify-between items-center">
            <h2 class="text-base font-bold text-white">Reset Password</h2>
            <button onclick="closeMemberPass()" class="text-slate-400 hover:text-slate-200"><i data-lucide="x" class="h-5 w-5"></i></button>
        </div>
        <form id="memberPassForm" method="POST" class="p-5 space-y-4">
            @csrf @method('PUT')
            <p class="text-xs text-slate-400">Member: <span id="mp_user" class="font-bold text-slate-200 font-mono"></span></p>
            <div class="relative">
                <input type="password" name="password" id="mp_pass" required minlength="6" placeholder="Password baru (min. 6)" class="w-full bg-slate-950 border border-slate-800 focus:border-accent rounded-xl px-4 py-2.5 pr-10 text-white placeholder-slate-600 focus:outline-none text-sm">
                <button type="button" onclick="togglePass('mp_pass', this)" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-500 hover:text-slate-300"><i data-lucide="eye" class="h-4 w-4"></i></button>
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeMemberPass()" class="px-4 py-2 rounded-xl border border-slate-800 text-slate-300 text-sm font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-accent hover:bg-accent-hover text-white rounded-xl text-sm font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const memberBase = "{{ url('members') }}";
    function openEditMember(m) {
        document.getElementById('editMemberForm').action = memberBase + '/' + m.id;
        document.getElementById('em_plan').value = m.plan || '';
        document.getElementById('em_price').value = m.price || '';
        document.getElementById('em_until').value = m.until || '';
        document.getElementById('em_active').checked = !!m.active;
        const md = document.getElementById('editMemberModal'); md.classList.remove('hidden'); md.classList.add('flex');
    }
    function closeEditMember(){ const m=document.getElementById('editMemberModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
    function extendUntil(days) {
        const el = document.getElementById('em_until');
        let base = el.value ? new Date(el.value) : new Date();
        if (base < new Date()) base = new Date();
        base.setDate(base.getDate() + days);
        el.value = base.toISOString().slice(0,10);
    }
    function openMemberPass(id, username) {
        document.getElementById('memberPassForm').action = memberBase + '/' + id + '/password';
        document.getElementById('mp_user').textContent = username;
        const m = document.getElementById('memberPassModal'); m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeMemberPass(){ const m=document.getElementById('memberPassModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
    function togglePass(id, btn) {
        const inp = document.getElementById(id); const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        btn.innerHTML = show ? '<i data-lucide="eye-off" class="h-4 w-4"></i>' : '<i data-lucide="eye" class="h-4 w-4"></i>';
        if (window.lucide) lucide.createIcons();
    }
</script>
@endpush
@endsection
