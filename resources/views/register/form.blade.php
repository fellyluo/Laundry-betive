@extends('layouts.auth')
@section('title', 'Pendaftaran Pelanggan')

@section('content')
<h2 class="text-lg font-bold text-white mb-1">Pendaftaran Pelanggan</h2>
<p class="text-slate-400 text-xs mb-5">Isi data Anda untuk menjadi member. Cepat & gratis!</p>

@if($errors->any())
    <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-xs flex items-center gap-2"><i data-lucide="alert-triangle" class="h-4 w-4 shrink-0"></i><span>{{ $errors->first() }}</span></div>
@endif

<form method="POST" action="{{ route('register.store') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Lengkap</label>
        <input type="text" name="nama" value="{{ old('nama') }}" autofocus placeholder="Nama Anda"
               class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all text-sm">
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nomor HP / WhatsApp</label>
        <input type="tel" name="no_hp" value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx"
               class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all text-sm font-mono">
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Alamat (Opsional)</label>
        <textarea name="alamat" rows="2" placeholder="Alamat untuk antar-jemput (opsional)"
                  class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all text-sm resize-none">{{ old('alamat') }}</textarea>
    </div>

    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Mau Pesan Apa? (Opsional)</label>
        <p class="text-[10px] text-slate-550 mb-2">Pilih layanan yang Anda inginkan. Berat/jumlah final ditimbang di outlet.</p>
        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
            @foreach($services as $s)
                <div class="flex items-center gap-2 bg-slate-950 border border-slate-850 rounded-xl px-3 py-2">
                    <input type="checkbox" id="chk{{ $s->id }}" onchange="toggleSvc({{ $s->id }})" class="w-4 h-4 rounded text-teal-600 bg-slate-950 border-slate-700 shrink-0 cursor-pointer">
                    <label for="chk{{ $s->id }}" class="flex-1 min-w-0 cursor-pointer select-none">
                        <span class="text-sm text-slate-200 block truncate">{{ $s->nama }}</span>
                        <span class="text-[10px] text-slate-500">{{ format_rupiah($s->tarif) }}/{{ $s->satuan }}@if($s->kategori === 'sabun') · produk @endif</span>
                    </label>
                    <input type="hidden" name="items[{{ $s->id }}][service_id]" value="{{ $s->id }}" id="sid{{ $s->id }}" disabled>
                    <input type="number" name="items[{{ $s->id }}][qty]" id="qty{{ $s->id }}" value="1" min="0.1" step="0.1" data-tarif="{{ $s->tarif }}" oninput="updateEst()" disabled
                           class="svc-qty w-16 bg-slate-950 border border-slate-800 focus:border-accent rounded-lg px-2 py-1 text-xs text-white text-center focus:outline-none hidden">
                    <span class="text-[10px] text-slate-500 shrink-0 hidden" id="unit{{ $s->id }}">{{ $s->satuan }}</span>
                </div>
            @endforeach
        </div>
        <div id="estTotal" class="hidden text-right text-xs text-slate-400 mt-2 pt-2 border-t border-slate-850">Estimasi: <span class="font-bold text-accent" id="estVal">Rp 0</span></div>
    </div>

    <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Metode Pembayaran Favorit</label>
        <select name="metode_bayar" id="regMetode" onchange="onRegMethod(this.value)"
                class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white focus:outline-none transition-all text-sm font-semibold">
            <option value="">— Pilih nanti saja —</option>
            @foreach($methods as $m)<option value="{{ $m['nama'] }}" @selected(old('metode_bayar') === $m['nama'])>{{ $m['nama'] }}</option>@endforeach
        </select>
        <div id="regMethodInfo" class="hidden mt-2"></div>
    </div>

    <button type="submit" id="submitBtn" class="w-full py-3 bg-accent hover:bg-accent-hover text-white font-bold rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 text-sm flex items-center justify-center gap-2">
        <i data-lucide="user-plus" class="h-4.5 w-4.5"></i><span>Daftar Sekarang</span>
    </button>
</form>

<p class="text-[10px] text-slate-550 text-center mt-4">Data Anda hanya digunakan untuk keperluan layanan laundry.</p>

@php
    $regMethodInfo = collect($methods)->mapWithKeys(fn($m) => [$m['nama'] => ['no_rek' => $m['no_rek'] ?? null, 'qris' => $m['qris'] ?? null]]);
@endphp
<script>
    const regMethodInfo = @json($regMethodInfo);
    function onRegMethod(nama) {
        const box = document.getElementById('regMethodInfo');
        const info = regMethodInfo[nama];
        if (!info || (!info.no_rek && !info.qris)) { box.classList.add('hidden'); box.innerHTML=''; return; }
        let html = '<div class="p-3 bg-slate-950/80 border border-slate-850 rounded-xl space-y-2">';
        html += '<div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Info Pembayaran</div>';
        if (info.no_rek) html += `<div class="text-xs text-slate-200 font-mono break-all">${escH(info.no_rek)}</div>`;
        if (info.qris) html += `<img src="${info.qris}" alt="QRIS" class="w-36 h-36 object-contain bg-white rounded-lg border border-slate-850 mx-auto">`;
        html += '</div>';
        box.innerHTML = html; box.classList.remove('hidden');
    }
    function escH(s){ return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    function toggleSvc(id) {
        const c = document.getElementById('chk'+id).checked;
        document.getElementById('sid'+id).disabled = !c;
        const q = document.getElementById('qty'+id);
        q.disabled = !c; q.classList.toggle('hidden', !c);
        document.getElementById('unit'+id).classList.toggle('hidden', !c);
        updateEst();
    }
    function updateEst() {
        let total = 0, any = false;
        document.querySelectorAll('.svc-qty').forEach(q => {
            if (!q.disabled) { total += (parseFloat(q.dataset.tarif)||0) * (parseFloat(q.value)||0); any = true; }
        });
        document.getElementById('estVal').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
        document.getElementById('estTotal').classList.toggle('hidden', !any);
        const btn = document.getElementById('submitBtn');
        if (btn) btn.querySelector('span').textContent = any ? 'Daftar & Pesan Sekarang' : 'Daftar Sekarang';
    }

    onRegMethod(document.getElementById('regMetode').value);
</script>
@endsection
