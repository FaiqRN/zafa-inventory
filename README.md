# ZafaSys

ZafaSys adalah aplikasi manajemen operasional distribusi berbasis Laravel untuk mengelola data master, transaksi, follow up pelanggan via WhatsApp, dan optimasi inventori (EOQ, Safety Stock, ROP) secara terpusat.

## Fitur Utama

- Autentikasi, session timeout, dan proteksi akses berbasis middleware.
- Manajemen role dan permission menggunakan Spatie Permission.
- Master data: Barang, Toko (dengan koordinat/geocoding), Customer, Barang Toko.
- Transaksi: Pemesanan, Pengiriman, dan Retur.
- Follow up pelanggan dengan integrasi WhatsApp (Wablas).
- Dashboard Inventory Optimization dan Partner Performance.
- Pengaturan EOQ dan Z-score per toko/barang.
- Pengaturan notifikasi dan utilitas cache refresh untuk performa.
- Otomasi terjadwal untuk sinkronisasi follow up dan data aktif Z-score.

## Stack Teknologi

- PHP 8.5.5+
- Laravel 13
- MySQL/MariaDB (direkomendasikan), atau SQLite untuk pengembangan sederhana
- Vite 8 + Tailwind CSS 4
- Laravel AdminLTE
- Spatie Laravel Permission
- Yajra DataTables
- Laravel Excel (Maatwebsite)
- Redis client (predis/phpredis) tersedia opsional

## Prasyarat

Pastikan perangkat pengembangan sudah memiliki:

- PHP sesuai requirement `composer.json`
- Composer 2+
- Node.js 20+ dan npm 10+
- Database server (MySQL/MariaDB direkomendasikan)
- Ekstensi PHP yang umum untuk Laravel (mbstring, openssl, pdo, tokenizer, xml, ctype, json)

## Instalasi Cepat

1. Clone repository lalu masuk ke folder proyek.
2. Install dependency backend:

```bash
composer install
```

3. Salin file environment:

```bash
cp .env.example .env
```

4. Isi konfigurasi utama di `.env` (database, URL aplikasi, dan integrasi pihak ketiga).
5. Generate app key:

```bash
php artisan key:generate
```

6. Jalankan migrasi dan seeder dasar:

```bash
php artisan migrate --seed
```

7. Install dependency frontend:

```bash
npm install
```

8. Jalankan Vite (development) atau build aset produksi:

```bash
npm run dev
# atau
npm run build
```

9. Buat symbolic link storage publik:

```bash
php artisan storage:link
```

10. Jalankan aplikasi:

```bash
php artisan serve
```

## Alternatif Script Siap Pakai

Project ini menyediakan script Composer:

```bash
composer run setup
composer run dev
```

- `setup` akan menjalankan install, generate key, migrate, install npm, dan build aset.
- `dev` akan menjalankan server, queue listener, log tail (`pail`), dan Vite secara paralel.

## Konfigurasi Environment Penting

Berikut variabel `.env` yang umum dipakai:

| Kategori | Variabel |
| --- | --- |
| Aplikasi | `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL` |
| Database | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Session/Cache/Queue | `SESSION_DRIVER`, `SESSION_LIFETIME`, `CACHE_STORE`, `QUEUE_CONNECTION` |
| Redis (opsional) | `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` |
| WhatsApp Wablas | `WABLAS_API_URL`, `WABLAS_TOKEN`, `WABLAS_DEVICE_ID`, `APP_ADMIN_PHONE` |
| Follow up upload | `MAX_IMAGE_SIZE`, `ALLOWED_IMAGE_FORMATS` |
| Maps/Geocoding | `GOOGLE_MAPS_API_KEY`, `GEOCODING_USER_AGENT`, `GEOCODING_TIMEOUT` |

Catatan:

- Jangan commit token/secret produksi ke repository.
- Untuk fitur follow up WhatsApp, `WABLAS_TOKEN` wajib terisi.

## Seeder Awal dan Akun Default

Seeder default (`DatabaseSeeder`) menjalankan:

- `RoleSeeder`
- `UserSeeder`
- `MigrateRolesToSpatieSeeder`
- `BarangSeeder`

Akun admin default dari seeder:

- Username: `admin`
- Password: `admin123`

Segera ubah password setelah login pertama.

Seeder tambahan (opsional) tersedia di folder `database/seeders` seperti `TokoSeeder`, `PengirimanSeeder`, `SsZscoreSettingSeeder`, dan lainnya.

## Scheduler dan Otomasi

Aplikasi memiliki beberapa pekerjaan terjadwal (misalnya sinkronisasi status follow up, cleanup data, health check, dan sinkronisasi pasangan aktif Z-score).

Untuk menjalankan scheduler:

```bash
php artisan schedule:work
```

Atau gunakan cron (Linux):

```bash
* * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
```

Timezone scheduler diset ke `Asia/Jakarta`.

## Perintah Artisan Penting

Contoh command operasional yang tersedia:

```bash
php artisan followup:sync-status --days=7 --status=sent --limit=100
php artisan followup:cleanup --days=90
php artisan whatsapp:debug --check-config
php artisan whatsapp:debug --check-device
php artisan zscore:sync-active-pairs --days=180
php artisan cache:refresh-barang
php artisan cache:refresh-toko
php artisan cache:refresh-pengiriman
php artisan cache:refresh-retur
php artisan login:cleanup --days=7
php artisan user:diagnose admin
php artisan user:fix-admin admin
```

## Pengujian

Jalankan test dengan:

```bash
php artisan test
```

Atau via Composer script:

```bash
composer test
```

## Struktur Singkat Modul

- `app/Http/Controllers`: alur request fitur utama.
- `app/Services`: logika bisnis dan integrasi (termasuk perhitungan dashboard).
- `app/Helpers`: helper domain/master data.
- `app/Console/Commands`: command maintenance dan operasional.
- `resources/views`: antarmuka berbasis Blade.
- `routes/web.php`: definisi route web aplikasi.

## Troubleshooting Singkat

- Menu tidak muncul sesuai role:
	- Jalankan `php artisan user:diagnose admin` dan `php artisan user:fix-admin admin`.
- Follow up WhatsApp gagal:
	- Cek `WABLAS_TOKEN` dan jalankan `php artisan whatsapp:debug --check-config`.
- Data dashboard belum muncul:
	- Pastikan data transaksi cukup, setting EOQ/Z-score ada, lalu coba `php artisan zscore:sync-active-pairs`.
- Perubahan aset tidak ter-update:
	- Jalankan ulang `npm run dev` atau `npm run build`.

## Lisensi

Mengikuti lisensi proyek pada repository ini.
