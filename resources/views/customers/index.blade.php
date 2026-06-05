@extends('layouts.app')

@section('content')
<div class="space-y-8" x-data>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2"><i data-lucide="users-2" class="h-8 w-8 text-accent"></i><span>Master Pelanggan</span></h1>
            <p class="text-slate-400 text-sm mt-1">Kelola data member laundry, riwayat poin, nomor kontak WhatsApp, dan alamat penjemputan.</p>
        </div>
        <button onclick="openAddCustomer()" class="flex items-center gap-2 bg-accent hover:bg-accent-hover text-white font-semibold px-5 py-3 rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 active:translate-y-0 w-full sm:w-auto justify-center">
            <i data-lucide="plus" class="h-5 w-5"></i><span>Tambah Pelanggan</span>
        </button>
    </div>

    @if(session('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-2"><i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i><span>{{ session('error') }}</span></div>
    @endif

    <!-- Search -->
    <div class="relative w-full max-w-md">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500"><i data-lucide="search" class="h-5 w-5"></i></span>
        <input type="text" id="customerSearch" oninput="filterCustomers()" placeholder="Cari pelanggan berdasarkan nama, nomor HP..."
               class="w-full bg-slate-900 border border-slate-800 hover:border-slate-750 focus:border-accent focus:outline-none rounded-xl pl-11 pr-4 py-3 text-slate-100 placeholder-slate-550 transition-all text-sm shadow-inner">
    </div>

    <!-- Grid -->
    @if($customers->isEmpty())
        <div class="bg-slate-900/40 border border-slate-850 p-12 rounded-3xl text-center max-w-md mx-auto space-y-4">
            <div class="mx-auto w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center text-accent"><i data-lucide="users-2" class="h-8 w-8"></i></div>
            <div>
                <h3 class="text-lg font-bold text-white">Pelanggan Tidak Ditemukan</h3>
                <p class="text-slate-400 text-sm mt-1">Belum ada pelanggan terdaftar di sistem. Tambahkan pelanggan untuk membuat order.</p>
            </div>
            <button onclick="openAddCustomer()" class="bg-accent hover:bg-accent-hover text-white font-semibold px-4 py-2.5 rounded-xl transition-all">Tambah Pelanggan Sekarang</button>
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
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-800/80 mt-6 pt-4">
                        <button onclick='openEditCustomer(@json($customer))' class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-lg transition-colors flex items-center gap-1.5 text-xs font-semibold px-3"><i data-lucide="edit-3" class="h-3.5 w-3.5"></i><span>Edit</span></button>
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pelanggan ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 bg-slate-800/50 hover:bg-rose-500/25 text-slate-400 hover:text-rose-450 rounded-lg transition-colors flex items-center justify-center" title="Hapus Pelanggan"><i data-lucide="trash-2" class="h-3.5 w-3.5"></i></button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div id="customerEmpty" class="hidden bg-slate-900/40 border border-slate-850 p-10 rounded-3xl text-center max-w-md mx-auto">
            <p class="text-slate-400 text-sm">Tidak ada pelanggan yang cocok dengan kata kunci pencarian Anda.</p>
        </div>
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

    function filterCustomers() {
        const q = document.getElementById('customerSearch').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.customer-card');
        let visible = 0;
        cards.forEach(c => {
            const match = !q || c.dataset.search.includes(q);
            c.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        const empty = document.getElementById('customerEmpty');
        if (empty) empty.classList.toggle('hidden', visible !== 0);
    }
</script>
@endpush
@endsection
