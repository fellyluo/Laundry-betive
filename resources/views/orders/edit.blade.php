@extends('layouts.app')

@section('content')
@php
    $estimasiVal = optional($order->estimasi_selesai)->format('Y-m-d\TH:i');
    $custArr = $customers->map(fn($c) => ['id' => $c->id, 'nama' => $c->nama, 'no_hp' => $c->no_hp, 'alamat' => $c->alamat, 'poin' => $c->poin])->values();
    $svcArr = $services->map(fn($s) => ['id' => $s->id, 'nama' => $s->nama, 'tarif' => $s->tarif, 'satuan' => $s->satuan])->values();
    $itemsArr = $order->items->map(fn($i) => ['service_id' => $i->service_id, 'qty' => (float) $i->qty])->values();
    $selCust = $order->customer ? ['id' => $order->customer->id, 'nama' => $order->customer->nama, 'no_hp' => $order->customer->no_hp, 'alamat' => $order->customer->alamat, 'poin' => $order->customer->poin] : null;
@endphp

<div class="space-y-8 pb-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2"><i data-lucide="pencil" class="h-8 w-8 text-accent"></i><span>Edit Order</span></h1>
            <p class="text-slate-400 text-sm mt-1">Ubah pelanggan, layanan, estimasi, atau catatan untuk nota <span class="font-mono font-bold text-slate-300">{{ $order->nomor_nota }}</span>.</p>
        </div>
        <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-200 transition-colors text-sm font-semibold"><i data-lucide="arrow-left" class="h-4 w-4"></i><span>Kembali ke Detail</span></a>
    </div>

    <div id="formError" class="hidden p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl items-center gap-2 max-w-3xl">
        <i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i><span id="formErrorMsg"></span>
    </div>

    <form id="orderForm" method="POST" action="{{ route('orders.update', $order) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="customer_id" id="customer_id" value="{{ $order->customer_id }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Customer -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-white flex items-center gap-2"><span class="w-6 h-6 bg-accent/10 text-accent rounded-full flex items-center justify-center text-xs font-bold">1</span><span>Detail Pelanggan</span></h3>
                        <button type="button" onclick="openQuickCustomer()" class="flex items-center gap-1.5 text-xs text-accent hover:text-accent/90 transition-colors font-semibold"><i data-lucide="user-plus" class="h-4 w-4"></i><span>Tambah Baru</span></button>
                    </div>
                    <div class="relative">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500"><i data-lucide="search" class="h-4 w-4"></i></span>
                            <input type="text" id="customerSearch" autocomplete="off" value="{{ $order->customer->nama ?? '' }}" oninput="onCustomerInput()" onfocus="showCustomerDropdown()" placeholder="Ketik nama atau nomor HP pelanggan..." class="w-full bg-slate-950 border border-slate-850 hover:border-slate-800 focus:border-accent rounded-xl pl-10 pr-10 py-2.5 text-white focus:outline-none transition-all text-sm">
                            <button type="button" id="clearCustomerBtn" onclick="clearCustomer()" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-500 hover:text-slate-350 {{ $order->customer ? '' : 'hidden' }}"><i data-lucide="x" class="h-4 w-4"></i></button>
                        </div>
                        <div id="customerDropdown" class="absolute left-0 right-0 mt-1.5 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl max-h-52 overflow-y-auto z-40 divide-y divide-slate-850 hidden"></div>
                    </div>
                    <div id="customerDetail" class="p-3 bg-slate-950/80 border border-slate-850 rounded-xl text-xs space-y-1 text-slate-400 hidden"></div>
                </div>

                <!-- 2. Items -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <h3 class="font-bold text-white flex items-center gap-2"><span class="w-6 h-6 bg-accent/10 text-accent rounded-full flex items-center justify-center text-xs font-bold">2</span><span>Rincian Layanan Laundry</span></h3>
                    <div class="space-y-4" id="itemRows"></div>
                    <button type="button" onclick="addItemRow()" class="flex items-center justify-center gap-2 border border-dashed border-slate-800 hover:border-slate-700 text-slate-400 hover:text-slate-200 text-sm font-semibold py-3 w-full rounded-xl transition-all"><i data-lucide="plus-circle" class="h-4 w-4"></i><span>Tambah Layanan / Item Lain</span></button>
                </div>

                <!-- 3. Details -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <h3 class="font-bold text-white flex items-center gap-2"><span class="w-6 h-6 bg-accent/10 text-accent rounded-full flex items-center justify-center text-xs font-bold">3</span><span>Detail Pengiriman &amp; Catatan</span></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5"><i data-lucide="calendar" class="h-4 w-4 text-accent"></i><span>Estimasi Waktu Selesai</span></label>
                            <input type="datetime-local" name="estimasi_selesai" value="{{ $estimasiVal }}" class="w-full bg-slate-950 border border-slate-850 hover:border-slate-800 focus:border-accent focus:outline-none rounded-xl px-4 py-2.5 text-white transition-all text-sm font-semibold">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5"><i data-lucide="file-text" class="h-4 w-4 text-accent"></i><span>Catatan Khusus (Opsional)</span></label>
                            <input type="text" name="catatan" value="{{ $order->catatan }}" placeholder="Contoh: baju putih dipisah, setrika licin..." class="w-full bg-slate-950 border border-slate-850 hover:border-slate-800 focus:border-accent focus:outline-none rounded-xl px-4 py-2.5 text-white transition-all text-sm">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6 sticky top-6">
                    <h3 class="font-bold text-white border-b border-slate-850 pb-3">Ringkasan Order</h3>
                    <div class="space-y-2 max-h-44 overflow-y-auto pr-1" id="summaryList"></div>
                    <div class="flex justify-between items-end border-t border-slate-850 pt-4">
                        <span class="text-sm font-semibold text-slate-450">Total Tagihan</span>
                        <span class="text-2xl font-black text-accent" id="totalDisplay">Rp 0</span>
                    </div>
                    <div class="text-[11px] text-slate-500 bg-slate-950/40 border border-slate-850 rounded-xl p-3 flex items-start gap-2">
                        <i data-lucide="info" class="h-3.5 w-3.5 shrink-0 mt-0.5 text-slate-400"></i>
                        <span>Pembayaran tidak diubah di sini. Status bayar dihitung ulang otomatis dari total baru. Kelola pembayaran di halaman detail.</span>
                    </div>
                    <div class="pt-4 border-t border-slate-850 space-y-3">
                        <button type="button" onclick="submitOrder()" class="w-full py-3 bg-accent hover:bg-accent-hover text-white font-bold rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 active:translate-y-0 text-sm">Simpan Perubahan</button>
                        <a href="{{ route('orders.show', $order) }}" class="block text-center w-full py-3 border border-slate-800 hover:border-slate-700 text-slate-400 hover:text-slate-300 font-semibold rounded-xl transition-colors text-sm">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Quick add customer modal -->
    <div id="quickModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-in">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                <h2 class="text-lg font-bold text-white flex items-center gap-2"><i data-lucide="user-plus" class="h-5 w-5 text-accent"></i><span>Tambah Pelanggan Cepat</span></h2>
                <button type="button" onclick="closeQuickCustomer()" class="text-slate-400 hover:text-slate-200 transition-colors"><i data-lucide="x" class="h-5 w-5"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Lengkap</label>
                    <input type="text" id="qc_nama" placeholder="Contoh: Andi Wijaya" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-650 focus:outline-none text-sm transition-all">
                    <span class="text-xs text-rose-500 mt-1 hidden" id="qerr_nama"></span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nomor HP / WhatsApp</label>
                    <input type="text" id="qc_no_hp" placeholder="Contoh: 0812XXXXXXXX" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-650 focus:outline-none font-mono text-sm transition-all">
                    <span class="text-xs text-rose-500 mt-1 hidden" id="qerr_no_hp"></span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Alamat Rumah (Opsional)</label>
                    <textarea id="qc_alamat" rows="2" placeholder="Masukkan alamat rumah..." class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2 text-white placeholder-slate-650 focus:outline-none text-sm transition-all resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" onclick="closeQuickCustomer()" class="px-4 py-2 rounded-xl border border-slate-800 text-slate-350 hover:border-slate-700 text-sm font-semibold transition-colors">Batal</button>
                    <button type="button" onclick="submitQuickCustomer()" class="px-5 py-2 bg-accent hover:bg-accent-hover text-white rounded-xl text-sm font-semibold shadow-lg transition-colors">Tambah Pelanggan</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let customers = @json($custArr);
    const services = @json($svcArr);
    const initialItems = @json($itemsArr);
    const initialCustomer = @json($selCust);
    const quickStoreUrl = "{{ route('customers.store') }}";

    function rupiah(v) { return 'Rp ' + Math.round(v).toLocaleString('id-ID'); }
    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

    // ---------- Customer autocomplete ----------
    function onCustomerInput() {
        document.getElementById('customer_id').value = '';
        document.getElementById('customerDetail').classList.add('hidden');
        renderCustomerDropdown(); showCustomerDropdown(); toggleClearBtn();
    }
    function toggleClearBtn() { document.getElementById('clearCustomerBtn').classList.toggle('hidden', document.getElementById('customerSearch').value.length === 0); }
    function showCustomerDropdown() { renderCustomerDropdown(); document.getElementById('customerDropdown').classList.remove('hidden'); }
    function hideCustomerDropdown() { document.getElementById('customerDropdown').classList.add('hidden'); }
    function renderCustomerDropdown() {
        const q = document.getElementById('customerSearch').value.toLowerCase();
        const dd = document.getElementById('customerDropdown');
        const filtered = customers.filter(c => c.nama.toLowerCase().includes(q) || (c.no_hp||'').includes(q));
        dd.innerHTML = filtered.length === 0
            ? '<div class="p-4 text-center text-slate-500 text-xs">Pelanggan tidak ditemukan. Silakan tambahkan baru.</div>'
            : filtered.map(c => `<button type="button" onclick='selectCustomer(${JSON.stringify(c)})' class="w-full text-left px-4 py-3 hover:bg-accent/10 hover:text-accent transition-colors flex justify-between items-center text-sm"><div><span class="font-semibold text-slate-200">${esc(c.nama)}</span><span class="text-xs text-slate-500 ml-2">(${esc(c.no_hp)})</span></div>${c.alamat ? `<span class="text-xs text-slate-500 truncate max-w-xs">${esc(c.alamat)}</span>` : ''}</button>`).join('');
    }
    function selectCustomer(c) {
        document.getElementById('customer_id').value = c.id;
        document.getElementById('customerSearch').value = c.nama;
        hideCustomerDropdown(); toggleClearBtn();
        const d = document.getElementById('customerDetail');
        d.innerHTML = `<div><span class="font-bold text-slate-300">HP:</span> ${esc(c.no_hp)}</div>` + (c.alamat ? `<div><span class="font-bold text-slate-300">Alamat:</span> ${esc(c.alamat)}</div>` : '') + `<div><span class="font-bold text-slate-300">Poin Saat Ini:</span> ${c.poin||0} Poin</div>`;
        d.classList.remove('hidden');
    }
    function clearCustomer() {
        document.getElementById('customerSearch').value = '';
        document.getElementById('customer_id').value = '';
        document.getElementById('customerDetail').classList.add('hidden');
        hideCustomerDropdown(); toggleClearBtn();
    }
    document.addEventListener('click', e => { if (!e.target.closest('#customerDropdown') && !e.target.closest('#customerSearch')) hideCustomerDropdown(); });

    // ---------- Item rows ----------
    let rowIdx = 0;
    function serviceOptions(selectedId) {
        let html = '<option value="">-- Pilih Layanan --</option>';
        services.forEach(s => { html += `<option value="${s.id}" ${s.id == selectedId ? 'selected' : ''}>${esc(s.nama)} (${rupiah(s.tarif)}/${s.satuan})</option>`; });
        return html;
    }
    function addItemRow(serviceId = '', qty = 1) {
        const i = rowIdx++;
        const wrap = document.createElement('div');
        wrap.className = 'flex flex-col sm:flex-row gap-3 items-start sm:items-center bg-slate-950/40 p-4 rounded-xl border border-slate-850 item-row';
        wrap.innerHTML = `
            <div class="flex-1 w-full">
                <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5 sm:hidden">Layanan</label>
                <select onchange="recalc()" class="row-service w-full bg-slate-950 border border-slate-800 focus:border-accent focus:outline-none rounded-lg px-3 py-2 text-sm text-white transition-all">${serviceOptions(serviceId)}</select>
            </div>
            <div class="w-full sm:w-28 flex items-center gap-2">
                <div class="flex-1">
                    <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5 sm:hidden">Jumlah / Berat</label>
                    <input type="number" step="0.01" value="${qty}" oninput="recalc()" placeholder="Berat/Qty" class="row-qty w-full bg-slate-950 border border-slate-800 focus:border-accent focus:outline-none rounded-lg px-3 py-2 text-sm text-white text-center font-semibold">
                </div>
                <span class="text-slate-500 text-xs shrink-0 self-end mb-2.5 row-unit"></span>
            </div>
            <div class="w-full sm:w-32 text-left sm:text-right font-bold text-accent shrink-0 text-sm">
                <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1 sm:hidden">Subtotal</label>
                <span class="row-subtotal">Rp 0</span>
            </div>
            <button type="button" onclick="removeItemRow(this)" class="p-2 text-slate-500 hover:text-rose-450 hover:bg-rose-500/10 rounded-lg transition-all self-end sm:self-center"><i data-lucide="trash-2" class="h-4.5 w-4.5"></i></button>`;
        document.getElementById('itemRows').appendChild(wrap);
        renderIcons(); recalc();
    }
    function removeItemRow(btn) {
        if (document.querySelectorAll('.item-row').length === 1) return;
        btn.closest('.item-row').remove(); recalc();
    }
    function recalc() {
        let total = 0; const summary = [];
        document.querySelectorAll('.item-row').forEach((row, idx) => {
            const sid = row.querySelector('.row-service').value;
            const qty = parseFloat(row.querySelector('.row-qty').value) || 0;
            const svc = services.find(s => s.id == sid);
            const unitEl = row.querySelector('.row-unit'), subEl = row.querySelector('.row-subtotal');
            // rename inputs sequentially so PHP receives a clean array
            row.querySelector('.row-service').name = `items[${idx}][service_id]`;
            row.querySelector('.row-qty').name = `items[${idx}][qty]`;
            if (svc && qty > 0) {
                const sub = Math.round(qty * svc.tarif); total += sub;
                unitEl.textContent = svc.satuan; subEl.textContent = rupiah(sub);
                summary.push(`<div class="flex justify-between text-xs text-slate-400"><span>${esc(svc.nama)} (${qty} ${svc.satuan})</span><span class="font-semibold">${rupiah(sub)}</span></div>`);
            } else { unitEl.textContent = svc ? svc.satuan : ''; subEl.textContent = 'Rp 0'; }
        });
        document.getElementById('totalDisplay').textContent = rupiah(total);
        document.getElementById('summaryList').innerHTML = summary.join('');
    }

    // ---------- Submit ----------
    function submitOrder() {
        hideFormError();
        if (!document.getElementById('customer_id').value) { showFormError('Silakan pilih pelanggan terlebih dahulu.'); return; }
        let valid = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            if (row.querySelector('.row-service').value && (parseFloat(row.querySelector('.row-qty').value) || 0) > 0) valid++;
        });
        if (valid === 0) { showFormError('Silakan tambahkan minimal 1 layanan dengan kuantitas yang valid.'); return; }
        if (!document.querySelector('[name="estimasi_selesai"]').value) { showFormError('Silakan tentukan estimasi selesai laundry.'); return; }
        document.getElementById('orderForm').submit();
    }
    function showFormError(msg) { document.getElementById('formErrorMsg').textContent = msg; const e = document.getElementById('formError'); e.classList.remove('hidden'); e.classList.add('flex'); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    function hideFormError() { const e = document.getElementById('formError'); e.classList.add('hidden'); e.classList.remove('flex'); }

    // ---------- Quick customer ----------
    function openQuickCustomer(){ const m=document.getElementById('quickModal'); m.classList.remove('hidden'); m.classList.add('flex'); }
    function closeQuickCustomer(){ const m=document.getElementById('quickModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
    function submitQuickCustomer() {
        ['nama','no_hp'].forEach(f=>document.getElementById('qerr_'+f).classList.add('hidden'));
        const nama=document.getElementById('qc_nama').value.trim(), hp=document.getElementById('qc_no_hp').value.trim(), alamat=document.getElementById('qc_alamat').value.trim();
        let ok=true;
        if(!nama){ qErr('nama','Nama pelanggan wajib diisi'); ok=false; }
        const clean=hp.replace(/[^0-9]/g,'');
        if(!hp){ qErr('no_hp','Nomor HP wajib diisi'); ok=false; } else if(clean.length<8){ qErr('no_hp','Nomor HP tidak valid (minimal 8 angka)'); ok=false; }
        if(!ok) return;
        fetch(quickStoreUrl, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':window.CSRF_TOKEN,'X-Requested-With':'XMLHttpRequest'}, body: JSON.stringify({nama,no_hp:hp,alamat}) })
            .then(r=>r.json()).then(c=>{
                customers.unshift({id:c.id,nama:c.nama,no_hp:c.no_hp,alamat:c.alamat,poin:c.poin||0});
                selectCustomer({id:c.id,nama:c.nama,no_hp:c.no_hp,alamat:c.alamat,poin:c.poin||0});
                closeQuickCustomer();
                document.getElementById('qc_nama').value=''; document.getElementById('qc_no_hp').value=''; document.getElementById('qc_alamat').value='';
            }).catch(()=>alert('Gagal menambahkan customer cepat'));
    }
    function qErr(f,msg){ const e=document.getElementById('qerr_'+f); e.textContent=msg; e.classList.remove('hidden'); }

    // ---------- Init (pre-fill) ----------
    if (initialCustomer) selectCustomer(initialCustomer);
    if (initialItems.length) initialItems.forEach(it => addItemRow(it.service_id, it.qty));
    else addItemRow();
</script>
@endpush
@endsection
