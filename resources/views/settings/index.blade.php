@extends('layouts.app')

@section('content')
@php
    $b = $settings['branding'];
    $logoUrl = $b['logo_url'] ?? null;
@endphp

<div class="space-y-8 pb-10 max-w-4xl">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2"><i data-lucide="settings" class="h-8 w-8 text-accent"></i><span>Pengaturan Kustom Aplikasi</span></h1>
        <p class="text-slate-400 text-sm mt-1">Kustomisasi visual tema warna, logo branding, identitas nota, dan daftar metode pembayaran laundry.</p>
    </div>

    @if($errors->any())
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-2"><i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i><span>{{ $errors->first() }}</span></div>
    @endif
    @if(session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-2 animate-in"><i data-lucide="check" class="h-5 w-5 shrink-0"></i><span>{{ session('success') }}</span></div>
    @endif

    <form method="POST" action="{{ route('settings.save') }}">
        @csrf
        <input type="hidden" name="theme_color" id="f_theme_color" value="{{ $settings['theme_color'] }}">
        <input type="hidden" name="theme_color_font" id="f_theme_color_font" value="{{ $settings['theme_color_font'] }}">
        <input type="hidden" name="theme_color_bg" id="f_theme_color_bg" value="{{ $settings['theme_color_bg'] }}">
        <input type="hidden" name="theme_bg" id="f_theme_bg" value="{{ $settings['theme_bg'] }}">
        <input type="hidden" name="theme_mode" id="f_theme_mode" value="{{ $settings['theme_mode'] }}">
        <input type="hidden" name="logo_url" id="f_logo_url" value="{{ $logoUrl }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="lg:col-span-2 space-y-8">
                <!-- 1. Branding -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <h3 class="font-bold text-white text-base border-b border-slate-850 pb-3 flex items-center gap-2"><span class="w-2 h-5 bg-accent rounded-full"></span><span>Identitas Laundry &amp; Nota</span></h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-b border-slate-850/80 pb-5 mb-4">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Logo Gambar (Kustom)</label>
                            <div class="flex items-center gap-3 bg-slate-950/40 p-3 border border-slate-850/50 rounded-xl shadow-inner">
                                <div id="logoPreview" class="w-12 h-12 bg-slate-950 border border-slate-850 rounded-lg flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                                    @if($logoUrl)<img src="{{ $logoUrl }}" alt="Logo" class="w-full h-full object-contain">@else<span class="text-slate-600 text-[10px] text-center leading-tight p-1">No Logo</span>@endif
                                </div>
                                <div class="flex-1 flex flex-col items-start gap-1">
                                    <label class="inline-flex items-center justify-center px-3 py-1.5 bg-slate-950 hover:bg-slate-850 border border-slate-850 rounded-lg text-xs font-semibold text-slate-350 hover:text-white cursor-pointer transition-all">
                                        <span>Pilih Berkas</span>
                                        <input type="file" accept="image/*" onchange="handleLogoUpload(event)" class="hidden">
                                    </label>
                                    <p class="text-[9px] text-slate-550">Maks. 256x256px, kompresi otomatis.</p>
                                </div>
                                <button type="button" id="removeLogoBtn" onclick="removeLogo()" class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-450 hover:text-rose-400 text-xs font-bold rounded-lg transition-all cursor-pointer {{ $logoUrl ? '' : 'hidden' }}">Hapus</button>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Logo Emoji (Fallback)</label>
                            <div class="flex gap-3">
                                <input type="text" name="logo_emoji" maxlength="2" value="{{ $b['logo_emoji'] }}" placeholder="🧺" class="w-16 bg-slate-950 border border-slate-850 hover:border-slate-800 focus:border-accent rounded-xl px-2 py-3 text-center text-2xl focus:outline-none transition-all">
                                <div class="flex-1 text-[11px] text-slate-550 flex items-center leading-normal">Emoji ini akan otomatis digunakan jika Anda tidak mengunggah logo gambar kustom.</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Laundry</label>
                        <input type="text" name="nama_laundry" value="{{ $b['nama_laundry'] }}" placeholder="Contoh: LaundryPro Premium" class="w-full bg-slate-950 border border-slate-850 hover:border-slate-800 focus:border-accent rounded-xl px-4 py-2.5 text-white focus:outline-none transition-all text-sm font-semibold">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">No. Telp Laundry</label>
                            <input type="text" name="no_telp_laundry" value="{{ $b['no_telp_laundry'] }}" placeholder="08123456789" class="w-full bg-slate-950 border border-slate-850 hover:border-slate-800 focus:border-accent rounded-xl px-4 py-2.5 text-white focus:outline-none transition-all text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Alamat Laundry</label>
                            <input type="text" name="alamat_laundry" value="{{ $b['alamat_laundry'] }}" placeholder="Jl. Raya Utama No. 12" class="w-full bg-slate-950 border border-slate-850 hover:border-slate-800 focus:border-accent rounded-xl px-4 py-2.5 text-white focus:outline-none transition-all text-sm">
                        </div>
                    </div>
                </div>

                <!-- 2. Theme colors -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
                    <h3 class="font-bold text-white text-base border-b border-slate-850 pb-3 flex items-center gap-2"><span class="w-2 h-5 bg-accent rounded-full"></span><span>Warna Tema Aplikasi</span></h3>
                    <div class="space-y-3">
                        <h4 class="font-semibold text-white text-sm">Aksen Warna Utama</h4>
                        <div class="grid grid-cols-4 sm:grid-cols-9 gap-3" id="colorPresetGrid">
                            @foreach($colorPresets as $key => $color)
                                <button type="button" data-color-key="{{ $key }}" data-accent="{{ $color['accent'] }}" data-hover="{{ $color['hover'] }}" onclick="selectColor('{{ $key }}','{{ $color['accent'] }}','{{ $color['hover'] }}')"
                                        class="color-preset flex flex-col items-center justify-center p-3 rounded-xl border transition-all duration-200 cursor-pointer {{ $settings['theme_color'] === $key ? 'border-accent bg-accent/10 shadow-lg scale-105' : 'border-slate-850 bg-slate-950 hover:border-slate-800' }}">
                                    <span class="w-8 h-8 rounded-full border border-black/30 shadow-inner flex items-center justify-center text-white" style="background-color: {{ $color['accent'] }}">
                                        <span class="preset-check {{ $settings['theme_color'] === $key ? '' : 'hidden' }}"><i data-lucide="check" class="h-4 w-4 drop-shadow"></i></span>
                                    </span>
                                    <span class="text-[10px] mt-1.5 capitalize font-semibold text-slate-400">{{ $key === 'teal' ? 'default' : $key }}</span>
                                </button>
                            @endforeach
                            <button type="button" data-color-key="custom" onclick="selectColorCustom()" class="color-preset flex flex-col items-center justify-center p-3 rounded-xl border transition-all duration-200 cursor-pointer {{ $settings['theme_color'] === 'custom' ? 'border-accent bg-accent/10 shadow-lg scale-105' : 'border-slate-850 bg-slate-950 hover:border-slate-800' }}">
                                <span class="w-8 h-8 rounded-full border border-black/30 shadow-inner flex items-center justify-center text-base" style="background: linear-gradient(135deg, #ef4444 0%, #3b82f6 50%, #10b981 100%)">
                                    <span class="preset-check {{ $settings['theme_color'] === 'custom' ? '' : 'hidden' }}"><i data-lucide="check" class="h-4 w-4 drop-shadow text-white"></i></span>
                                </span>
                                <span class="text-[10px] mt-1.5 capitalize font-semibold text-slate-400">Kustom 🎨</span>
                            </button>
                        </div>
                    </div>

                    <div id="customColorBox" class="p-4 bg-slate-950/60 border border-slate-850 rounded-xl grid grid-cols-1 sm:grid-cols-2 gap-4 animate-in {{ $settings['theme_color'] === 'custom' ? '' : 'hidden' }}">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Warna Font / Teks Aksen</label>
                            <div class="flex items-center gap-3">
                                <input type="color" id="pick_font" value="{{ $settings['theme_color_font'] }}" onchange="onCustomFont(this.value)" class="w-10 h-10 rounded-lg border border-slate-800 cursor-pointer bg-transparent">
                                <input type="text" id="text_font" value="{{ $settings['theme_color_font'] }}" onchange="onCustomFont(this.value)" class="flex-1 bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-lg px-3 py-2 text-sm text-white focus:outline-none uppercase font-mono">
                            </div>
                            <p class="text-[10px] text-slate-500">Mempengaruhi warna teks sorotan, tautan, dan ikon aktif.</p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Warna Latar Belakang / Tombol Aksen</label>
                            <div class="flex items-center gap-3">
                                <input type="color" id="pick_bg" value="{{ $settings['theme_color_bg'] }}" onchange="onCustomBg(this.value)" class="w-10 h-10 rounded-lg border border-slate-800 cursor-pointer bg-transparent">
                                <input type="text" id="text_bg" value="{{ $settings['theme_color_bg'] }}" onchange="onCustomBg(this.value)" class="flex-1 bg-slate-950 border border-slate-800 hover:border-slate-700 focus:border-accent rounded-lg px-3 py-2 text-sm text-white focus:outline-none uppercase font-mono">
                            </div>
                            <p class="text-[10px] text-slate-500">Mempengaruhi warna tombol utama, border aktif, dan background sorotan.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 p-3 bg-slate-950/60 border border-slate-850 rounded-xl text-xs text-slate-500"><i data-lucide="info" class="h-4 w-4 shrink-0 text-slate-400"></i><span>Memilih warna di atas akan mengubah seluruh aksen visual tombol, tag, dan elemen aktif di seluruh aplikasi secara instan.</span></div>

                    <div id="bgSection" class="border-t border-slate-850/80 pt-6 space-y-4 transition-all duration-300 {{ $settings['theme_mode'] === 'light' ? 'opacity-40 pointer-events-none' : '' }}">
                        <h4 class="font-semibold text-white text-sm flex items-center gap-2">
                            <span>Warna Latar Belakang Aplikasi</span>
                            @if($settings['theme_mode'] === 'light')<span class="text-[10px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded font-normal">Hanya Aktif di Mode Gelap</span>@endif
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                            @foreach($bgPresets as $key => $bg)
                                <button type="button" data-bg-key="{{ $key }}" data-bg-color="{{ $bg['color'] }}" onclick="selectBg('{{ $key }}','{{ $bg['color'] }}')"
                                        class="bg-preset flex items-center gap-3 p-2.5 rounded-xl border text-left transition-all duration-200 {{ $settings['theme_bg'] === $key ? 'border-accent bg-accent/5 shadow-lg scale-102' : 'border-slate-850 bg-slate-950 hover:border-slate-800' }}">
                                    <span class="w-5 h-5 rounded-full border border-slate-700/80 flex items-center justify-center shrink-0" style="background-color: {{ $bg['color'] }}">
                                        <span class="bg-check {{ $settings['theme_bg'] === $key ? '' : 'hidden' }}"><span class="w-2 h-2 rounded-full bg-accent animate-pulse block"></span></span>
                                    </span>
                                    <span class="text-xs font-semibold text-slate-300 truncate">{{ explode(' (', $bg['label'])[0] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Payment methods -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                <h3 class="font-bold text-white text-base border-b border-slate-850 pb-3 flex items-center gap-2"><i data-lucide="credit-card" class="h-5 w-5 text-accent"></i><span>Metode Bayar</span></h3>
                <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1" id="methodList"></div>
                <div class="space-y-2.5 pt-4 border-t border-slate-850">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Tambah Metode Baru</label>
                    <div class="flex gap-2">
                        <input type="text" id="newPayName" placeholder="Contoh: Gopay, BCA Mandiri" class="flex-1 bg-slate-955 border border-slate-800 hover:border-slate-750 focus:border-accent rounded-xl px-3 py-2 text-xs text-white focus:outline-none transition-all">
                        <button type="button" onclick="addMethod()" class="bg-accent hover:bg-accent-hover text-white p-2 rounded-xl transition-all" title="Tambah"><i data-lucide="plus" class="h-4.5 w-4.5"></i></button>
                    </div>
                </div>
                <div class="pt-4 border-t border-slate-850">
                    <button type="submit" class="w-full py-3 bg-accent hover:bg-accent-hover text-white font-bold rounded-xl transition-all duration-200 shadow-lg hover:-translate-y-0.5 text-sm">Simpan Pengaturan</button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    let methods = @json($settings['payment_methods']);

    function darken(hex, percent) {
        hex = hex.replace(/^#/, '');
        if (hex.length === 3) hex = hex.replace(/(.)/g, '$1$1');
        let r = parseInt(hex.substr(0,2),16), g = parseInt(hex.substr(2,2),16), b = parseInt(hex.substr(4,2),16);
        r = Math.max(0, Math.min(255, Math.floor(r*(1-percent/100))));
        g = Math.max(0, Math.min(255, Math.floor(g*(1-percent/100))));
        b = Math.max(0, Math.min(255, Math.floor(b*(1-percent/100))));
        return '#' + [r,g,b].map(x => x.toString(16).padStart(2,'0')).join('');
    }
    function applyAccent(font, bg, hover) {
        const root = document.documentElement.style;
        root.setProperty('--accent-font', font);
        root.setProperty('--accent-bg', bg);
        root.setProperty('--accent-hover-bg', hover);
    }

    function clearPresetSelection() {
        document.querySelectorAll('.color-preset').forEach(b => {
            b.className = 'color-preset flex flex-col items-center justify-center p-3 rounded-xl border transition-all duration-200 cursor-pointer border-slate-850 bg-slate-950 hover:border-slate-800';
            const chk = b.querySelector('.preset-check'); if (chk) chk.classList.add('hidden');
        });
    }
    function markPreset(key) {
        const b = document.querySelector(`.color-preset[data-color-key="${key}"]`);
        if (b) { b.className = 'color-preset flex flex-col items-center justify-center p-3 rounded-xl border transition-all duration-200 cursor-pointer border-accent bg-accent/10 shadow-lg scale-105'; const chk = b.querySelector('.preset-check'); if (chk) chk.classList.remove('hidden'); }
    }
    function selectColor(key, accent, hover) {
        document.getElementById('f_theme_color').value = key;
        clearPresetSelection(); markPreset(key);
        document.getElementById('customColorBox').classList.add('hidden');
        applyAccent(accent, accent, hover);
    }
    function selectColorCustom() {
        document.getElementById('f_theme_color').value = 'custom';
        clearPresetSelection(); markPreset('custom');
        document.getElementById('customColorBox').classList.remove('hidden');
        const font = document.getElementById('f_theme_color_font').value;
        const bg = document.getElementById('f_theme_color_bg').value;
        applyAccent(font, bg, darken(bg, 12));
    }
    function onCustomFont(v) {
        document.getElementById('f_theme_color_font').value = v;
        document.getElementById('pick_font').value = v; document.getElementById('text_font').value = v;
        if (document.getElementById('f_theme_color').value === 'custom') document.documentElement.style.setProperty('--accent-font', v);
    }
    function onCustomBg(v) {
        document.getElementById('f_theme_color_bg').value = v;
        document.getElementById('pick_bg').value = v; document.getElementById('text_bg').value = v;
        if (document.getElementById('f_theme_color').value === 'custom') {
            document.documentElement.style.setProperty('--accent-bg', v);
            document.documentElement.style.setProperty('--accent-hover-bg', darken(v, 12));
        }
    }

    function selectBg(key, color) {
        document.getElementById('f_theme_bg').value = key;
        document.querySelectorAll('.bg-preset').forEach(b => {
            b.className = 'bg-preset flex items-center gap-3 p-2.5 rounded-xl border text-left transition-all duration-200 border-slate-850 bg-slate-950 hover:border-slate-800';
            const c = b.querySelector('.bg-check'); if (c) c.classList.add('hidden');
        });
        const sel = document.querySelector(`.bg-preset[data-bg-key="${key}"]`);
        if (sel) { sel.className = 'bg-preset flex items-center gap-3 p-2.5 rounded-xl border text-left transition-all duration-200 border-accent bg-accent/5 shadow-lg scale-102'; const c = sel.querySelector('.bg-check'); if (c) c.classList.remove('hidden'); }
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.style.setProperty('--background', color);
            document.body.style.backgroundColor = color;
        }
    }

    function handleLogoUpload(e) {
        const file = e.target.files[0]; if (!file) return;
        if (!file.type.startsWith('image/')) { alert('Format berkas harus berupa gambar.'); return; }
        const reader = new FileReader();
        reader.onload = ev => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const MAX = 256; let w = img.width, h = img.height;
                if (w > h) { if (w > MAX) { h *= MAX/w; w = MAX; } } else { if (h > MAX) { w *= MAX/h; h = MAX; } }
                canvas.width = w; canvas.height = h; ctx.drawImage(img, 0, 0, w, h);
                const data = canvas.toDataURL('image/jpeg', 0.85);
                document.getElementById('f_logo_url').value = data;
                document.getElementById('logoPreview').innerHTML = `<img src="${data}" alt="Logo" class="w-full h-full object-contain">`;
                document.getElementById('removeLogoBtn').classList.remove('hidden');
            };
            img.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    }
    function removeLogo() {
        document.getElementById('f_logo_url').value = '';
        document.getElementById('logoPreview').innerHTML = '<span class="text-slate-600 text-[10px] text-center leading-tight p-1">No Logo</span>';
        document.getElementById('removeLogoBtn').classList.add('hidden');
    }

    // ---- Payment methods ----
    function renderMethods() {
        const list = document.getElementById('methodList');
        list.innerHTML = methods.map((m, i) => {
            const rek = m.no_rek || '';
            const qris = m.qris || '';
            const key = ((m.nama || '') + ' ' + (m.id || '')).toLowerCase();
            const isCash = /tunai|cash/.test(key);
            const isTransfer = /transfer|bank/.test(key);
            const showRek = !isCash;          // cash needs no number
            const showQr = !isCash && !isTransfer; // QR only for QRIS / e-wallet

            const rekBlock = showRek ? `
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">No. Rekening / Nomor</label>
                    <input type="text" name="payment_methods[${i}][no_rek]" value="${esc(rek)}" oninput="setMethodRek(${i}, this.value)" placeholder="${isTransfer ? 'cth: BCA 1234567890 a.n. Toko' : 'cth: 0812-3456-7890'}" class="w-full bg-slate-950 border border-slate-850 hover:border-slate-800 focus:border-accent rounded-lg px-3 py-2 text-xs text-white placeholder-slate-650 focus:outline-none transition-all font-mono">
                </div>` : '';

            const qrBlock = showQr ? `
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Barcode QRIS</label>
                    <div class="flex items-center gap-2 bg-slate-950 border border-slate-850 rounded-lg p-2">
                        <div class="w-12 h-12 bg-white/5 border border-slate-850 rounded flex items-center justify-center overflow-hidden shrink-0">
                            ${qris ? `<img src="${qris}" alt="QRIS" class="w-full h-full object-contain bg-white">` : `<span class="text-slate-600 text-[8px] text-center leading-tight">No QR</span>`}
                        </div>
                        <label class="inline-flex items-center px-2.5 py-1.5 bg-slate-950 hover:bg-slate-850 border border-slate-850 rounded-lg text-[11px] font-semibold text-slate-350 hover:text-white cursor-pointer transition-all">
                            <span>${qris ? 'Ganti' : 'Unggah QR'}</span>
                            <input type="file" accept="image/*" onchange="uploadMethodQris(${i}, this)" class="hidden">
                        </label>
                        ${qris ? `<button type="button" onclick="removeMethodQris(${i})" class="px-2 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-450 text-[11px] font-bold rounded-lg transition-all">Hapus</button>` : ''}
                    </div>
                </div>
                <input type="hidden" name="payment_methods[${i}][qris]" value="${esc(qris)}">` : '';

            const cashNote = isCash ? `<p class="text-[10px] text-slate-550 italic">Pembayaran tunai — tanpa rekening / QR.</p>` : '';

            return `
            <div class="p-3 rounded-xl border transition-all space-y-3 ${m.aktif ? 'bg-slate-950 border-slate-850' : 'bg-slate-950/40 border-slate-900 opacity-60'}">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-slate-200">${esc(m.nama)}</span>
                        <span class="text-[9px] text-slate-550 font-mono">id: ${esc(m.id)}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="toggleMethod(${i})" class="p-1 hover:bg-slate-800 rounded-lg transition-colors text-slate-400" title="${m.aktif ? 'Nonaktifkan' : 'Aktifkan'}">
                            <i data-lucide="${m.aktif ? 'toggle-right' : 'toggle-left'}" class="h-6 w-6 ${m.aktif ? 'text-accent' : 'text-slate-500'}"></i>
                        </button>
                        <button type="button" onclick="deleteMethod(${i})" class="p-1 hover:bg-rose-500/10 text-slate-500 hover:text-rose-450 rounded-lg transition-colors" title="Hapus"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                    </div>
                </div>
                ${rekBlock}
                ${qrBlock}
                ${cashNote}
                <input type="hidden" name="payment_methods[${i}][id]" value="${esc(m.id)}">
                <input type="hidden" name="payment_methods[${i}][nama]" value="${esc(m.nama)}">
                <input type="hidden" name="payment_methods[${i}][aktif]" value="${m.aktif ? 1 : 0}">
            </div>`;
        }).join('');
        renderIcons();
    }
    function addMethod() {
        const name = document.getElementById('newPayName').value.trim();
        if (!name) return;
        if (methods.find(m => m.nama.toLowerCase() === name.toLowerCase())) { alert('Metode pembayaran ini sudah terdaftar.'); return; }
        const id = name.toLowerCase().replace(/[^a-z0-9]/g, '-') || ('custom-' + Date.now());
        methods.push({ id, nama: name, aktif: true, no_rek: '', qris: '' });
        document.getElementById('newPayName').value = '';
        renderMethods();
    }
    function toggleMethod(i) { methods[i].aktif = !methods[i].aktif; renderMethods(); }
    function deleteMethod(i) {
        const remaining = methods.filter((m, idx) => idx !== i && m.aktif).length;
        if (remaining === 0) { alert('Harus ada minimal satu metode pembayaran aktif.'); return; }
        methods.splice(i, 1); renderMethods();
    }
    function setMethodRek(i, val) { methods[i].no_rek = val; } // update model only (no re-render → keeps focus)
    function uploadMethodQris(i, input) {
        const file = input.files[0]; if (!file) return;
        if (!file.type.startsWith('image/')) { alert('Format berkas harus berupa gambar.'); return; }
        compressImage(file, 512, (dataUrl) => { methods[i].qris = dataUrl; renderMethods(); });
    }
    function removeMethodQris(i) { methods[i].qris = ''; renderMethods(); }
    function compressImage(file, maxSize, cb) {
        const reader = new FileReader();
        reader.onload = ev => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let w = img.width, h = img.height;
                if (w > h) { if (w > maxSize) { h *= maxSize / w; w = maxSize; } }
                else { if (h > maxSize) { w *= maxSize / h; h = maxSize; } }
                canvas.width = w; canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, w, h); // white bg keeps QR scannable
                ctx.drawImage(img, 0, 0, w, h);
                cb(canvas.toDataURL('image/jpeg', 0.92));
            };
            img.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    }

    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

    renderMethods();
</script>
@endpush
@endsection
