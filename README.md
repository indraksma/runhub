# RunHub

Aplikasi pendaftaran single-event race untuk satu organizer, dibangun dengan Laravel 13, Blade, MySQL, database queue, dan file storage publik.

## Menjalankan aplikasi

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan queue:work
```

Pastikan database MySQL bernama `runners` tersedia dan konfigurasi `.env` sudah sesuai. Di Laragon, situs dapat dibuka melalui `http://runners.test`.

## Akun demo

- Admin: `admin@runhub.test` / `password`
Segera ganti kata sandi akun demo pada environment produksi.

## Fitur

- Pendaftaran guest tanpa akun dengan akses ulang memakai invoice dan email
- Satu event aktif, kategori dan tier dengan kuota opsional, serta QRIS per event
- Wizard pendaftaran mobile-first dan jersey kondisional per kategori
- Reservasi kuota dengan transaction dan row locking
- Upload bukti pembayaran serta upload ulang setelah ditolak
- Approval/rejection admin, BIB otomatis, teks WhatsApp siap salin
- Email invoice PDF, konfirmasi pembayaran/racepack, dan audit `notification_logs`
- Manajemen pendaftar dengan filter serta ekspor Excel/PDF
- Abstraksi `PaymentService` yang siap diperluas untuk gateway
- Duplikasi event beserta kategori, tier, dan tujuan pembayaran
- Dashboard riwayat peserta dan dashboard organizer

## Pengujian

Jika ekstensi SQLite aktif:

```bash
php artisan test
```

Pada instalasi PHP Laragon saat proyek ini dibuat, ekstensi SQLite tersedia tetapi belum diaktifkan di `php.ini`. Tes dapat dijalankan langsung dengan:

```bash
php -d extension=php_pdo_sqlite.dll -d extension=php_sqlite3.dll vendor/bin/phpunit
```

Feature test mencakup harga tier aktif, submit pendaftaran, penguncian kuota penuh, dan perubahan status/BIB setelah pembayaran disetujui.

## Catatan paket admin

Kode menyediakan control room admin yang fungsional. Pemasangan Filament/Livewire sempat terhalang kegagalan DNS lokal menuju `repo.packagist.org`; setelah DNS normal, paket dapat ditambahkan dengan:

```bash
composer require filament/filament:"^5.0" -W
php artisan filament:install --panels
```
