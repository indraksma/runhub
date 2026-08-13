# Spesifikasi Aplikasi Pendaftaran Event Lari (Dinamis & Reusable)

> Dokumen ini adalah acuan untuk AI coding agent (Claude Code, Cursor, dsb) dalam membangun aplikasi pendaftaran event lari. Aplikasi bersifat **dinamis** — dirancang untuk dipakai berulang kali oleh satu organizer/panitia untuk banyak event lari yang berbeda dari waktu ke waktu, bukan aplikasi sekali pakai untuk satu event tertentu.

---

## 1. Ringkasan Proyek

- **Tujuan**: menyediakan platform pendaftaran online untuk event lari (fun run, half marathon, dsb) yang bisa dipakai berulang — admin cukup membuat event baru setiap kali ada penyelenggaraan, tanpa perlu rebuild aplikasi.
- **Skala pemakaian**: single organizer (satu panitia/admin) yang mengelola banyak event dari waktu ke waktu. **Bukan** platform multi-tenant (organizer lain tidak bisa mendaftar dan membuat event sendiri) — meski begitu, struktur data tetap dibuat rapi per-event supaya mudah di-extend ke arah multi-tenant di masa depan bila diperlukan.
- **Pembayaran**: tahap awal menggunakan QRIS statis yang diunggah manual oleh admin per event, diverifikasi manual. Arsitektur pembayaran harus disiapkan agar mudah diintegrasikan dengan payment gateway (Midtrans, Xendit, dll) di kemudian hari tanpa merombak alur inti.

---

## 2. Tech Stack

| Layer | Pilihan | Alasan |
|---|---|---|
| Backend framework | Laravel (versi LTS/terbaru) | Familiar, ekosistem matang, cocok untuk hosting di VPS aaPanel |
| Admin panel | Filament | Mempercepat pembuatan CRUD untuk event, kategori, verifikasi pembayaran, dsb — pekerjaan admin yang berulang tiap event baru |
| Frontend pendaftaran publik | Livewire + Blade + Tailwind CSS | Interaktif (multi-step form) tanpa perlu API + SPA terpisah |
| Database | MySQL / MariaDB | Konsisten dengan environment hosting yang sudah dipakai (aaPanel) |
| Queue | Laravel Queue (database/redis driver) | Kirim notifikasi WhatsApp & email secara async, tidak memblokir proses submit pendaftaran |
| File storage | Local disk / S3-compatible | Menyimpan bukti transfer QRIS & gambar QR per event |
| Hosting | VPS dengan aaPanel | Sesuai environment yang sudah biasa dipakai |

---

## 3. Role Pengguna

1. **Admin/Panitia** — mengelola event, kategori, harga, verifikasi pembayaran, dan komunikasi ke peserta.
2. **Peserta** — **wajib memiliki akun** (register/login) sebelum mendaftar. Tujuannya supaya data diri & riwayat pendaftaran tersimpan dan bisa auto-fill saat mendaftar ke event berikutnya.

---

## 4. Entitas Data Utama (Garis Besar)

- **User** — data peserta (nama, email, no. HP/WA, tanggal lahir, gender, golongan darah, kontak darurat [nama + no. HP], dsb) + role (admin/peserta).
- **Event** — nama, deskripsi, lokasi, tanggal pelaksanaan, tanggal buka/tutup pendaftaran, status (draft/published/closed), banner.
- **RaceCategory** (kategori/jarak lomba) — relasi ke Event; nama kategori (5K/10K/Half Marathon, dst), kuota, harga dasar.
- **PricingTier** (harga bertingkat) — relasi ke RaceCategory; nama tier (early bird/reguler/last minute), tanggal_mulai, tanggal_selesai, harga. Sistem memilih tier aktif otomatis berdasarkan tanggal pendaftaran saat ini.
- **EventPaymentAccount** (QRIS per event) — relasi ke Event; gambar QRIS, nama rekening/catatan, aktif/tidak. Setiap event bisa punya QRIS/tujuan pembayaran berbeda.
- **Registration** (pendaftaran) — relasi ke User, RaceCategory, PricingTier; nomor BIB (auto-generate), status (pending_payment/menunggu_verifikasi/terverifikasi/ditolak/dibatalkan), data tambahan spesifik event (ukuran jersey, dsb — kontak darurat & golongan darah sudah tersimpan di profil User).
- **Payment** — relasi ke Registration; metode (qris_statis untuk versi awal, gateway untuk masa depan), bukti transfer (file), status verifikasi, diverifikasi_oleh (admin), waktu verifikasi.
- **NotificationLog** (opsional tapi disarankan) — mencatat riwayat notifikasi WhatsApp/email yang terkirim per registrasi, untuk audit trail.

---

## 5. Fitur Utama (Versi Awal)

### 5.1 Manajemen Event (Admin)
- CRUD event: buka/tutup pendaftaran, atur tanggal pelaksanaan, banner, deskripsi.
- Duplikasi event (clone dari event sebelumnya) untuk mempercepat setup event baru yang formatnya mirip.

### 5.2 Kategori & Harga Bertingkat
- Admin bisa menambahkan beberapa kategori/jarak lomba per event, masing-masing dengan kuota dan harga sendiri.
- Setiap kategori bisa punya beberapa **pricing tier** berbasis periode waktu (early bird → reguler → last minute). Harga yang ditampilkan ke peserta otomatis mengikuti tanggal saat mereka mendaftar.

### 5.3 QRIS per Event
- Admin mengunggah gambar QRIS (dan opsional nomor rekening manual sebagai alternatif) khusus untuk tiap event — bukan satu QRIS global untuk semua event.

### 5.4 Pendaftaran & Akun Peserta
- Peserta wajib register/login sebelum mendaftar.
- Form pendaftaran multi-step: pilih event → pilih kategori (harga sesuai tier aktif) → isi/lengkapi data diri (auto-fill dari profil jika sudah pernah daftar sebelumnya) → upload bukti transfer QRIS → submit.
- Dashboard peserta menampilkan riwayat pendaftaran dari semua event yang pernah diikuti serta status masing-masing.

### 5.5 Nomor BIB Otomatis
- Nomor BIB digenerate otomatis saat pembayaran terverifikasi (bukan saat submit), dengan format yang bisa dikonfigurasi per event/kategori (misal prefix kategori + nomor urut).

### 5.6 Verifikasi Pembayaran Manual
- Admin melihat daftar pembayaran masuk beserta bukti transfer, lalu approve/reject.
- Saat approve: status registrasi berubah, BIB digenerate, notifikasi WhatsApp & email terpicu otomatis.
- Saat reject: peserta mendapat notifikasi dengan alasan, dan diberi kesempatan upload ulang bukti transfer.

### 5.7 Notifikasi WhatsApp
- Setelah status pendaftaran berubah (terverifikasi/ditolak), admin (atau sistem) mendapat/mengirim teks yang sudah diformat siap-kirim ke WhatsApp — pola serupa dengan project ALFAGO sebelumnya (bisa lewat tombol "copy teks" atau integrasi API WhatsApp jika tersedia ke depannya).

### 5.8 Notifikasi Email Otomatis
- Email otomatis terkirim ke peserta untuk: invoice/instruksi pembayaran setelah submit pendaftaran, status pembayaran (diverifikasi/ditolak), dan konfirmasi registrasi berikut nomor BIB.

---

## 6. Alur Pengguna (User Flow)

### 6.1 Alur Peserta
1. Register/login.
2. Pilih event yang sedang buka pendaftaran.
3. Pilih kategori lomba → sistem menampilkan harga sesuai pricing tier aktif saat itu.
4. Lengkapi data pendaftaran (sebagian auto-filled dari profil).
5. Submit → sistem menampilkan detail pembayaran (QRIS event tersebut) + mengirim email invoice.
6. Peserta upload bukti transfer.
7. Menunggu verifikasi admin.
8. Setelah terverifikasi: menerima email + (opsional) info WhatsApp berisi konfirmasi dan nomor BIB.
9. Bisa cek riwayat & status pendaftaran kapan pun lewat dashboard akun.

### 6.2 Alur Admin
1. Buat event baru (atau duplikasi dari event sebelumnya).
2. Setup kategori lomba, kuota, dan pricing tier (early bird/reguler/last minute) per kategori.
3. Upload QRIS/rekening tujuan untuk event tersebut.
4. Publish event (buka pendaftaran).
5. Pantau pendaftaran masuk & antrian verifikasi pembayaran.
6. Approve/reject bukti transfer → sistem otomatis generate BIB & kirim notifikasi.
7. Setelah event selesai, tutup/arsipkan event.

---

## 7. Arsitektur Pembayaran & Rencana Payment Gateway

Untuk memudahkan integrasi payment gateway di masa depan tanpa merombak alur inti, disarankan:

- Buat abstraksi berupa `PaymentService` (interface/contract) dengan implementasi awal `StaticQrisPaymentService` (alur manual: tampilkan QR → peserta upload bukti → admin verifikasi manual).
- Tabel `Payment` dirancang generik (kolom `method`, `status`, `reference_id`, `raw_payload`/`meta` dalam JSON) sehingga siap menampung data dari gateway (VA number, callback payload, dsb) tanpa perlu migrasi besar-besaran nantinya.
- Saat payment gateway ditambahkan, cukup buat implementasi baru (misal `MidtransPaymentService`) yang mengikuti interface yang sama, plus endpoint webhook untuk callback status pembayaran otomatis — alur verifikasi manual admin tetap bisa dipertahankan sebagai fallback/metode alternatif.

---

## 8. Pertimbangan Non-Fungsional

- **Concurrency kuota**: pendaftaran pada kategori dengan kuota terbatas harus aman dari race condition (gunakan database transaction + row locking saat mengurangi kuota tersisa), terutama saat pendaftaran ramai di awal pembukaan.
- **Mobile-first**: mayoritas peserta kemungkinan mendaftar lewat HP — form pendaftaran & upload bukti transfer harus nyaman diakses dari mobile.
- **Keamanan upload file**: validasi tipe file (jpg/png/pdf) dan ukuran maksimum untuk bukti transfer & banner event.
- **Reusability**: hindari hardcode apa pun yang spesifik untuk satu event (nama event, kategori, harga, dsb) — semua harus dikelola dari admin panel agar aplikasi benar-benar bisa dipakai berulang untuk event berikutnya.

---

## 9. Di Luar Cakupan Versi Awal (Potensi Roadmap)

- Integrasi payment gateway otomatis (arsitektur sudah disiapkan di §7, implementasi menyusul).
- E-sertifikat/e-BIB otomatis dalam bentuk PDF untuk peserta.
- Model multi-tenant (organizer lain membuat event sendiri).

---

## 10. Catatan untuk AI Coding Agent

- Ikuti konvensi Laravel standar: gunakan Form Request untuk validasi, Policy untuk otorisasi (admin vs peserta), Job/Queue untuk pengiriman notifikasi async.
- Gunakan Filament Resource untuk tiap entitas admin (Event, RaceCategory, PricingTier, Registration, Payment) lengkap dengan relation manager di mana relevan (misal kategori & pricing tier sebagai nested resource dari Event).
- Tulis feature test minimal untuk alur kritikal: submit pendaftaran, perhitungan harga sesuai pricing tier aktif, penguncian kuota saat kategori penuh, dan perubahan status setelah verifikasi pembayaran.
- Struktur folder mengikuti standar Laravel (`app/Models`, `app/Livewire`, `app/Filament/Resources`, `app/Services` untuk `PaymentService`, dsb).
