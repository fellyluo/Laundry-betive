# LaundryPro — Manajemen Laundry (Laravel)

Versi Laravel dari aplikasi Manajemen Laundry. Dibangun dengan **Laravel 9 + Blade + HTML/CSS/JS native (tanpa Vite)**. Styling memakai **Tailwind CSS via CDN** dan ikon **Lucide via CDN**, sehingga tidak perlu langkah build (`npm`/Vite) sama sekali.

Tampilan & fitur dibuat sama persis dengan versi Next.js: dashboard + laporan keuangan, order (buat/daftar/detail), alur status (diterima → diproses → selesai → diambil), pembayaran & cicilan, struk thermal 58/80mm, notifikasi WhatsApp, master pelanggan & layanan, pengaturan tema (warna aksen, latar, mode terang/gelap, logo, metode bayar), dan PWA.

## Stack
- PHP 8.0 (XAMPP) + Laravel 9
- Database: **SQLite** (`database/database.sqlite`) — tanpa setup server DB
- Tailwind CDN + Lucide CDN (tanpa Vite/npm)

## Cara menjalankan
1. Pastikan XAMPP terpasang (PHP di `C:\xampp\php`).
2. **Double-click `start.bat`** (atau jalankan `php artisan serve`).
3. Buka **http://127.0.0.1:8000**

Data awal (seeder) sudah berisi 7 layanan contoh, 3 pelanggan, dan pengaturan default.

## Setup dari clone baru (git)
`vendor/`, `.env`, dan file database SQLite tidak ikut di-commit. Setelah clone:
```bash
php E:\laundry-build-tools\composer.phar install   # pasang dependency
copy .env.example .env                              # buat .env
php artisan key:generate                            # generate APP_KEY
# pastikan DB_CONNECTION=sqlite di .env, lalu:
type nul > database\database.sqlite                 # buat file SQLite kosong (Windows)
php artisan migrate:fresh --seed                    # buat tabel + data contoh
```
Lalu jalankan `start.bat`.

## Perintah berguna
```bash
# Reset database ke kondisi awal + data contoh
php artisan migrate:fresh --seed

# Menjalankan server di port lain
php artisan serve --port=8080
```
> Catatan: `php` di sini = `C:\xampp\php\php.exe`. Composer dipanggil dengan `php E:\laundry-build-tools\composer.phar ...`.

## Struktur
- `app/Http/Controllers` — Dashboard, Order, Customer, Service, Setting
- `app/Models` — Customer, Service, Order, OrderItem, Payment, StatusLog, Setting
- `app/Support` — `Settings.php` (preset tema + helper), `helpers.php` (format rupiah/tanggal/WA)
- `resources/views` — `layouts/app`, `partials/navbar`, `dashboard`, `orders/*`, `customers/*`, `services/*`, `settings/*`
- `public/css/app.css` — global styles (aksen, mode terang, print thermal)
- `public/{manifest.json,sw.js,icon.svg}` — PWA
