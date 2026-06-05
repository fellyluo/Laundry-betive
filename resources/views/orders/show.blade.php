@extends('layouts.app')

@section('content')
@php
    function show_status_badge($s) {
        return match($s) {
            'diterima' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
            'diproses' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
            'selesai' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
            'diambil' => 'bg-slate-500/10 text-slate-400 border border-slate-500/20',
            'dibatalkan' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
            default => 'bg-slate-500/10 text-slate-400',
        };
    }
    $totalPaid = $order->payments->sum('jumlah');
    $remaining = $order->total - $totalPaid;
    $logoVal = $branding['logo_url'] ?? ($branding['logo_emoji'] ?? '🧺');
    $isImgLogo = is_string($logoVal) && str_starts_with($logoVal, 'data:image/');
    $logCount = $order->logs->count();
    $orderJs = [
        'nomor_nota' => $order->nomor_nota,
        'status' => $order->status,
        'status_bayar' => $order->status_bayar,
        'total' => $order->total,
        'paid' => $totalPaid,
        'customer' => ['nama' => $order->customer->nama ?? 'Umum', 'no_hp' => $order->customer->no_hp ?? ''],
        'items' => $order->items->map(fn($i) => ['nama' => $i->service->nama ?? 'Layanan', 'qty' => (float) $i->qty, 'satuan' => $i->service->satuan ?? '', 'harga' => $i->harga_satuan, 'subtotal' => $i->subtotal])->values(),
        'laundryName' => $branding['nama_laundry'] ?? 'LaundryPro',
    ];
    $methodInfo = collect($methods)->mapWithKeys(fn($m) => [$m['nama'] => ['no_rek' => $m['no_rek'] ?? null, 'qris' => $m['qris'] ?? null]]);
@endphp

<!-- ===== WEB SCREEN (no-print) ===== -->
<div class="space-y-8 pb-12 no-print max-w-5xl">
    <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-200 transition-colors text-sm font-semibold"><i data-lucide="arrow-left" class="h-4 w-4"></i><span>Kembali ke Daftar Order</span></a>

    <!-- Action header -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="font-mono text-xl font-black text-white">{{ $order->nomor_nota }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold capitalize {{ show_status_badge($order->status) }}">{{ $order->status }}</span>
            </div>
            <p class="text-xs text-slate-500">Diterima pada {{ format_date($order->tanggal_masuk, true) }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if($order->status === 'diterima')
                <form method="POST" action="{{ route('orders.status', $order) }}" class="flex-1 sm:flex-initial">@csrf<input type="hidden" name="status" value="diproses">
                    <button type="submit" class="w-full flex items-center justify-center gap-1.5 bg-amber-600 hover:bg-amber-500 text-white font-bold px-4 py-3 text-xs rounded-xl transition-all duration-200 shadow-md hover:-translate-y-0.5 whitespace-nowrap cursor-pointer"><i data-lucide="play" class="h-4.5 w-4.5 fill-white"></i><span>Mulai Proses Cuci</span></button>
                </form>
            @elseif($order->status === 'diproses')
                <form method="POST" action="{{ route('orders.status', $order) }}" class="flex-1 sm:flex-initial">@csrf<input type="hidden" name="status" value="selesai">
                    <button type="submit" class="w-full flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-4 py-3 text-xs rounded-xl transition-all duration-200 shadow-md hover:-translate-y-0.5 whitespace-nowrap cursor-pointer"><i data-lucide="check-circle-2" class="h-4.5 w-4.5"></i><span>Selesaikan Cucian</span></button>
                </form>
            @elseif($order->status === 'selesai')
                <form method="POST" action="{{ route('orders.status', $order) }}" class="flex-1 sm:flex-initial">@csrf<input type="hidden" name="status" value="diambil">
                    <button type="submit" class="w-full flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold px-4 py-3 text-xs rounded-xl transition-all duration-200 shadow-md hover:-translate-y-0.5 whitespace-nowrap cursor-pointer"><i data-lucide="package-check" class="h-4.5 w-4.5"></i><span>Serahkan Laundry</span></button>
                </form>
            @endif

            <button onclick="sendWhatsApp()" class="flex-1 sm:flex-initial flex items-center justify-center gap-2 bg-slate-800 hover:bg-teal-700/20 hover:text-accent text-slate-200 font-bold px-4 py-3 rounded-xl transition-all border border-slate-750/30" title="Kirim Pesan WhatsApp"><i data-lucide="send" class="h-4.5 w-4.5 text-emerald-550"></i><span class="text-xs">Kirim WA</span></button>
            <button onclick="window.print()" class="flex-1 sm:flex-initial flex items-center justify-center gap-2 bg-slate-800 hover:bg-teal-700/20 hover:text-accent text-slate-200 font-bold px-4 py-3 rounded-xl transition-all border border-slate-750/30" title="Cetak Nota"><i data-lucide="printer" class="h-4.5 w-4.5 text-slate-400"></i><span class="text-xs">Cetak Struk</span></button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Items + log -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
                <div class="flex items-start gap-4 border-b border-slate-850 pb-5">
                    <div class="p-3 bg-accent/10 text-accent rounded-xl"><i data-lucide="user" class="h-6 w-6"></i></div>
                    <div class="space-y-1">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Identitas Pelanggan</span>
                        <h4 class="font-bold text-white text-base">{{ $order->customer->nama ?? 'Umum' }}</h4>
                        <div class="text-xs text-slate-400 font-mono flex flex-wrap gap-x-4">
                            <span>HP: {{ $order->customer->no_hp ?? '-' }}</span>
                            @if($order->customer && $order->customer->alamat)<span>Alamat: {{ $order->customer->alamat }}</span>@endif
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="font-bold text-white text-sm flex items-center gap-2"><i data-lucide="file-text" class="h-4 w-4 text-accent"></i><span>Item Rincian Cucian</span></h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="border-b border-slate-800 text-slate-400 text-xs font-semibold">
                                    <th class="pb-3">Layanan</th><th class="pb-3 text-center w-24">Jumlah</th><th class="pb-3 text-right w-28">Harga Satuan</th><th class="pb-3 text-right w-32">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-850/60 text-slate-300">
                                @foreach($order->items as $item)
                                    <tr>
                                        <td class="py-4 font-semibold text-slate-200">{{ $item->service->nama ?? 'Item' }}</td>
                                        <td class="py-4 text-center font-mono">{{ rtrim(rtrim(number_format($item->qty,2,'.',''),'0'),'.') }} <span class="text-xs text-slate-500 uppercase">{{ $item->service->satuan ?? '' }}</span></td>
                                        <td class="py-4 text-right font-mono">{{ format_rupiah($item->harga_satuan) }}</td>
                                        <td class="py-4 text-right font-bold font-mono text-accent">{{ format_rupiah($item->subtotal) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col gap-2 items-end border-t border-slate-850 pt-5 text-sm">
                        <div class="flex justify-between w-64 text-slate-450"><span>Subtotal Tagihan:</span><span class="font-mono font-bold text-slate-300">{{ format_rupiah($order->total) }}</span></div>
                        <div class="flex justify-between w-64 text-slate-450"><span>Sudah Dibayar:</span><span class="font-mono font-bold text-emerald-450">{{ format_rupiah($totalPaid) }}</span></div>
                        <div class="flex justify-between w-64 border-t border-slate-850 pt-2 text-base font-extrabold text-white"><span>Sisa Tagihan:</span><span class="font-mono text-accent">{{ format_rupiah($remaining) }}</span></div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
                <h4 class="font-bold text-white text-sm flex items-center gap-2"><i data-lucide="clock" class="h-4 w-4 text-accent"></i><span>Riwayat Status &amp; Audit Trail</span></h4>
                <div class="relative border-l-2 border-slate-850 pl-5 ml-2.5 space-y-6">
                    @foreach($order->logs as $idx => $log)
                        <div class="relative">
                            <span class="absolute -left-7.5 top-1 bg-slate-950 border-2 border-accent w-4 h-4 rounded-full flex items-center justify-center"><span class="w-1.5 h-1.5 bg-accent rounded-full"></span></span>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-slate-200 capitalize">Status: {{ $log->status }}</span>
                                    @if($idx === $logCount - 1)<span class="text-[9px] bg-accent/10 text-accent px-2 py-0.5 rounded-full border border-accent/20 font-semibold uppercase animate-pulse">Terbaru</span>@endif
                                </div>
                                <span class="text-xs text-slate-500 block">Diperbarui pada: {{ format_date($log->created_at, true) }} WIB</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Payment side -->
        <div class="space-y-8">
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
                <div class="flex justify-between items-center">
                    <h4 class="font-bold text-white text-sm flex items-center gap-2"><i data-lucide="credit-card" class="h-4 w-4 text-accent"></i><span>Riwayat Transaksi</span></h4>
                    @if($remaining > 0)
                        <button onclick="openPayModal()" class="p-1.5 bg-accent hover:bg-accent-hover text-white rounded-lg transition-colors flex items-center gap-1 text-[11px] font-bold px-2.5"><i data-lucide="plus" class="h-3.5 w-3.5"></i><span>Bayar</span></button>
                    @endif
                </div>
                @if($order->payments->isEmpty())
                    <div class="text-center py-6 text-slate-550 text-xs italic">Belum ada transaksi pembayaran.</div>
                @else
                    <div class="space-y-4">
                        @foreach($order->payments as $p)
                            <div class="p-3 bg-slate-950/70 border border-slate-850 rounded-xl space-y-2">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-mono text-slate-450">{{ format_date($p->created_at, true) }}</span>
                                    <span class="px-2 py-0.5 bg-slate-850 border border-slate-750 text-[10px] text-slate-300 font-bold uppercase rounded-md">{{ $p->metode }}</span>
                                </div>
                                <div class="flex justify-between items-end"><span class="text-xs text-slate-500">Jumlah Bayar</span><span class="font-bold text-emerald-450 font-mono text-sm">+{{ format_rupiah($p->jumlah) }}</span></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <h4 class="font-bold text-white text-sm">Ukuran Cetak Thermal</h4>
                <div class="grid grid-cols-2 gap-2">
                    <button id="rw58" onclick="setReceiptWidth('58mm')" class="py-2 rounded-xl text-xs font-bold transition-all border bg-accent/10 border-accent text-accent shadow-md">Bluetooth Mini (58mm)</button>
                    <button id="rw80" onclick="setReceiptWidth('80mm')" class="py-2 rounded-xl text-xs font-bold transition-all border bg-slate-950 border-slate-850 text-slate-500">Desktop (80mm)</button>
                </div>
            </div>

            @if($order->status !== 'diambil' && $order->status !== 'dibatalkan')
                <div class="bg-slate-900/30 border border-slate-850/50 rounded-2xl p-6 text-center shadow-md">
                    <form method="POST" action="{{ route('orders.status', $order) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan laundry ini?')">
                        @csrf<input type="hidden" name="status" value="dibatalkan">
                        <button type="submit" class="text-xs font-bold text-rose-500 hover:text-rose-400 hover:underline">Batalkan Transaksi Order Ini</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- ===== PRINT-ONLY THERMAL RECEIPT ===== -->
<div id="printArea" class="print-only print-area font-mono text-black mx-auto p-4 leading-tight" style="width:100%; max-width:58mm; font-size:10px;">
    <div class="text-center space-y-1">
        @if($isImgLogo)<img src="{{ $logoVal }}" alt="Logo" class="h-10 mx-auto object-contain">@else<div class="text-2xl">{{ $logoVal }}</div>@endif
        <h2 class="text-base font-extrabold tracking-wide uppercase">{{ $branding['nama_laundry'] ?? 'LaundryPro' }}</h2>
        @if(!empty($branding['alamat_laundry']))<p class="text-[10px] leading-tight">{{ $branding['alamat_laundry'] }}</p>@endif
        <p class="text-[10px] leading-tight">Telp: {{ $branding['no_telp_laundry'] ?: ($order->customer->no_hp ?? '-') }}</p>
    </div>
    <div class="my-4 border-t border-dashed border-black"></div>
    <div class="space-y-1 text-[10px]">
        <div class="flex justify-between"><span>Nota:</span><span class="font-bold">{{ $order->nomor_nota }}</span></div>
        <div class="flex justify-between"><span>Tanggal:</span><span>{{ format_date($order->tanggal_masuk, true) }}</span></div>
        <div class="flex justify-between"><span>Pelanggan:</span><span class="font-bold">{{ $order->customer->nama ?? 'Umum' }}</span></div>
        <div class="flex justify-between"><span>Estimasi:</span><span class="font-bold">{{ format_date($order->estimasi_selesai, true) }}</span></div>
    </div>
    <div class="my-4 border-t border-dashed border-black"></div>
    <div class="space-y-2">
        @foreach($order->items as $item)
            <div class="space-y-0.5">
                <div class="font-bold text-[10px] uppercase">{{ $item->service->nama ?? 'Item' }}</div>
                <div class="flex justify-between text-[10px]"><span>{{ rtrim(rtrim(number_format($item->qty,2,'.',''),'0'),'.') }} {{ $item->service->satuan ?? '' }} x {{ format_rupiah($item->harga_satuan) }}</span><span>{{ format_rupiah($item->subtotal) }}</span></div>
            </div>
        @endforeach
    </div>
    <div class="my-4 border-t border-dashed border-black"></div>
    <div class="space-y-1 text-[10px]">
        <div class="flex justify-between font-extrabold text-sm"><span>TOTAL TAGIHAN:</span><span>{{ format_rupiah($order->total) }}</span></div>
        <div class="flex justify-between"><span>Jumlah Dibayar:</span><span>{{ format_rupiah($totalPaid) }}</span></div>
        <div class="flex justify-between border-t border-dotted border-black pt-1"><span>Sisa Tagihan:</span><span class="font-bold">{{ format_rupiah($remaining) }}</span></div>
        <div class="flex justify-between mt-2 font-bold uppercase border border-black p-1 text-center items-center justify-center"><span>Status Bayar: {{ $order->status_bayar }}</span></div>
    </div>
    @if($remaining > 0)
        @php $payInfo = collect($methods)->filter(fn($m) => ! empty($m['no_rek']) || ! empty($m['qris'])); @endphp
        @if($payInfo->isNotEmpty())
            <div class="my-4 border-t border-dashed border-black"></div>
            <div class="space-y-1 text-[10px]">
                <p class="font-bold text-center uppercase">Cara Pembayaran</p>
                @foreach($payInfo as $m)
                    <div class="mt-1.5">
                        <div class="font-bold">{{ $m['nama'] }}</div>
                        @if(! empty($m['no_rek']))<div class="break-all">{{ $m['no_rek'] }}</div>@endif
                        @if(! empty($m['qris']))
                            <div class="text-center mt-1">
                                <img src="{{ $m['qris'] }}" alt="QRIS" style="width:38mm;max-width:100%;margin:0 auto;display:block;" />
                                <div class="text-[9px] mt-0.5">Scan QRIS untuk bayar</div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif
    <div class="my-4 border-t border-dashed border-black"></div>
    <div class="text-center text-[9px] space-y-1">
        <p class="font-bold">Terima kasih atas kepercayaan Anda!</p>
        <p>Cucian Anda adalah amanah bagi kami.</p>
        <p>Harap periksa cucian saat diserahkan.</p>
    </div>
</div>

<!-- Pay modal -->
<div id="payModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4 no-print">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-in">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
            <h2 class="text-lg font-bold text-white flex items-center gap-2"><i data-lucide="credit-card" class="h-5 w-5 text-accent"></i><span>Bayar Cicilan / Pelunasan</span></h2>
            <button type="button" onclick="closePayModal()" class="text-slate-400 hover:text-slate-200 transition-colors"><i data-lucide="x" class="h-5 w-5"></i></button>
        </div>
        <form method="POST" action="{{ route('orders.payment', $order) }}" class="p-6 space-y-4" onsubmit="return validatePay(event)">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nominal Pembayaran (Rp)</label>
                <input type="number" name="jumlah_bayar" id="payAmount" value="{{ $remaining > 0 ? $remaining : '' }}" max="{{ $remaining }}" placeholder="Contoh: 15000" class="w-full bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-xl px-4 py-2.5 text-white font-bold text-base focus:outline-none transition-all">
                <span class="text-xs text-slate-500 mt-1 block">Maksimal sisa tagihan: <span class="font-semibold text-accent">{{ format_rupiah($remaining) }}</span></span>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Metode Pembayaran</label>
                <select name="metode_bayar" id="payMetode" onchange="onPayMethodChange(this.value)" class="w-full bg-slate-955 border border-slate-800 hover:border-slate-750 focus:border-accent focus:outline-none rounded-xl px-4 py-2.5 text-white font-semibold text-sm transition-all">
                    @foreach($methods as $m)<option value="{{ $m['nama'] }}">{{ $m['nama'] }}</option>@endforeach
                </select>
            </div>
            <div id="payMethodInfo" class="hidden"></div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="button" onclick="closePayModal()" class="px-4 py-2 rounded-xl border border-slate-800 text-slate-350 hover:border-slate-700 text-sm font-semibold transition-colors">Batal</button>
                <button type="submit" class="px-5 py-2 bg-accent hover:bg-accent-hover text-white rounded-xl text-sm font-semibold shadow-lg transition-colors">Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const ORDER = @json($orderJs);

    function rupiah(v){ return 'Rp ' + Math.round(v).toLocaleString('id-ID'); }
    function fmtQty(q){ return (Math.round(q*100)/100).toString(); }

    function setReceiptWidth(w) {
        const area = document.getElementById('printArea');
        area.style.maxWidth = w;
        area.style.fontSize = w === '58mm' ? '10px' : '12px';
        const a = document.getElementById('rw58'), b = document.getElementById('rw80');
        const active = 'py-2 rounded-xl text-xs font-bold transition-all border bg-accent/10 border-accent text-accent shadow-md';
        const idle = 'py-2 rounded-xl text-xs font-bold transition-all border bg-slate-950 border-slate-850 text-slate-500';
        if (w === '58mm') { a.className = active; b.className = idle; } else { b.className = active; a.className = idle; }
    }

    function waNumber(phone){ let c = (phone||'').replace(/[^0-9]/g,''); if(c.startsWith('0')) c='62'+c.slice(1); else if(c.startsWith('8')) c='62'+c; return c; }

    function sendWhatsApp() {
        const o = ORDER;
        const unpaid = o.total - o.paid;
        let text = `Halo *${o.customer.nama}*,\n`;
        if (o.status === 'selesai') text += `Laundry Anda dengan nomor nota *${o.nomor_nota}* sudah *SELESAI* dan siap untuk diambil. 🧺\n\n`;
        else text += `Berikut rincian pesanan laundry Anda dengan nomor nota *${o.nomor_nota}* di *${o.laundryName}*. 🧺\n\n`;
        text += `Rincian Layanan:\n`;
        o.items.forEach(it => { text += `- ${it.nama}: ${fmtQty(it.qty)} ${it.satuan} x ${rupiah(it.harga)} = ${rupiah(it.subtotal)}\n`; });
        text += `\n*Total Biaya*: *${rupiah(o.total)}*\n`;
        if (unpaid > 0) text += `*Sisa Tagihan*: *${rupiah(unpaid)}* (Status: Belum Bayar)\n\n`;
        else text += `*Status Bayar*: *LUNAS* (Terima kasih) ✅\n\n`;
        text += `Silakan berkunjung kembali untuk mengambil cucian Anda.\nTerima kasih telah berlangganan di *${o.laundryName}*! ✨`;
        window.open('https://wa.me/' + waNumber(o.customer.no_hp) + '?text=' + encodeURIComponent(text), '_blank');
    }

    const methodInfo = @json($methodInfo);
    function onPayMethodChange(nama) {
        const box = document.getElementById('payMethodInfo');
        const info = methodInfo[nama];
        if (!info || (!info.no_rek && !info.qris)) { box.classList.add('hidden'); box.innerHTML = ''; return; }
        let html = '<div class="p-3 bg-slate-950/80 border border-slate-850 rounded-xl space-y-2">';
        html += '<div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Info Pembayaran</div>';
        if (info.no_rek) html += `<div class="text-xs text-slate-200 font-mono break-all">${escHtml(info.no_rek)}</div>`;
        if (info.qris) html += `<img src="${info.qris}" alt="QRIS" class="w-40 h-40 object-contain bg-white rounded-lg border border-slate-850 mx-auto">`;
        html += '</div>';
        box.innerHTML = html; box.classList.remove('hidden');
    }
    function escHtml(s){ return String(s==null?'':s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    function openPayModal(){
        const m=document.getElementById('payModal'); m.classList.remove('hidden'); m.classList.add('flex');
        const sel=document.getElementById('payMetode'); if(sel) onPayMethodChange(sel.value);
    }
    function closePayModal(){ const m=document.getElementById('payModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
    function validatePay(e){ const v = document.getElementById('payAmount').value; if(v==='' || Number(v)<=0){ alert('Jumlah pembayaran harus bernilai positif.'); e.preventDefault(); return false; } return true; }
</script>
@endpush
@endsection
