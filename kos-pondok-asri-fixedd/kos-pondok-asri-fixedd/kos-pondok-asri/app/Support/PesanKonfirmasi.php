<?php

namespace App\Support;

use App\Models\Pembayaran;

/**
 * Menyusun pesan WhatsApp untuk memberi tahu calon penghuni bahwa pembayarannya
 * diterima, ditolak, atau hangus. Dikirim admin lewat tautan wa.me (tanpa layanan API).
 */
class PesanKonfirmasi
{
    /**
     * @return array{jenis:string,nama:string,url:string,pesan:string}|null null bila belum diproses / tidak ada nomor
     */
    public static function untuk(Pembayaran $p, ?string $namaKos = null): ?array
    {
        $jenis = match ($p->status_pembayaran) {
            Pembayaran::TERKONFIRMASI => 'disetujui',
            Pembayaran::DITOLAK       => 'ditolak',
            Pembayaran::HANGUS        => 'hangus',
            default                   => null,
        };

        if (! $jenis) {
            return null;
        }

        $sewa = $p->sewaKamar;
        $booking = $sewa?->booking;
        $penghuni = $sewa?->penghuni;

        // nomor yang dipakai saat booking; bila identitas sudah dihapus, tidak ada yang bisa dihubungi
        $kontak = $booking && Whatsapp::nomor($booking->kontak) ? $booking->kontak : $penghuni?->kontak;
        $nama = $booking && $booking->nama_pendaftar !== 'Penghuni dihapus' ? $booking->nama_pendaftar : ($penghuni?->nama ?? 'Kak');

        if (! Whatsapp::nomor($kontak)) {
            return null;
        }

        $kos = $namaKos ?: config('app.name');
        $kamar = $sewa?->kamar?->nomor_kamar ?? '-';
        $kode = $booking?->kode ?? ('PAY-' . $p->id_pembayaran);
        $statusUrl = $booking ? route('booking.payment', $booking) : route('kamar.index');

        $pesan = match ($jenis) {
            'disetujui' => implode("\n", [
                "Halo {$nama}, kabar baik! ✅",
                "Pembayaran sewa Kamar {$kamar} di {$kos} sudah kami TERIMA.",
                '',
                "Kode booking: {$kode}",
                'Mulai sewa: ' . Tampil::tanggal($sewa?->tanggal_masuk),
                'Total: Rp ' . number_format($p->jumlah_bayar, 0, ',', '.'),
                '',
                'Bukti pembayaran: ' . route('booking.bukti', $p),
                '',
                "Terima kasih sudah memilih {$kos}.",
            ]),
            'ditolak' => implode("\n", [
                "Halo {$nama}, mohon maaf. ❌",
                "Pembayaran untuk Kamar {$kamar} di {$kos} belum dapat kami terima.",
                'Alasan: ' . ($p->alasan_penolakan ?: '-'),
                '',
                "Kode booking: {$kode}",
                "Cek status: {$statusUrl}",
                '',
                'Silakan hubungi kami bila ada pertanyaan atau ingin booking ulang.',
            ]),
            default => implode("\n", [
                "Halo {$nama},",
                "Booking Kamar {$kamar} di {$kos} (kode {$kode}) dibatalkan otomatis karena melewati batas waktu 24 jam.",
                '',
                'Kamu bisa booking ulang kapan saja: ' . route('kamar.index'),
            ]),
        };

        return ['jenis' => $jenis, 'nama' => $nama, 'url' => Whatsapp::link($kontak, $pesan), 'pesan' => $pesan];
    }
}