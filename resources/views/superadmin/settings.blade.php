@extends('layouts.app')

@section('content')
@php $b = $settings['branding']; $logoUrl = $b['logo_url'] ?? null; @endphp

<div class="space-y-8 pb-10 max-w-4xl">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2"><i data-lucide="settings" class="h-8 w-8 text-accent"></i><span>Pengaturan</span></h1>
        <p class="text-slate-400 text-sm mt-1">Atur identitas aplikasi (logo &amp; nama), tema, dan akun super admin Anda.</p>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-2 animate-in"><i data-lucide="check" class="h-5 w-5 shrink-0"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-2"><i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i><span>{{ $errors->first() }}</span></div>
    @endif

    <!-- ===== Identitas & Tema Platform ===== -->
    <form method="POST" action="{{ route('platform.settings.save') }}" class="space-y-8">
        @csrf
        <input type="hidden" name="theme_color" id="f_theme_color" value="{{ $settings['theme_color'] }}">
        <input type="hidden" name="theme_color_font" id="f_theme_color_font" value="{{ $settings['theme_color_font'] }}">
        <input type="hidden" name="theme_color_bg" id="f_theme_color_bg" value="{{ $settings['theme_color_bg'] }}">
        <input type="hidden" name="theme_bg" id="f_theme_bg" value="{{ $settings['theme_bg'] }}">
        <input type="hidden" name="logo_url" id="f_logo_url" value="{{ $logoUrl }}">

        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h3 class="font-bold text-white text-base border-b border-slate-850 pb-3 flex items-center gap-2"><span class="w-2 h-5 bg-accent rounded-full"></span><span>Logo &amp; Nama Aplikasi</span></h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Logo (Kustom)</label>
                    <div class="flex items-center gap-3 bg-slate-950/40 p-3 border border-slate-850/50 rounded-xl">
                        <div id="logoPreview" class="w-12 h-12 bg-slate-950 border border-slate-850 rounded-lg flex items-center justify-center overflow-hidden shrink-0">
                            @if($logoUrl)<img src="{{ $logoUrl }}" alt="Logo" class="w-full h-full object-contain">@else<span class="text-slate-600 text-[10px] text-center p-1">No Logo</span>@endif
                        </div>
                        <div class="flex-1 flex flex-col items-start gap-1">
                            <label class="inline-flex items-center px-3 py-1.5 bg-slate-950 hover:bg-slate-850 border border-slate-850 rounded-lg text-xs font-semibold text-slate-350 hover:text-white cursor-pointer transition-all"><span>Pilih Berkas</span><input type="file" accept="image/*" onchange="handleLogoUpload(event)" class="hidden"></label>
                            <p class="text-[9px] text-slate-550">Maks. 256x256px, dikompres otomatis.</p>
                        </div>
                        <button type="button" id="removeLogoBtn" onclick="removeLogo()" class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-450 text-xs font-bold rounded-lg transition-all {{ $logoUrl ? '' : 'hidden' }}">Hapus</button>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Logo Emoji (Fallback)</label>
                    <input type="text" name="logo_emoji" maxlength="2" value="{{ $b['logo_emoji'] }}" placeholder="🧺" class="w-16 bg-slate-950 border border-slate-850 hover:border-slate-800 focus:border-accent rounded-xl px-2 py-3 text-center text-2xl focus:outline-none transition-all">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Aplikasi / Platform</label>
                <input type="text" name="nama_laundry" value="{{ $b['nama_laundry'] }}" placeholder="cth: BtiveSolution" class="w-full bg-slate-950 border border-slate-850 hover:border-slate-800 focus:border-accent rounded-xl px-4 py-2.5 text-white focus:outline-none transition-all text-sm font-semibold">
                <p class="text-[10px] text-slate-550 mt-1.5">Nama & logo ini tampil di landing page, halaman login, dan sidebar Anda.</p>
            </div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
            <h3 class="font-bold text-white text-base border-b border-slate-850 pb-3 flex items-center gap-2"><span class="w-2 h-5 bg-accent rounded-full"></span><span>Warna Tema</span></h3>
            <div class="space-y-3">
                <h4 class="font-semibold text-white text-sm">Aksen Warna</h4>
                <div class="grid grid-cols-4 sm:grid-cols-9 gap-3">
                    @foreach($colorPresets as $key => $color)
                        <button type="button" data-color-key="{{ $key }}" onclick="selectColor('{{ $key }}','{{ $color['accent'] }}','{{ $color['hover'] }}')" class="color-preset flex flex-col items-center justify-center p-3 rounded-xl border transition-all cursor-pointer {{ $settings['theme_color'] === $key ? 'border-accent bg-accent/10 shadow-lg scale-105' : 'border-slate-850 bg-slate-950 hover:border-slate-800' }}">
                            <span class="w-8 h-8 rounded-full border border-black/30 flex items-center justify-center text-white" style="background-color: {{ $color['accent'] }}"><span class="preset-check {{ $settings['theme_color'] === $key ? '' : 'hidden' }}"><i data-lucide="check" class="h-4 w-4"></i></span></span>
                            <span class="text-[10px] mt-1.5 capitalize font-semibold text-slate-400">{{ $key === 'teal' ? 'default' : $key }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="border-t border-slate-850/80 pt-5 space-y-3 {{ ($settings['theme_mode'] ?? 'dark') === 'light' ? 'opacity-40 pointer-events-none' : '' }}">
                <h4 class="font-semibold text-white text-sm">Warna Latar (mode gelap)</h4>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    @foreach($bgPresets as $key => $bg)
                        <button type="button" data-bg-key="{{ $key }}" onclick="selectBg('{{ $key }}','{{ $bg['color'] }}')" class="bg-preset flex items-center gap-3 p-2.5 rounded-xl border text-left transition-all {{ $settings['theme_bg'] === $key ? 'border-accent bg-accent/5 shadow-lg' : 'border-slate-850 bg-slate-950 hover:border-slate-800' }}">
                            <span class="w-5 h-5 rounded-full border border-slate-700/80 shrink-0" style="background-color: {{ $bg['color'] }}"></span>
                            <span class="text-xs font-semibold text-slate-300 truncate">{{ explode(' (', $bg['label'])[0] }}</span>
                        </button>
                    @endforeach
                </div>
                <p class="text-[10px] text-slate-550">Mode terang/gelap diatur lewat tombol di sidebar.</p>
            </div>
            <div class="pt-2">
                <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-accent hover:bg-accent-hover text-white font-bold rounded-xl transition-all shadow-lg text-sm flex items-center justify-center gap-2"><i data-lucide="save" class="h-4 w-4"></i>Simpan Identitas &amp; Tema</button>
            </div>
        </div>
    </form>

    <!-- ===== Akun Super Admin ===== -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <h3 class="font-bold text-white text-base border-b border-slate-850 pb-3 mb-4 flex items-center gap-2"><i data-lucide="user-cog" class="h-5 w-5 text-accent"></i><span>Akun Saya</span></h3>
        <form method="POST" action="{{ route('members.profile') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-start">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Anda</label>
                <input type="text" name="name" value="{{ auth()->user()->name }}" placeholder="Nama" class="w-full bg-slate-950 border border-slate-800 focus:border-accent rounded-xl px-3 py-2 text-sm text-white focus:outline-none transition-all">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Username</label>
                <input type="text" name="username" value="{{ auth()->user()->username }}" class="w-full bg-slate-950 border border-slate-800 focus:border-accent rounded-xl px-3 py-2 text-sm text-white focus:outline-none transition-all font-mono">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Password Baru <span class="text-slate-600 normal-case font-normal">(opsional)</span></label>
                <div class="relative">
                    <input type="password" name="password" id="saPass" placeholder="Reset password (kosongkan jika tetap)" class="w-full bg-slate-950 border border-slate-800 focus:border-accent rounded-xl px-3 py-2 pr-10 text-sm text-white placeholder-slate-600 focus:outline-none transition-all">
                    <button type="button" onclick="togglePass('saPass', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300"><i data-lucide="eye" class="h-4 w-4"></i></button>
                </div>
            </div>
            <div class="sm:col-span-3 flex justify-end pt-1">
                <button type="submit" class="px-5 py-2.5 bg-accent hover:bg-accent-hover text-white font-semibold rounded-xl text-sm transition-all flex items-center gap-2"><i data-lucide="save" class="h-4 w-4"></i>Simpan Akun</button>
            </div>
        </form>
        <p class="text-[10px] text-slate-550 mt-3 flex items-start gap-1.5"><i data-lucide="info" class="h-3.5 w-3.5 shrink-0 mt-0.5"></i><span>Lupa password? Cukup isi "Password Baru" di sini untuk menggantinya, atau gunakan menu "Lupa password" di halaman login (berdasarkan username).</span></p>
    </div>
</div>

@push('scripts')
<script>
    function darken(hex, p){ hex=hex.replace(/^#/,''); if(hex.length===3)hex=hex.replace(/(.)/g,'$1$1'); let r=parseInt(hex.substr(0,2),16),g=parseInt(hex.substr(2,2),16),b=parseInt(hex.substr(4,2),16); r=Math.max(0,Math.floor(r*(1-p/100)));g=Math.max(0,Math.floor(g*(1-p/100)));b=Math.max(0,Math.floor(b*(1-p/100))); return '#'+[r,g,b].map(x=>x.toString(16).padStart(2,'0')).join(''); }
    function applyAccent(font,bg,hover){ const r=document.documentElement.style; r.setProperty('--accent-font',font); r.setProperty('--accent-bg',bg); r.setProperty('--accent-hover-bg',hover); }
    function selectColor(key, accent, hover){
        document.getElementById('f_theme_color').value=key;
        document.getElementById('f_theme_color_font').value=accent;
        document.getElementById('f_theme_color_bg').value=accent;
        document.querySelectorAll('.color-preset').forEach(b=>{ b.className='color-preset flex flex-col items-center justify-center p-3 rounded-xl border transition-all cursor-pointer border-slate-850 bg-slate-950 hover:border-slate-800'; const c=b.querySelector('.preset-check'); if(c)c.classList.add('hidden'); });
        const sel=document.querySelector(`.color-preset[data-color-key="${key}"]`); if(sel){ sel.className='color-preset flex flex-col items-center justify-center p-3 rounded-xl border transition-all cursor-pointer border-accent bg-accent/10 shadow-lg scale-105'; const c=sel.querySelector('.preset-check'); if(c)c.classList.remove('hidden'); }
        applyAccent(accent, accent, hover);
    }
    function selectBg(key, color){
        document.getElementById('f_theme_bg').value=key;
        document.querySelectorAll('.bg-preset').forEach(b=> b.className='bg-preset flex items-center gap-3 p-2.5 rounded-xl border text-left transition-all border-slate-850 bg-slate-950 hover:border-slate-800');
        const sel=document.querySelector(`.bg-preset[data-bg-key="${key}"]`); if(sel) sel.className='bg-preset flex items-center gap-3 p-2.5 rounded-xl border text-left transition-all border-accent bg-accent/5 shadow-lg';
        if(document.documentElement.classList.contains('dark')){ document.documentElement.style.setProperty('--background',color); document.body.style.backgroundColor=color; }
    }
    function handleLogoUpload(e){
        const file=e.target.files[0]; if(!file) return;
        if(!file.type.startsWith('image/')){ alert('Format harus gambar.'); return; }
        const reader=new FileReader();
        reader.onload=ev=>{ const img=new Image(); img.onload=()=>{ const c=document.createElement('canvas'); const MAX=256; let w=img.width,h=img.height; if(w>h){if(w>MAX){h*=MAX/w;w=MAX;}}else{if(h>MAX){w*=MAX/h;h=MAX;}} c.width=w;c.height=h; c.getContext('2d').drawImage(img,0,0,w,h); const data=c.toDataURL('image/jpeg',0.85); document.getElementById('f_logo_url').value=data; document.getElementById('logoPreview').innerHTML=`<img src="${data}" class="w-full h-full object-contain">`; document.getElementById('removeLogoBtn').classList.remove('hidden'); }; img.src=ev.target.result; };
        reader.readAsDataURL(file);
    }
    function removeLogo(){ document.getElementById('f_logo_url').value=''; document.getElementById('logoPreview').innerHTML='<span class="text-slate-600 text-[10px] text-center p-1">No Logo</span>'; document.getElementById('removeLogoBtn').classList.add('hidden'); }
    function togglePass(id, btn){ const inp=document.getElementById(id); const show=inp.type==='password'; inp.type=show?'text':'password'; btn.innerHTML=show?'<i data-lucide="eye-off" class="h-4 w-4"></i>':'<i data-lucide="eye" class="h-4 w-4"></i>'; if(window.lucide)lucide.createIcons(); }
</script>
@endpush
@endsection
