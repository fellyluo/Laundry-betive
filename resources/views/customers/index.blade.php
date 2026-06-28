@extends('layouts.app')

@section('content')
<div class="space-y-8" x-data>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2"><i data-lucide="users-2" class="h-8 w-8 text-accent"></i><span>Master Pelanggan</span></h1>
            <p class="text-slate-400 text-sm mt-1">Kelola data member laundry, riwayat poin, nomor kontak WhatsApp, dan alamat penjemputan.</p>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button onclick="openQrModal()" class="flex items-center gap-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold px-4 py-3 rounded-xl transition-all border border-slate-750/30 flex-1 sm:flex-initial justify-center" title="QR untuk pendaftaran pelanggan">
                <i data-lucide="qr-code" class="h-5 w-5 text-accent"></i><span>QR Daftar</span>
            </button>
            <button onclick="openAddCustomer()" class="flex items-center gap-2 bg-accent hover:bg-accent-hover text-white font-semibold px-5 py-3 rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 active:translate-y-0 flex-1 sm:flex-initial justify-center">
                <i data-lucide="plus" class="h-5 w-5"></i><span>Tambah Pelanggan</span>
            </button>
        </div>
    </div>

    @if(session('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-2"><i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i><span>{{ session('error') }}</span></div>
    @endif

    <!-- Search (server-side) -->
    <form method="GET" action="{{ route('customers.index') }}" class="relative w-full max-w-md">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500"><i data-lucide="search" class="h-5 w-5"></i></span>
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama, nomor HP, alamat... lalu tekan Enter"
               class="w-full bg-slate-900 border border-slate-800 hover:border-slate-750 focus:border-accent focus:outline-none rounded-xl pl-11 pr-10 py-3 text-slate-100 placeholder-slate-550 transition-all text-sm shadow-inner">
        @if($q !== '')
            <a href="{{ route('customers.index') }}" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-500 hover:text-slate-300" title="Hapus pencarian"><i data-lucide="x" class="h-4 w-4"></i></a>
        @endif
    </form>

    <!-- Grid -->
    @if($customers->isEmpty())
        <div class="bg-slate-900/40 border border-slate-850 p-12 rounded-3xl text-center max-w-md mx-auto space-y-4">
            <div class="mx-auto w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center text-accent"><i data-lucide="users-2" class="h-8 w-8"></i></div>
            <div>
                <h3 class="text-lg font-bold text-white">Pelanggan Tidak Ditemukan</h3>
                @if($q !== '')
                    <p class="text-slate-400 text-sm mt-1">Tidak ada pelanggan yang cocok dengan kata kunci "<span class="text-slate-200 font-semibold">{{ $q }}</span>".</p>
                @else
                    <p class="text-slate-400 text-sm mt-1">Belum ada pelanggan terdaftar di sistem. Tambahkan pelanggan untuk membuat order.</p>
                @endif
            </div>
            @if($q !== '')
                <a href="{{ route('customers.index') }}" class="inline-block bg-slate-800 hover:bg-slate-700 text-white font-semibold px-4 py-2.5 rounded-xl transition-all">Reset Pencarian</a>
            @else
                <button onclick="openAddCustomer()" class="bg-accent hover:bg-accent-hover text-white font-semibold px-4 py-2.5 rounded-xl transition-all">Tambah Pelanggan Sekarang</button>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="customerGrid">
            @foreach($customers as $customer)
                <div class="customer-card bg-slate-900/60 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between hover:border-slate-700 transition-all duration-200 shadow-xl group"
                     data-search="{{ strtolower($customer->nama.' '.$customer->no_hp.' '.$customer->alamat) }}">
                    <div class="space-y-4">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <h3 class="font-bold text-lg text-white group-hover:text-accent transition-colors leading-snug">{{ $customer->nama }}</h3>
                                <span class="text-[10px] text-slate-500 block mt-0.5">Terdaftar: {{ format_date($customer->created_at) }}</span>
                            </div>
                            <span class="flex items-center gap-1.5 px-3 py-1 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded-full text-xs font-bold shadow-inner"><i data-lucide="award" class="h-3.5 w-3.5"></i><span>{{ $customer->poin }} Poin</span></span>
                        </div>
                        <div class="space-y-2 text-sm text-slate-405">
                            <div class="flex items-center gap-2">
                                <i data-lucide="phone" class="h-4 w-4 text-slate-500"></i>
                                <a href="https://wa.me/{{ wa_number($customer->no_hp) }}" target="_blank" rel="noopener noreferrer" class="hover:underline hover:text-accent font-mono">{{ $customer->no_hp }}</a>
                            </div>
                            <div class="flex items-start gap-2">
                                <i data-lucide="map-pin" class="h-4 w-4 text-slate-500 mt-0.5 shrink-0"></i>
                                <span class="line-clamp-2">{{ $customer->alamat ?: '' }}@unless($customer->alamat)<span class="text-slate-600 italic">Alamat belum diatur</span>@endunless</span>
                            </div>
                            @if($customer->metode_bayar)
                            <div class="flex items-center gap-2">
                                <i data-lucide="credit-card" class="h-4 w-4 text-slate-500"></i>
                                <span class="text-xs">Bayar favorit: <span class="font-semibold text-slate-300">{{ $customer->metode_bayar }}</span></span>
                            </div>
                            @endif
                        </div>
                        @if($customer->via_qr)
                            <span class="inline-flex items-center gap-1 mt-3 px-2 py-0.5 bg-accent/10 text-accent border border-accent/20 rounded-full text-[10px] font-bold"><i data-lucide="qr-code" class="h-3 w-3"></i>Daftar via QR</span>
                        @endif
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-800/80 mt-6 pt-4">
                        <a href="{{ route('customers.points', $customer) }}" class="p-2 bg-slate-800 hover:bg-amber-500/15 text-slate-200 hover:text-amber-400 rounded-lg transition-colors flex items-center gap-1.5 text-xs font-semibold px-3" title="Riwayat Poin"><i data-lucide="award" class="h-3.5 w-3.5"></i><span>Poin</span></a>
                        <button onclick='openEditCustomer(@json($customer))' class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-lg transition-colors flex items-center gap-1.5 text-xs font-semibold px-3"><i data-lucide="edit-3" class="h-3.5 w-3.5"></i><span>Edit</span></button>
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pelanggan ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 bg-slate-800/50 hover:bg-rose-500/25 text-slate-400 hover:text-rose-450 rounded-lg transition-colors flex items-center justify-center" title="Hapus Pelanggan"><i data-lucide="trash-2" class="h-3.5 w-3.5"></i></button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $customers->links() }}</div>
    @endif

    <!-- Modal Add/Edit -->
    <div id="customerModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-in">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                <h2 class="text-lg font-bold text-white" id="customerModalTitle">Tambah Pelanggan Baru</h2>
                <button onclick="closeCustomerModal()" class="text-slate-400 hover:text-slate-200 transition-colors"><i data-lucide="x" class="h-5 w-5"></i></button>
            </div>
            <form id="customerForm" method="POST" action="{{ route('customers.store') }}" class="p-6 space-y-4" onsubmit="return validateCustomer(event)">
                @csrf
                <input type="hidden" name="_method" id="customerMethod" value="POST">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Lengkap</label>
                    <input type="text" name="nama" id="cf_nama" placeholder="Contoh: Rian Hidayat" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all">
                    <span class="text-xs text-rose-500 mt-1 hidden" id="err_nama"></span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nomor HP / WhatsApp</label>
                    <input type="text" name="no_hp" id="cf_no_hp" placeholder="Contoh: 08123456789" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none font-mono transition-all">
                    <span class="text-xs text-rose-500 mt-1 hidden" id="err_no_hp"></span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Alamat Rumah (Opsional)</label>
                    <textarea name="alamat" id="cf_alamat" rows="3" placeholder="Contoh: Jalan Anggrek No. 4, Kecamatan Menteng..." class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all resize-none text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" onclick="closeCustomerModal()" class="px-5 py-2.5 rounded-xl border border-slate-800 hover:border-slate-700 text-slate-300 font-semibold transition-colors w-full sm:w-auto">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-white font-semibold shadow-lg transition-colors w-full sm:w-auto">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal QR Pendaftaran -->
<div id="qrModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-sm overflow-hidden shadow-2xl animate-in">
        <div class="p-5 border-b border-slate-800 flex justify-between items-center">
            <h2 class="text-base font-bold text-white flex items-center gap-2"><i data-lucide="qr-code" class="h-5 w-5 text-accent"></i><span>QR Pendaftaran Pelanggan</span></h2>
            <button type="button" onclick="closeQrModal()" class="text-slate-400 hover:text-slate-200"><i data-lucide="x" class="h-5 w-5"></i></button>
        </div>
        <div class="p-5 space-y-4 text-center">
            <p class="text-xs text-slate-400">Minta pelanggan <b>scan QR</b> ini untuk mengisi data sendiri. Datanya langsung masuk ke Master Pelanggan.</p>
            <div id="qrBox" class="bg-white p-3 rounded-xl inline-block mx-auto"></div>
            <div class="bg-slate-950/60 border border-slate-850 rounded-xl p-2.5 flex items-center gap-2">
                <span id="qrUrl" class="text-[11px] text-slate-300 font-mono truncate flex-1 text-left"></span>
                <button type="button" onclick="copyQrUrl()" class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg shrink-0" title="Salin link"><i data-lucide="copy" class="h-4 w-4"></i></button>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="printQr()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold rounded-xl text-sm flex items-center justify-center gap-2"><i data-lucide="printer" class="h-4 w-4"></i><span>Cetak</span></button>
                <button type="button" onclick="closeQrModal()" class="flex-1 py-2.5 bg-accent hover:bg-accent-hover text-white font-semibold rounded-xl text-sm">Tutup</button>
            </div>
            <p class="text-[10px] text-slate-550 flex items-start gap-1.5 text-left"><i data-lucide="info" class="h-3.5 w-3.5 shrink-0 mt-0.5"></i><span>Agar bisa discan dari HP pelanggan, aplikasi harus diakses lewat alamat jaringan/domain (bukan localhost). QR mengikuti alamat yang sedang Anda buka.</span></p>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    let _qrObj = null;
    function regUrl() { return window.location.origin + '/daftar/' + {{ auth()->id() }}; }
    function openQrModal() {
        const url = regUrl();
        const box = document.getElementById('qrBox');
        box.innerHTML = '';
        _qrObj = new QRCode(box, { text: url, width: 220, height: 220, colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
        document.getElementById('qrUrl').textContent = url;
        const m = document.getElementById('qrModal'); m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeQrModal(){ const m=document.getElementById('qrModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
    function copyQrUrl(){ navigator.clipboard?.writeText(regUrl()).then(()=>{}, ()=>{}); }
    function qrImageSrc() {
        const box = document.getElementById('qrBox');
        const img = box.querySelector('img'); const cv = box.querySelector('canvas');
        return img && img.src ? img.src : (cv ? cv.toDataURL('image/png') : '');
    }
    function printQr() {
        const src = qrImageSrc(); if (!src) return;
        const w = window.open('', '_blank', 'width=420,height=560');
        w.document.write(`<html><head><title>QR Pendaftaran</title></head><body style="font-family:sans-serif;text-align:center;padding:24px;">
            <h2 style="margin:0 0 4px;">{{ $appSettings['branding']['nama_laundry'] ?? 'LaundryPro' }}</h2>
            <p style="margin:0 0 16px;color:#555;">Scan untuk daftar jadi member</p>
            <img src="${src}" style="width:260px;height:260px;"/>
            <p style="margin-top:12px;font-size:12px;color:#777;word-break:break-all;">${regUrl()}</p>
            </body></html>`);
        w.document.close(); w.focus();
        setTimeout(() => { w.print(); }, 300);
    }
</script>
@endpush

@push('scripts')
<script>
    const customerStoreUrl = "{{ route('customers.store') }}";
    const customerBaseUrl = "{{ url('customers') }}";

    function openAddCustomer() {
        document.getElementById('customerModalTitle').textContent = 'Tambah Pelanggan Baru';
        document.getElementById('customerForm').action = customerStoreUrl;
        document.getElementById('customerMethod').value = 'POST';
        document.getElementById('cf_nama').value = '';
        document.getElementById('cf_no_hp').value = '';
        document.getElementById('cf_alamat').value = '';
        clearCustomerErrors();
        showCustomerModal();
    }
    function openEditCustomer(c) {
        document.getElementById('customerModalTitle').textContent = 'Edit Profil Pelanggan';
        document.getElementById('customerForm').action = customerBaseUrl + '/' + c.id;
        document.getElementById('customerMethod').value = 'PUT';
        document.getElementById('cf_nama').value = c.nama || '';
        document.getElementById('cf_no_hp').value = c.no_hp || '';
        document.getElementById('cf_alamat').value = c.alamat || '';
        clearCustomerErrors();
        showCustomerModal();
    }
    function showCustomerModal() {
        const m = document.getElementById('customerModal');
        m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeCustomerModal() {
        const m = document.getElementById('customerModal');
        m.classList.add('hidden'); m.classList.remove('flex');
    }
    function clearCustomerErrors() {
        ['nama','no_hp'].forEach(f => { const e = document.getElementById('err_'+f); e.classList.add('hidden'); e.textContent=''; });
    }
    function validateCustomer(e) {
        clearCustomerErrors();
        let ok = true;
        const nama = document.getElementById('cf_nama').value.trim();
        const hp = document.getElementById('cf_no_hp').value.trim();
        if (!nama) { setErr('nama','Nama pelanggan wajib diisi'); ok = false; }
        const clean = hp.replace(/[^0-9]/g,'');
        if (!hp) { setErr('no_hp','Nomor HP wajib diisi'); ok = false; }
        else if (clean.length < 8) { setErr('no_hp','Nomor HP tidak valid (minimal 8 angka)'); ok = false; }
        if (!ok) { e.preventDefault(); return false; }
        return true;
    }
    function setErr(f, msg) { const e = document.getElementById('err_'+f); e.textContent = msg; e.classList.remove('hidden'); }
</script>
@endpush
@endsection
