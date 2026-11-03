# Pondok Asri — Sistem Informasi Kos

Aplikasi kos dengan **Laravel 13 + React (Inertia.js)**: katalog kamar, booking online tanpa akun, pembayaran (transfer, QRIS, atau bayar langsung), dan panel admin dengan dashboard visual.

## Alur calon penghuni (tanpa login)

1. Lihat katalog → buka detail kamar → isi **identitas** (nama, KTP 16 digit, HP, tanggal).
2. Begitu identitas terkirim, **kamar otomatis ditahan** (status *Dipesan* / sold) selama **24 jam**.
3. Pilih metode pembayaran:
   - **Transfer bank** — rekening ditampilkan (diisi admin), lalu unggah **foto bukti transfer** (wajib).
   - **QRIS** — gambar QRIS ditampilkan (ditempel admin), lalu unggah **foto bukti pembayaran** (wajib).
   - **Bayar langsung** — datang ke kos **dalam 1x24 jam** dan bayar ke pengelola.
4. Tombol **Cetak Bukti Pembayaran** ada di halaman pembayaran (aktif setelah pembayaran dikirim). Bukti berstatus *LUNAS* setelah admin menyetujui.

**Hangus otomatis:** booking yang tidak dibayar dalam 24 jam, atau pilihan *bayar langsung* yang tidak datang dalam 24 jam, otomatis **hangus** dan kamar kembali **tersedia**. Pembayaran transfer/QRIS yang sudah mengirim bukti tidak hangus selama menunggu pemeriksaan admin. Pembersihan berjalan lewat penjadwal (`booking:kedaluwarsa` tiap 5 menit) dan sebagai cadangan setiap ada kunjungan web (maks. sekali per 30 detik).

## Panel admin (`/admin`, wajib login)

| Menu | Kemampuan |
|---|---|
| Dashboard | Kartu ringkasan + visualisasi (Recharts): pemasukan per bulan, komposisi kamar, booking 14 hari, metode pembayaran; unduh CSV |
| Kelola Kamar | Tambah/ubah/hapus kamar, **foto kamar**, pilih fasilitas, **ubah status kamar** langsung dari tabel |
| Fasilitas | **Tambah**/ubah/hapus fasilitas |
| Penghuni | **Lihat identitas lengkap** (KTP, kontak, riwayat), **tandai keluar** (kamar jadi tersedia), **hapus** penghuni yang sudah tidak ngekos |
| Konfirmasi Pembayaran | Lihat **foto bukti transfer**, setujui/tolak (alasan wajib); filter Menunggu/Terkonfirmasi/Ditolak/Hangus |
| Info Kos | Nama, **deskripsi**, **kontak/WhatsApp**, lokasi, **peraturan**, **foto kos/kamar utama**, **rekening & gambar QRIS** |

Admin tetap memakai login karena panel memuat KTP penghuni dan bukti pembayaran. Calon penghuni **tidak** perlu akun.

**WhatsApp:** semua tombol *Hubungi Kami / Hubungi Pemilik* membuka chat WhatsApp ke nomor di Info Kos (nomor `08…` otomatis menjadi `62…`).

**Menghapus penghuni:** hanya untuk penghuni yang tidak aktif. Nama, KTP, dan kontak dihapus permanen; catatan pembayaran tetap tersimpan tanpa identitas untuk laporan keuangan.

## Instalasi

Kebutuhan: PHP 8.4+, Composer, Node 20+, SQLite/MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link          # agar foto kamar/kos/QRIS tampil
npm install && npm run build      # frontend React (atau: composer run dev)
php artisan serve
```

Agar booking hangus tepat waktu, pasang cron: `* * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1`.

`migrate --seed` membuat data contoh dan **satu akun admin** (email/password dari `ADMIN_EMAIL` / `ADMIN_PASSWORD` di `.env`; bila password kosong, dibuat acak dan ditampilkan sekali di terminal). Admin tambahan: `php artisan admin:create email@contoh.com`. Login di `/login`.

## Jika sesuatu tidak bisa disimpan

- **Setelah memperbarui kode, selalu jalankan `php artisan migrate`** (menambah kolom baru seperti deskripsi kos, QRIS, foto kamar). Halaman Info Kos menampilkan peringatan kuning bila database belum diperbarui.
- **Foto gagal diunggah:** PHP bawaan (XAMPP/Laragon) hanya mengizinkan file **2 MB**. Halaman admin sudah menolak file yang lebih besar dengan pesan jelas. Untuk batas lebih besar, ubah di `php.ini`: `upload_max_filesize = 8M` dan `post_max_size = 10M`, lalu restart server.
- **Setelah mengubah kode React:** jalankan `npm run build` (atau `npm run dev` saat pengembangan).
- Bila ada isian yang salah, ringkasan error tampil di bagian atas form dan halaman otomatis menggulir ke sana.

## Frontend (React)

- Inertia.js + React 19, Tailwind CSS 4, Recharts, lucide-react. Entry: `resources/js/app.jsx`.
- Halaman: `resources/js/pages/` (publik di akar, admin di `Admin/`). Komponen bersama: `resources/js/components/`.
- Foto kamar → jika kosong memakai foto kos (Info Kos) → jika kosong memakai ilustrasi bawaan `public/images/kamar-default.svg`.

## Keamanan (ringkas)

- `/admin/*` dilindungi middleware `auth` + `admin`; login dibatasi 5 percobaan/menit; form publik 10 request/menit.
- URL booking & bukti pembayaran memakai **token UUID**. KTP disimpan **terenkripsi** (jangan ganti `APP_KEY` setelah ada data).
- **Foto bukti transfer privat** (`storage/app/private`), hanya bisa dilihat admin. Foto kamar/kos/QRIS publik (`storage/app/public`).
- Produksi: `APP_DEBUG=false`, `APP_ENV=production`, HTTPS.

## Struktur singkat

| Lokasi | Isi |
|---|---|
| `app/Services/BookingService.php` | Tahan kamar, pembayaran, setujui/tolak, hangus otomatis |
| `app/Services/PenghuniService.php` | Tandai keluar & hapus penghuni |
| `app/Http/Controllers/` | Publik & booking; `Admin/` untuk panel |
| `app/Support/Tampil.php` | Pengubah model → props React |
| `routes/web.php` | Route publik, login, admin (`admin.*`) |

## Testing

```bash
php artisan test
```

Cakupan: halaman publik & props, tahan kamar otomatis, ketiga metode bayar, foto bukti wajib, hangus otomatis (24 jam), akses & login admin, setujui/tolak, CRUD kamar & foto, fasilitas, penghuni (identitas/keluar/hapus), info kos, data dashboard.
