# Checklist Deploy — LaundryPro (Laravel 12 / PHP 8.2)

Panduan ringkas & aman untuk mendeploy/update aplikasi ke server.
Database = **satu file SQLite** (`database/database.sqlite`). Yang berbahaya **bukan** `git pull`,
melainkan perintah migrasi yang salah. Ikuti urutan di bawah.

> **Aturan emas:** SELALU backup `database/database.sqlite` sebelum menyentuh apa pun di server.

---

## 0) Prasyarat server (cek sekali di awal)

- [ ] **PHP ≥ 8.2** — wajib sejak upgrade Laravel 12. Cek: `php -v`
- [ ] Ekstensi PHP aktif: `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `fileinfo`. Cek: `php -m`
- [ ] **Composer 2** terpasang. Cek: `composer --version`
- [ ] Folder writable oleh web server: `storage/` dan `bootstrap/cache/`
- [ ] (Jika pakai web server) document root mengarah ke **`public/`**, bukan root project

---

## 1) Pre-deploy — BACKUP & cek

```bash
# Backup database (WAJIB)
cp database/database.sqlite "database/backups/db-$(date +%Y-%m-%d-%H%M).sqlite"

# Catat versi yang sedang jalan (untuk rollback bila perlu)
php artisan --version
git rev-parse --short HEAD
```

- [ ] Database sudah dibackup ke `database/backups/` (folder ini gitignored)
- [ ] Backup disalin ke tempat aman (di luar server) bila memungkinkan

---

## 2) Deploy / Update kode

```bash
# 1. Ambil kode terbaru
git fetch origin
git checkout main
git pull origin main

# 2. Install dependency PRODUKSI (tanpa dev, autoloader teroptimasi)
composer install --no-dev --optimize-autoloader

# 3. Pastikan .env produksi benar (lihat bagian .env di bawah)

# 4. Jalankan migrasi BARU saja — AMAN, tidak menghapus data
php artisan migrate --force

# 5. Bersihkan + cache ulang konfigurasi/route/view
php artisan optimize:clear
php artisan optimize        # cache config + route + view sekaligus
```

- [ ] `composer install --no-dev` sukses tanpa error
- [ ] `php artisan migrate --force` jalan (untuk update ini hanya menambah kolom `poin_awarded`; aman)
- [ ] `php artisan optimize` sukses

> **JANGAN** menjalankan `migrate:fresh`, `migrate:refresh`, `migrate:reset`, atau `db:wipe`
> di server yang sudah berisi data — semuanya **menghapus data**.

---

## 3) `.env` produksi (pastikan nilai ini)

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`  ← penting untuk keamanan (jangan `true` di produksi)
- [ ] `APP_URL=https://domain-anda`  ← agar QR pendaftaran pelanggan menunjuk URL benar
- [ ] `APP_KEY` terisi (jika baru: `php artisan key:generate`)
- [ ] `DB_CONNECTION=sqlite`
- [ ] `SESSION_DRIVER=file`, `CACHE_DRIVER=file`, `QUEUE_CONNECTION=sync` (default app ini — tanpa Redis/worker)

> Karena `QUEUE_CONNECTION=sync` dan cache/session berbasis file, **tidak perlu** queue worker
> atau Redis. Tidak ada proses background yang harus dijalankan.

---

## 4) Verifikasi setelah deploy (smoke test)

```bash
php artisan about | head -20      # konfirmasi Laravel 12 + PHP 8.2 + env production
```

Cek manual di browser:
- [ ] `/` mengarah (redirect) ke `/login`
- [ ] Halaman `/login` tampil normal
- [ ] Login super admin berhasil → dashboard monitoring tampil
- [ ] Login member berhasil → dashboard laundry + daftar order tampil (pagination jalan)
- [ ] Buat 1 order uji → status & pembayaran berfungsi → **hapus order uji**
- [ ] Halaman `/forgot-password` menampilkan info "Hubungi Admin" (bukan form reset)
- [ ] QR pendaftaran (`/daftar/{id}`) terbuka dan memakai `APP_URL` yang benar

Perilaku keamanan yang harus aktif (hasil perbaikan review):
- [ ] Salah login 5× berturut → akun terkunci sementara (pesan hitung mundur)
- [ ] Poin loyalitas hanya bertambah saat order **lunas**, tidak saat order dibuat

---

## 5) Rollback (bila ada masalah)

```bash
# 1. Kembalikan kode ke versi sebelumnya
git checkout <commit-lama>        # short hash yang dicatat di langkah 1

# 2. Kembalikan dependency versi lama
composer install --no-dev --optimize-autoloader

# 3. Kembalikan database dari backup
cp "database/backups/db-<tanggal>.sqlite" database/database.sqlite

# 4. Bersihkan cache
php artisan optimize:clear
```

- [ ] Kode, dependency, dan database dikembalikan ke titik sebelum deploy
- [ ] Smoke test ulang

---

## Catatan khusus upgrade Laravel 9 → 12 (deploy pertama setelah upgrade)

- Server **harus** sudah PHP 8.2+ SEBELUM `composer install` (Laravel 12 tidak jalan di PHP < 8.2).
- Hanya ada **1 migrasi baru**: `2024_01_08_000000_add_poin_awarded_to_orders` (menambah kolom, aman).
  Migrasi ini juga menandai semua order lama `poin_awarded = true` agar poin tidak diberikan ganda.
- Tidak ada perubahan struktur folder (skeleton klasik dipertahankan) — tidak ada langkah migrasi konfigurasi khusus.
- Jika sebelumnya pernah `config:cache`, jalankan `php artisan config:clear` dulu sebelum cache ulang.
```bash
php -v                              # pastikan 8.2.x atau lebih baru — STOP bila masih 8.0/8.1
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
```
