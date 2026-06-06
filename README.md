# LaundryPro — Aplikasi Manajemen Laundry (Laravel, Multi-Tenant)

Aplikasi manajemen laundry berbasis **Laravel 9 + Blade + HTML/CSS/JS native (tanpa Vite)**. Styling pakai **Tailwind CSS via CDN** + ikon **Lucide via CDN** (tanpa `npm`/build).

Model **multi-tenant / SaaS**:
- **Super Admin** (pemilik aplikasi): dashboard monitoring semua member, kelola member & langganan/sewa, pengaturan platform (logo, nama, tema), akun.
- **Member** (penyewa): tiap member punya data laundry **terpisah** — dashboard + laporan keuangan, order (status, edit, batal, struk thermal 58/80mm, WA), pelanggan & layanan, pengeluaran/laba bersih, QR pendaftaran pelanggan, tema/branding sendiri.
- Pelanggan bisa **self-register + order via QR** per member (`/daftar/{member}`).

## Stack
- PHP 8.0 (XAMPP) + Laravel 9
- Database **SQLite** (`database/database.sqlite`) — tanpa setup server DB
- Tailwind CDN + Lucide CDN (tanpa Vite/npm)

## Menjalankan (lokal)
1. Pastikan XAMPP terpasang (PHP di `C:\xampp\php`).
2. **Double-click `start.bat`** (atau `php artisan serve`).
3. Buka **http://127.0.0.1:8000**

**Akun default (seeder fresh):** Super Admin `admin` / `admin123` · Member demo `demo` / `demo123`. **Ganti password setelah login pertama.**

---

## ⚠️ BACKUP DATABASE (lakukan sebelum deploy/update apa pun!)
Database = satu file SQLite. Backup-nya cukup copy file:
```bash
# Windows (CMD):  copy database\database.sqlite database\backups\db-backup.sqlite
# Bash:
cp database/database.sqlite "database/backups/db-$(date +%Y-%m-%d).sqlite"
```
> Folder `database/backups/` sudah di-gitignore (tidak ikut ke git). Simpan backup di tempat aman.
> **Restore:** tinggal copy file backup kembali jadi `database/database.sqlite`.

---

## Deploy / Update — APAKAH DATA HILANG?
**Push kode TIDAK menghapus data.** File `database/database.sqlite` di-gitignore, jadi `git push`/`pull` tidak menyentuh data. Yang berisiko adalah **perintah migrasi** yang kamu jalankan:

| Perintah | Efek |
|---|---|
| ✅ `php artisan migrate` | Hanya jalankan migrasi **baru** (mostly nambah kolom). **Aman** untuk update server yang sudah ada data. |
| ❌ `php artisan migrate:fresh` / `migrate:fresh --seed` | **DROP semua tabel + data.** JANGAN dijalankan di server yang sudah ada data. |
| ⚠️ `db:wipe`, `migrate:reset/refresh` | Juga menghapus data. Hindari di produksi. |

### A. Server BARU (belum ada data)
```bash
git clone <repo> && cd <repo>
php E:\laundry-build-tools\composer.phar install --no-dev --optimize-autoloader
copy .env.example .env          # set APP_ENV=production, APP_DEBUG=false, APP_URL, DB_CONNECTION=sqlite
php artisan key:generate
type nul > database\database.sqlite      # (bash: touch database/database.sqlite)
php artisan migrate --force --seed       # seed boleh: belum ada data
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### B. UPDATE server yang SUDAH dipakai pelanggan (data penting)
```bash
# 1) BACKUP DULU
cp database/database.sqlite database/backups/db-$(date +%Y-%m-%d).sqlite
# 2) Ambil kode terbaru
git pull
php E:\laundry-build-tools\composer.phar install --no-dev --optimize-autoloader
# 3) Migrasi BARU saja (TANPA fresh, TANPA --seed)
php artisan migrate --force
# 4) Refresh cache
php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
```
> Catatan multi-tenant: jika server lama berisi data dari versi **sebelum multi-tenant**, datanya tidak terhapus tapi `user_id`-nya kosong sehingga belum tampil di member mana pun — perlu di-assign ke satu member dulu.

> Data di laptop (`E:\laundry-laravel`) adalah file SQLite terpisah — **tidak tersentuh** saat deploy ke server lain.

## Perintah berguna
```bash
php artisan serve --port=8080        # server di port lain
php artisan migrate --force          # jalankan migrasi baru (AMAN, tidak hapus data)
# php artisan migrate:fresh --seed   # HATI-HATI: reset total (hapus semua data) — hanya untuk dev/baru
```
> `php` = `C:\xampp\php\php.exe`. Composer = `php E:\laundry-build-tools\composer.phar ...`.

## Struktur (ringkas)
- `app/Models/Concerns/BelongsToTenant.php` — isolasi data per member (global scope + auto user_id)
- `app/Http/Middleware` — `EnsureSuperAdmin`, `EnsureMember`, `CheckSubscription`
- `app/Http/Controllers` — Dashboard (member + monitor super admin), Order/Customer/Service/Expense/Setting (member), Member/Registration/MemberSignup/Auth/Subscription/Landing
- `resources/views` — `landing`, `auth`, `superadmin/{monitor,members,settings}`, `langganan`, `member`, `register`, `dashboard`, `orders/*`, `customers/*`, `services/*`, `expenses/*`, `settings/*`
