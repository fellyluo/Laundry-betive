@extends('layouts.app')

@section('content')
@php
    $catLabel = ['operasional'=>'Operasional','gaji'=>'Gaji/Upah','bahan'=>'Bahan/Deterjen','sewa'=>'Sewa','listrik'=>'Listrik/Air','lainnya'=>'Lainnya'];
    $catBadge = function($k){ return match($k){
        'gaji' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
        'bahan' => 'bg-amber-500/10 text-amber-450 border border-amber-500/20',
        'sewa' => 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20',
        'listrik' => 'bg-emerald-500/10 text-emerald-450 border border-emerald-500/20',
        'operasional' => 'bg-teal-500/10 text-teal-400 border border-teal-500/20',
        default => 'bg-slate-500/10 text-slate-400 border border-slate-500/20',
    }; };
@endphp

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2"><i data-lucide="receipt" class="h-8 w-8 text-accent"></i><span>Pengeluaran</span></h1>
            <p class="text-slate-400 text-sm mt-1">Catat biaya operasional (gaji, deterjen, listrik, sewa, dll.) untuk menghitung laba bersih.</p>
        </div>
        <button onclick="openAddExpense()" class="flex items-center gap-2 bg-accent hover:bg-accent-hover text-white font-semibold px-5 py-3 rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 active:translate-y-0 w-full sm:w-auto justify-center">
            <i data-lucide="plus" class="h-5 w-5"></i><span>Catat Pengeluaran</span>
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-2 animate-in"><i data-lucide="check" class="h-5 w-5 shrink-0"></i><span>{{ session('success') }}</span></div>
    @endif

    <!-- Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-slate-900/60 border border-slate-800/80 p-5 rounded-xl shadow-lg">
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Pengeluaran Hari Ini</p>
            <h3 class="text-xl font-bold text-rose-400 mt-1">{{ format_rupiah($totalToday) }}</h3>
        </div>
        <div class="bg-slate-900/60 border border-slate-800/80 p-5 rounded-xl shadow-lg">
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Pengeluaran Bulan Ini</p>
            <h3 class="text-xl font-bold text-rose-400 mt-1">{{ format_rupiah($totalMonth) }}</h3>
        </div>
        <div class="bg-slate-900/60 border border-slate-800/80 p-5 rounded-xl shadow-lg">
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Total Keseluruhan</p>
            <h3 class="text-xl font-bold text-white mt-1">{{ format_rupiah($totalAll) }}</h3>
        </div>
    </div>

    <!-- List -->
    @if($expenses->isEmpty())
        <div class="bg-slate-900/40 border border-slate-850 p-12 rounded-3xl text-center max-w-md mx-auto space-y-4">
            <div class="mx-auto w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center text-accent"><i data-lucide="receipt" class="h-8 w-8"></i></div>
            <div>
                <h3 class="text-lg font-bold text-white">Belum Ada Pengeluaran</h3>
                <p class="text-slate-400 text-sm mt-1">Catat pengeluaran pertama agar laporan laba bersih lebih akurat.</p>
            </div>
            <button onclick="openAddExpense()" class="bg-accent hover:bg-accent-hover text-white font-semibold px-4 py-2.5 rounded-xl transition-all">Catat Pengeluaran</button>
        </div>
    @else
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xl overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[520px]">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs font-semibold">
                        <th class="pb-3 pr-3 w-32">Tanggal</th><th class="pb-3 px-3">Keterangan</th><th class="pb-3 px-3 w-36">Kategori</th><th class="pb-3 px-3 w-32 text-right">Jumlah</th><th class="pb-3 pl-3 w-16 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-850">
                    @foreach($expenses as $e)
                        <tr class="text-sm hover:bg-slate-800/10 transition-colors">
                            <td class="py-4 pr-3 text-slate-400 font-mono text-xs">{{ format_date($e->tanggal) }}</td>
                            <td class="py-4 px-3 font-medium text-slate-200">{{ $e->keterangan }}</td>
                            <td class="py-4 px-3"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $catBadge($e->kategori) }}">{{ $catLabel[$e->kategori] ?? ucfirst($e->kategori) }}</span></td>
                            <td class="py-4 px-3 text-right font-bold text-rose-400 font-mono">-{{ format_rupiah($e->jumlah) }}</td>
                            <td class="py-4 pl-3 text-right">
                                <form method="POST" action="{{ route('expenses.destroy', $e) }}" onsubmit="return confirm('Hapus pengeluaran ini?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 bg-slate-800/50 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors" title="Hapus"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $expenses->links() }}</div>
    @endif

    <!-- Modal -->
    <div id="expenseModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-in">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                <h2 class="text-lg font-bold text-white flex items-center gap-2"><i data-lucide="receipt" class="h-5 w-5 text-accent"></i><span>Catat Pengeluaran</span></h2>
                <button onclick="closeExpenseModal()" class="text-slate-400 hover:text-slate-200 transition-colors"><i data-lucide="x" class="h-5 w-5"></i></button>
            </div>
            <form method="POST" action="{{ route('expenses.store') }}" class="p-6 space-y-4" onsubmit="return validateExpense(event)">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Keterangan</label>
                    <input type="text" name="keterangan" id="ef_ket" placeholder="cth: Beli deterjen, bayar listrik..." class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all">
                    <span class="text-xs text-rose-500 mt-1 hidden" id="eerr_ket"></span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Kategori</label>
                        <select name="kategori" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white focus:outline-none transition-all text-sm font-semibold">
                            @foreach($catLabel as $k => $lbl)<option value="{{ $k }}">{{ $lbl }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Jumlah (Rp)</label>
                        <input type="number" name="jumlah" id="ef_jml" placeholder="50000" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white placeholder-slate-600 focus:outline-none transition-all">
                        <span class="text-xs text-rose-500 mt-1 hidden" id="eerr_jml"></span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ now()->format('Y-m-d') }}" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white focus:outline-none transition-all text-sm">
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" onclick="closeExpenseModal()" class="px-5 py-2.5 rounded-xl border border-slate-800 hover:border-slate-700 text-slate-300 font-semibold transition-colors w-full sm:w-auto">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-white font-semibold shadow-lg transition-colors w-full sm:w-auto">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openAddExpense(){ const m=document.getElementById('expenseModal'); m.classList.remove('hidden'); m.classList.add('flex'); }
    function closeExpenseModal(){ const m=document.getElementById('expenseModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
    function validateExpense(e){
        ['ket','jml'].forEach(f=>document.getElementById('eerr_'+f).classList.add('hidden'));
        let ok=true;
        if(!document.getElementById('ef_ket').value.trim()){ eErr('ket','Keterangan wajib diisi'); ok=false; }
        const j=document.getElementById('ef_jml').value;
        if(j==='' || Number(j)<=0){ eErr('jml','Jumlah harus angka positif'); ok=false; }
        if(!ok){ e.preventDefault(); return false; }
        return true;
    }
    function eErr(f,msg){ const el=document.getElementById('eerr_'+f); el.textContent=msg; el.classList.remove('hidden'); }
    @if($errors->any() || session('open_expense'))openAddExpense();@endif
</script>
@endpush
@endsection
