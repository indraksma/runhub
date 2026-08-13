# ABBA — Running Event Registration

ABBA adalah aplikasi web untuk mengelola pendaftaran event lari dari publikasi event sampai verifikasi pembayaran peserta. Aplikasi ini dirancang untuk satu penyelenggara dengan satu event aktif (`published`) pada satu waktu.

Peserta dapat mendaftar tanpa membuat akun, memilih kategori lomba, memperoleh invoice, mengunggah bukti pembayaran, dan memeriksa status pendaftaran menggunakan nomor invoice serta email. Administrator memiliki panel untuk mengelola event, kategori, tier harga, tujuan pembayaran, peserta, dan verifikasi pembayaran.

## Fitur utama

### Peserta

- Melihat event aktif, detail acara, kategori lomba, harga, kuota, dan hitung mundur pembukaan pendaftaran.
- Mendaftar sebagai tamu melalui formulir bertahap tanpa membuat akun.
- Mendapatkan harga dari tier yang sedang aktif atau harga dasar kategori jika tidak ada tier aktif.
- Memilih ukuran jersey hanya pada kategori yang menyertakan jersey.
- Mendapatkan nomor invoice unik dan invoice PDF melalui email.
- Membayar melalui tujuan pembayaran statis, termasuk QRIS, lalu mengunggah bukti dalam format JPG, JPEG, PNG, atau PDF.
- Mengunggah ulang bukti pembayaran setelah ditolak.
- Memeriksa detail dan status pendaftaran menggunakan kombinasi invoice dan email.
- Menerima email ketika pendaftaran dibuat, pembayaran disetujui, atau pembayaran ditolak.

### Administrator

- Dashboard ringkasan event, jumlah peserta, peserta terverifikasi, dan pembayaran yang menunggu pemeriksaan.
- Membuat, mengubah, menghapus, dan menduplikasi event.
- Menyunting deskripsi event dengan rich-text editor dan unggahan gambar yang disanitasi.
- Mengelola banner event, informasi pengambilan racepack, periode pendaftaran, dan prefix BIB.
- Mengelola kategori lomba, jarak desimal, kuota opsional, harga dasar, jersey, serta prefix BIB per kategori.
- Mengelola tier harga berdasarkan periode dan kuota opsional.
- Mengelola tujuan pembayaran berupa QRIS atau informasi rekening/transfer.
- Menyetujui atau menolak bukti pembayaran disertai alasan.
- Membuat nomor BIB otomatis setelah pembayaran disetujui.
- Memfilter peserta berdasarkan event, kategori, status, atau kata kunci.
- Mengekspor data peserta terfilter ke Excel dan PDF.
- Mencatat hasil pengiriman email pada `notification_logs`.

## Alur pendaftaran

1. Admin memublikasikan event beserta kategori, tier harga, dan tujuan pembayaran.
2. Peserta mengisi data diri serta memilih kategori dan ukuran jersey jika diperlukan.
3. Sistem mengunci data kategori saat transaksi untuk mencegah kuota terlewati, menentukan harga aktif, lalu membuat invoice dan pembayaran berstatus `pending`.
4. Peserta mengunggah bukti pembayaran; status pendaftaran berubah menjadi `awaiting_verification`.
5. Admin menyetujui pembayaran untuk menghasilkan BIB dan status `verified`, atau menolaknya agar peserta dapat mengunggah bukti baru.

## Teknologi

- PHP 8.3+
- Laravel 13
- Blade dan Vite 8
- Tailwind CSS 4
- MySQL untuk aplikasi dan SQLite in-memory untuk pengujian
- Laravel database queue
- DomPDF untuk invoice dan ekspor PDF
- Laravel Excel untuk ekspor XLSX
- Trix untuk editor deskripsi event
- Symfony HTML Sanitizer untuk membersihkan HTML deskripsi

## Prasyarat

Pastikan perangkat telah memiliki:

- PHP 8.3 atau lebih baru beserta ekstensi yang dibutuhkan Laravel
- Composer
- Node.js dan npm
- MySQL atau MariaDB
- Ekstensi PHP SQLite jika ingin menjalankan test suite

## Instalasi

1. Instal dependency PHP dan JavaScript.

   ```bash
   composer install
   npm install
   ```

2. Salin konfigurasi environment dan buat application key.

   Pada Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

   Pada Linux/macOS:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Buat database MySQL bernama `runners`, kemudian sesuaikan bagian berikut di `.env` jika diperlukan.

   ```dotenv
   APP_URL=http://runners.test

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=runners
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. Buat tabel, isi data demo, dan hubungkan penyimpanan publik.

   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

5. Bangun aset frontend.

   ```bash
   npm run build
   ```

Sebagai alternatif, perintah berikut menangani instalasi dependency, pembuatan `.env`, application key, migrasi, dan build aset:

```bash
composer run setup
```

Setelah perintah tersebut selesai, jalankan seeder dan buat storage link secara terpisah:

```bash
php artisan db:seed
php artisan storage:link
```

> Jangan menjalankan seeder demo pada produksi tanpa terlebih dahulu mengganti atau menghapus kredensial admin bawaan.

## Menjalankan aplikasi

Untuk menjalankan web server Laravel, queue listener, log viewer, dan Vite secara bersamaan:

```bash
composer run dev
```

Aplikasi dapat dibuka melalui alamat yang ditampilkan oleh Laravel, biasanya `http://127.0.0.1:8000`. Jika menggunakan virtual host Laragon, gunakan `http://runners.test` sesuai nilai `APP_URL`.

Queue worker harus tetap berjalan agar email invoice dan perubahan status dapat dikirim. Jika komponen dijalankan secara terpisah, gunakan:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

## Konfigurasi email

Konfigurasi bawaan menggunakan `MAIL_MAILER=log`, sehingga email ditulis ke log aplikasi dan tidak benar-benar dikirim. Untuk pengiriman email nyata, ubah konfigurasi `MAIL_*` di `.env` sesuai layanan SMTP yang digunakan, misalnya:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Setelah mengubah konfigurasi, bersihkan cache dan mulai ulang queue worker:

```bash
php artisan config:clear
php artisan queue:restart
```

## Akun demo

Seeder membuat akun administrator berikut:

```text
Email    : admin@runhub.test
Password : password
```

Panel administrator tersedia di `/admin` setelah login melalui `/login`. Ganti kata sandi dan alamat email tersebut sebelum aplikasi digunakan di lingkungan publik.

Seeder juga membuat event contoh **Jakarta Sunrise Run**, kategori 5K dan 10K, tier Early Bird, serta tujuan pembayaran demo.

## Status utama

Status event yang tersedia adalah `draft`, `published`, `closed`, dan `archived`. Sistem hanya mengizinkan satu event berstatus `published` dalam satu waktu.

Siklus status pendaftaran dan pembayaran secara umum:

```text
pending_payment
      |
      | peserta mengunggah bukti
      v
awaiting_verification
      |
      +---- disetujui ----> verified + nomor BIB
      |
      +---- ditolak ------> rejected ----> unggah ulang bukti
```

## Pengujian dan kualitas kode

Jalankan seluruh test suite dengan:

```bash
composer test
```

Skrip tersebut membersihkan cache konfigurasi dan memuat ekstensi SQLite untuk instalasi PHP Laragon. Pada lingkungan yang telah mengaktifkan SQLite secara permanen, test juga dapat dijalankan dengan:

```bash
php artisan test
```

Pengujian fitur mencakup harga tier aktif, kuota kategori dan tier, aturan jersey, akses tamu, invoice PDF, email dan attachment, pembuatan BIB, ekspor admin, pembatasan satu event aktif, sanitasi deskripsi, dan tampilan hitung mundur.

Format kode PHP sebelum mengirim perubahan:

```bash
vendor/bin/pint
```

## Struktur proyek

```text
app/
├── Contracts/          Kontrak layanan pembayaran
├── Exports/            Ekspor data peserta
├── Http/               Controller, middleware, dan form request
├── Jobs/               Pekerjaan pengiriman email melalui queue
├── Mail/               Template dan konfigurasi email
├── Models/             Model Eloquent dan relasi domain
├── Policies/           Aturan otorisasi
└── Services/           Alur registrasi, pembayaran, dan sanitasi HTML
database/
├── migrations/         Definisi skema database
└── seeders/            Data administrator dan event demo
resources/
├── css/                Sumber gaya aplikasi
├── js/                 Sumber JavaScript
└── views/              Blade untuk halaman publik, admin, email, dan PDF
routes/web.php          Seluruh route web aplikasi
tests/Feature/          Pengujian alur aplikasi
tests/Unit/             Pengujian unit
```

## Catatan keamanan dan operasional

- Jangan commit file `.env`, kredensial, atau bukti pembayaran peserta.
- Pastikan direktori `storage` dan `bootstrap/cache` dapat ditulis oleh proses web server.
- Gunakan HTTPS di produksi karena aplikasi memproses data pribadi peserta dan bukti pembayaran.
- Konfigurasikan queue worker sebagai proses yang selalu aktif, misalnya melalui Supervisor atau process manager lain.
- Jalankan `php artisan optimize` setelah deployment dan `php artisan optimize:clear` saat melakukan troubleshooting konfigurasi.
- Lakukan backup database dan isi `storage/app/public` secara berkala.

## Lisensi

Proyek ini menggunakan kerangka Laravel yang berlisensi [MIT](https://opensource.org/licenses/MIT). Tentukan kebijakan lisensi khusus aplikasi sebelum mendistribusikan kode ke pihak lain.
