@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2"><i data-lucide="washing-machine" class="h-8 w-8 text-accent"></i><span>Master Layanan &amp; Tarif</span></h1>
            <p class="text-slate-400 text-sm mt-1">Kelola macam-macam layanan cuci/setrika serta tarif yang ditawarkan kepada pelanggan.</p>
        </div>
        <button onclick="openAddService()" class="flex items-center gap-2 bg-accent hover:bg-accent-hover text-white font-semibold px-5 py-3 rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 active:translate-y-0 w-full sm:w-auto justify-center">
            <i data-lucide="plus" class="h-5 w-5"></i><span>Tambah Layanan</span>
        </button>
    </div>

    @if(session('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-2"><i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i><span>{{ session('error') }}</span></div>
    @endif

    @if($services->isEmpty())
        <div class="bg-slate-900/40 border border-slate-850 p-12 rounded-3xl text-center max-w-md mx-auto space-y-4">
            <div class="mx-auto w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center text-accent"><i data-lucide="washing-machine" class="h-8 w-8"></i></div>
            <div>
                <h3 class="text-lg font-bold text-white">Belum Ada Layanan</h3>
                <p class="text-slate-400 text-sm mt-1">Tambahkan layanan pertama Anda (misalnya: Cuci Setrika Per Kg) untuk memulai pembuatan nota order.</p>
            </div>
            <button onclick="openAddService()" class="bg-accent hover:bg-accent-hover text-white font-semibold px-4 py-2.5 rounded-xl transition-all">Tambah Layanan Sekarang</button>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($services as $service)
                <div class="bg-slate-900/60 border rounded-2xl p-6 flex flex-col justify-between transition-all duration-200 {{ $service->aktif ? 'border-slate-800 hover:border-slate-700' : 'border-slate-800/40 opacity-60' }}">
                    <div>
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <h3 class="font-bold text-lg text-white leading-tight">{{ $service->nama }}</h3>
                                <span class="inline-block mt-1.5 text-[10px] font-bold px-2 py-0.5 rounded-md {{ $service->kategori === 'sabun' ? 'bg-amber-500/10 text-amber-450 border border-amber-500/20' : 'bg-teal-500/10 text-teal-400 border border-teal-500/20' }}">
                                    {{ $service->kategori === 'sabun' ? 'Produk/Sabun' : 'Jasa Laundry' }}
                                </span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase {{ $service->satuan === 'kg' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' }}">Per {{ $service->satuan }}</span>
                        </div>
                        <div class="mt-4">
                            <span class="text-slate-400 text-xs">Tarif Layanan</span>
                            <div class="text-2xl font-black text-accent mt-0.5">{{ format_rupiah($service->tarif) }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-800/80 mt-6 pt-4">
                        <form method="POST" action="{{ route('services.toggle', $service) }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 text-xs font-semibold transition-colors">
                                @if($service->aktif)
                                    <i data-lucide="toggle-right" class="h-6 w-6 text-accent"></i><span class="text-slate-300">Aktif</span>
                                @else
                                    <i data-lucide="toggle-left" class="h-6 w-6 text-slate-500"></i><span class="text-slate-500">Nonaktif</span>
                                @endif
                            </button>
                        </form>
                        <div class="flex items-center gap-2">
                            <button onclick='openEditService(@json($service))' class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-lg transition-colors" title="Edit Layanan"><i data-lucide="edit-3" class="h-4 w-4"></i></button>
                            <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus layanan ini? Tindakan ini tidak dapat dibatalkan.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 bg-slate-800/50 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors" title="Hapus Layanan"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Modal -->
    <div id="serviceModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-in">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                <h2 class="text-lg font-bold text-white" id="serviceModalTitle">Tambah Layanan Baru</h2>
                <button onclick="closeServiceModal()" class="text-slate-400 hover:text-slate-200 transition-colors"><i data-lucide="x" class="h-5 w-5"></i></button>
            </div>
            <form id="serviceForm" method="POST" action="{{ route('services.store') }}" class="p-6 space-y-4" onsubmit="return validateService(event)">
                @csrf
                <input type="hidden" name="_method" id="serviceMethod" value="POST">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Layanan</label>
                    <input type="text" name="nama" id="sf_nama" placeholder="Contoh: Cuci Setrika Kiloan" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all">
                    <span class="text-xs text-rose-500 mt-1 hidden" id="serr_nama"></span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Kategori Item</label>
                    <select name="kategori" id="sf_kategori" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white focus:outline-none transition-all text-sm font-semibold">
                        <option value="laundry">Jasa Laundry</option>
                        <option value="sabun">Penjualan Sabun / Produk</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Satuan</label>
                        <select name="satuan" id="sf_satuan" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white focus:outline-none transition-all">
                            <option value="kg">Kiloan (kg)</option>
                            <option value="pcs">Satuan (pcs)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Tarif (Rp)</label>
                        <input type="number" name="tarif" id="sf_tarif" placeholder="8000" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all">
                        <span class="text-xs text-rose-500 mt-1 hidden" id="serr_tarif"></span>
                    </div>
                </div>
                <div class="flex items-center gap-3 py-2">
                    <input type="checkbox" name="aktif" id="sf_aktif" value="1" checked class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 bg-slate-950 border-slate-850">
                    <label for="sf_aktif" class="text-sm text-slate-350 select-none">Layanan ini aktif dan ditawarkan ke pelanggan</label>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" onclick="closeServiceModal()" class="px-5 py-2.5 rounded-xl border border-slate-800 hover:border-slate-700 text-slate-300 font-semibold transition-colors w-full sm:w-auto">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-white font-semibold shadow-lg transition-colors w-full sm:w-auto">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const serviceStoreUrl = "{{ route('services.store') }}";
    const serviceBaseUrl = "{{ url('services') }}";

    function openAddService() {
        document.getElementById('serviceModalTitle').textContent = 'Tambah Layanan Baru';
        document.getElementById('serviceForm').action = serviceStoreUrl;
        document.getElementById('serviceMethod').value = 'POST';
        document.getElementById('sf_nama').value = '';
        document.getElementById('sf_kategori').value = 'laundry';
        document.getElementById('sf_satuan').value = 'kg';
        document.getElementById('sf_tarif').value = '';
        document.getElementById('sf_aktif').checked = true;
        clearServiceErrors();
        showServiceModal();
    }
    function openEditService(s) {
        document.getElementById('serviceModalTitle').textContent = 'Edit Layanan';
        document.getElementById('serviceForm').action = serviceBaseUrl + '/' + s.id;
        document.getElementById('serviceMethod').value = 'PUT';
        document.getElementById('sf_nama').value = s.nama || '';
        document.getElementById('sf_kategori').value = s.kategori || 'laundry';
        document.getElementById('sf_satuan').value = s.satuan || 'kg';
        document.getElementById('sf_tarif').value = s.tarif || '';
        document.getElementById('sf_aktif').checked = !!s.aktif;
        clearServiceErrors();
        showServiceModal();
    }
    function showServiceModal(){ const m=document.getElementById('serviceModal'); m.classList.remove('hidden'); m.classList.add('flex'); }
    function closeServiceModal(){ const m=document.getElementById('serviceModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
    function clearServiceErrors(){ ['nama','tarif'].forEach(f=>{const e=document.getElementById('serr_'+f); e.classList.add('hidden'); e.textContent='';}); }
    function validateService(e) {
        clearServiceErrors();
        let ok = true;
        const nama = document.getElementById('sf_nama').value.trim();
        const tarif = document.getElementById('sf_tarif').value;
        if (!nama) { sErr('nama','Nama layanan wajib diisi'); ok = false; }
        if (tarif === '' || Number(tarif) <= 0) { sErr('tarif','Tarif harus berupa angka positif'); ok = false; }
        if (!ok) { e.preventDefault(); return false; }
        return true;
    }
    function sErr(f,msg){ const e=document.getElementById('serr_'+f); e.textContent=msg; e.classList.remove('hidden'); }
</script>
@endpush
@endsection
