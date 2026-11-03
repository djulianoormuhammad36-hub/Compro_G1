# Pondok Asri — catatan untuk agent

Laravel 13 / PHP 8.4, SQLite default. Frontend **React 19 via Inertia 3** (`resources/js/`), Tailwind 4, Recharts.

- Aturan bisnis booking/pembayaran **hanya** di `app/Services/BookingService.php`; penghuni di `PenghuniService`. Controller tipis, mengembalikan `Inertia::render('Halaman', props)`.
- Props dibentuk lewat `app/Support/Tampil.php`; halaman React ada di `resources/js/pages/<Nama>.jsx` (nama = argumen `Inertia::render`).
- Alur: identitas terkirim → kamar otomatis `reservasi` + `batas_waktu` 24 jam → bayar (Transfer Bank / QRIS wajib foto, Bayar Langsung) → admin setujui → kamar `terisi`. Lewat batas → `Hangus` (`booking:kedaluwarsa`, juga dipicu middleware).
- Primary key kustom: `id_kamar`, `id_booking`, `id_sewa`, `id_pembayaran`, `id_penghuni`, `id_fasilitas`.
- `Booking` & `Pembayaran` di-route-bind lewat `token` (UUID). Route admin memakai ID biasa.
- `no_ktp` terenkripsi; cari penghuni lewat `Penghuni::findByKtp()`.
- Status memakai konstanta model (`Kamar::KOSONG`, `Booking::HANGUS`, `Pembayaran::METODE_LANGSUNG`, dst.).
- Foto bukti transfer di disk `local` (privat) → `admin.konfirmasi.bukti`. Foto kamar/kos/QRIS di disk `public` lewat `App\Support\Media`.
- Test: `php artisan test`. Ubah frontend → `npm run build`.
