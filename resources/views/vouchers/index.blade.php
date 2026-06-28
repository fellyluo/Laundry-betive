@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2"><i data-lucide="ticket-percent" class="h-8 w-8 text-accent"></i><span>Voucher &amp; Diskon</span></h1>
            <p class="text-slate-400 text-sm mt-1">Buat kode voucher (potongan nominal atau persen) untuk diterapkan saat pembayaran order.</p>
        </div>
        <button onclick="openAddVoucher()" class="flex items-center gap-2 bg-accent hover:bg-accent-hover text-white font-semibold px-5 py-3 rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 active:translate-y-0 w-full sm:w-auto justify-center">
            <i data-lucide="plus" class="h-5 w-5"></i><span>Tambah Voucher</span>
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-2 animate-in"><i data-lucide="check" class="h-5 w-5 shrink-0"></i><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-2"><i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i><span>{{ session('error') }}</span></div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-2"><i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i><span>{{ $errors->first() }}</span></div>
    @endif

    @if($vouchers->isEmpty())
        <div class="bg-slate-900/40 border border-slate-850 p-12 rounded-3xl text-center max-w-md mx-auto space-y-4">
            <div class="mx-auto w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center text-accent"><i data-lucide="ticket-percent" class="h-8 w-8"></i></div>
            <div>
                <h3 class="text-lg font-bold text-white">Belum Ada Voucher</h3>
                <p class="text-slate-400 text-sm mt-1">Buat voucher pertama Anda (misalnya HEMAT10 untuk potongan 10%) agar bisa dipakai saat pembayaran order.</p>
            </div>
            <button onclick="openAddVoucher()" class="bg-accent hover:bg-accent-hover text-white font-semibold px-4 py-2.5 rounded-xl transition-all">Tambah Voucher Sekarang</button>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($vouchers as $voucher)
                @php
                    $bisa = $voucher->bisaDipakai();
                    $sisaKuota = $voucher->kuota === null ? null : max(0, $voucher->kuota - $voucher->terpakai);
                @endphp
                <div class="bg-slate-900/60 border rounded-2xl p-6 flex flex-col justify-between transition-all duration-200 {{ $voucher->aktif ? 'border-slate-800 hover:border-slate-700' : 'border-slate-800/40 opacity-60' }}">
                    <div>
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <h3 class="font-black text-lg text-white leading-tight font-mono tracking-wide">{{ $voucher->kode }}</h3>
                                <span class="inline-block mt-1.5 text-[10px] font-bold px-2 py-0.5 rounded-md bg-accent/10 text-accent border border-accent/20">
                                    {{ $voucher->tipe === 'persen' ? 'Potongan '.$voucher->nilai.'%' : 'Potongan '.format_rupiah($voucher->nilai) }}
                                </span>
                            </div>
                            @if(! $bisa)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                    {{ ! $voucher->aktif ? 'Nonaktif' : ($voucher->kadaluarsaLewat() ? 'Kedaluwarsa' : 'Kuota Habis') }}
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Aktif</span>
                            @endif
                        </div>
                        <div class="mt-4 space-y-1.5 text-xs text-slate-400">
                            @if($voucher->min_belanja > 0)
                                <div class="flex items-center gap-2"><i data-lucide="shopping-bag" class="h-3.5 w-3.5 text-slate-500"></i>Min. belanja {{ format_rupiah($voucher->min_belanja) }}</div>
                            @endif
                            <div class="flex items-center gap-2"><i data-lucide="hash" class="h-3.5 w-3.5 text-slate-500"></i>Terpakai {{ $voucher->terpakai }}{{ $voucher->kuota !== null ? ' / '.$voucher->kuota : '' }}@if($sisaKuota !== null) <span class="text-slate-600">(sisa {{ $sisaKuota }})</span>@endif</div>
                            <div class="flex items-center gap-2"><i data-lucide="calendar" class="h-3.5 w-3.5 text-slate-500"></i>{{ $voucher->kadaluarsa ? 'Berlaku s.d. '.format_date($voucher->kadaluarsa) : 'Tanpa batas waktu' }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-800/80 mt-6 pt-4">
                        <form method="POST" action="{{ route('vouchers.toggle', $voucher) }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 text-xs font-semibold transition-colors">
                                @if($voucher->aktif)
                                    <i data-lucide="toggle-right" class="h-6 w-6 text-accent"></i><span class="text-slate-300">Aktif</span>
                                @else
                                    <i data-lucide="toggle-left" class="h-6 w-6 text-slate-500"></i><span class="text-slate-500">Nonaktif</span>
                                @endif
                            </button>
                        </form>
                        <div class="flex items-center gap-2">
                            <button onclick='openEditVoucher(@json($voucher))' class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-lg transition-colors" title="Edit Voucher"><i data-lucide="edit-3" class="h-4 w-4"></i></button>
                            <form method="POST" action="{{ route('vouchers.destroy', $voucher) }}" onsubmit="return confirm('Hapus voucher ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 bg-slate-800/50 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors" title="Hapus Voucher"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Modal -->
    <div id="voucherModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-in">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                <h2 class="text-lg font-bold text-white" id="voucherModalTitle">Tambah Voucher</h2>
                <button onclick="closeVoucherModal()" class="text-slate-400 hover:text-slate-200 transition-colors"><i data-lucide="x" class="h-5 w-5"></i></button>
            </div>
            <form id="voucherForm" method="POST" action="{{ route('vouchers.store') }}" class="p-6 space-y-4" onsubmit="return validateVoucher(event)">
                @csrf
                <input type="hidden" name="_method" id="voucherMethod" value="POST">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Kode Voucher</label>
                    <input type="text" name="kode" id="vf_kode" placeholder="Contoh: HEMAT10" oninput="this.value=this.value.toUpperCase()" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all font-mono uppercase">
                    <span class="text-xs text-rose-500 mt-1 hidden" id="verr_kode"></span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Tipe</label>
                        <select name="tipe" id="vf_tipe" onchange="onVoucherTipe()" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white focus:outline-none transition-all text-sm font-semibold">
                            <option value="nominal">Nominal (Rp)</option>
                            <option value="persen">Persen (%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2"><span id="vf_nilai_label">Nilai (Rp)</span></label>
                        <input type="number" name="nilai" id="vf_nilai" placeholder="10000" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all">
                        <span class="text-xs text-rose-500 mt-1 hidden" id="verr_nilai"></span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Min. Belanja (Rp)</label>
                        <input type="number" name="min_belanja" id="vf_min" placeholder="0 (opsional)" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Kuota</label>
                        <input type="number" name="kuota" id="vf_kuota" placeholder="Tak terbatas" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Berlaku Sampai (Opsional)</label>
                    <input type="date" name="kadaluarsa" id="vf_kadaluarsa" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white focus:outline-none transition-all text-sm">
                </div>
                <div class="flex items-center gap-3 py-1">
                    <input type="checkbox" name="aktif" id="vf_aktif" value="1" checked class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 bg-slate-950 border-slate-850">
                    <label for="vf_aktif" class="text-sm text-slate-350 select-none">Voucher aktif dan bisa dipakai</label>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" onclick="closeVoucherModal()" class="px-5 py-2.5 rounded-xl border border-slate-800 hover:border-slate-700 text-slate-300 font-semibold transition-colors w-full sm:w-auto">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-white font-semibold shadow-lg transition-colors w-full sm:w-auto">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const voucherStoreUrl = "{{ route('vouchers.store') }}";
    const voucherBaseUrl = "{{ url('vouchers') }}";

    function onVoucherTipe() {
        const tipe = document.getElementById('vf_tipe').value;
        document.getElementById('vf_nilai_label').textContent = tipe === 'persen' ? 'Nilai (%)' : 'Nilai (Rp)';
    }
    function openAddVoucher() {
        document.getElementById('voucherModalTitle').textContent = 'Tambah Voucher';
        document.getElementById('voucherForm').action = voucherStoreUrl;
        document.getElementById('voucherMethod').value = 'POST';
        document.getElementById('vf_kode').value = '';
        document.getElementById('vf_tipe').value = 'nominal';
        document.getElementById('vf_nilai').value = '';
        document.getElementById('vf_min').value = '';
        document.getElementById('vf_kuota').value = '';
        document.getElementById('vf_kadaluarsa').value = '';
        document.getElementById('vf_aktif').checked = true;
        onVoucherTipe();
        clearVoucherErrors();
        showVoucherModal();
    }
    function openEditVoucher(v) {
        document.getElementById('voucherModalTitle').textContent = 'Edit Voucher';
        document.getElementById('voucherForm').action = voucherBaseUrl + '/' + v.id;
        document.getElementById('voucherMethod').value = 'PUT';
        document.getElementById('vf_kode').value = v.kode || '';
        document.getElementById('vf_tipe').value = v.tipe || 'nominal';
        document.getElementById('vf_nilai').value = v.nilai || '';
        document.getElementById('vf_min').value = v.min_belanja || '';
        document.getElementById('vf_kuota').value = v.kuota ?? '';
        document.getElementById('vf_kadaluarsa').value = v.kadaluarsa ? String(v.kadaluarsa).substring(0, 10) : '';
        document.getElementById('vf_aktif').checked = !!v.aktif;
        onVoucherTipe();
        clearVoucherErrors();
        showVoucherModal();
    }
    function showVoucherModal(){ const m=document.getElementById('voucherModal'); m.classList.remove('hidden'); m.classList.add('flex'); }
    function closeVoucherModal(){ const m=document.getElementById('voucherModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
    function clearVoucherErrors(){ ['kode','nilai'].forEach(f=>{const e=document.getElementById('verr_'+f); e.classList.add('hidden'); e.textContent='';}); }
    function validateVoucher(e) {
        clearVoucherErrors();
        let ok = true;
        const kode = document.getElementById('vf_kode').value.trim();
        const nilai = document.getElementById('vf_nilai').value;
        const tipe = document.getElementById('vf_tipe').value;
        if (!kode) { vErr('kode','Kode voucher wajib diisi'); ok = false; }
        if (nilai === '' || Number(nilai) < 1) { vErr('nilai','Nilai harus angka positif'); ok = false; }
        else if (tipe === 'persen' && Number(nilai) > 100) { vErr('nilai','Persen maksimal 100'); ok = false; }
        if (!ok) { e.preventDefault(); return false; }
        return true;
    }
    function vErr(f,msg){ const e=document.getElementById('verr_'+f); e.textContent=msg; e.classList.remove('hidden'); }
</script>
@endpush
@endsection
