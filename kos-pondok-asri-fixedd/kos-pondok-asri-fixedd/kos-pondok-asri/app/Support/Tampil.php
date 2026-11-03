<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Fasilitas;
use App\Models\Kamar;
use App\Models\Pembayaran;
use Carbon\CarbonInterface;

/**
 * Pengubah model -> array props untuk halaman React.
 */
class Tampil
{
    public static function kamar(Kamar $k): array
    {
        return [
            'id'        => $k->id_kamar,
            'nomor'     => $k->nomor_kamar,
            'detail'    => $k->detail_kamar,
            'biaya'     => $k->biaya_sewa,
            'status'    => $k->status_kamar,
            'foto'      => $k->foto_url,
            'fasilitas' => $k->relationLoaded('fasilitas')
                ? $k->fasilitas->map(fn ($f) => ['id' => $f->id_fasilitas, 'nama' => $f->nama_fasilitas])->values()->all()
                : [],
        ];
    }

    public static function fasilitas(Fasilitas $f): array
    {
        return [
            'id'        => $f->id_fasilitas,
            'nama'      => $f->nama_fasilitas,
            'deskripsi' => $f->deskripsi_fasilitas,
            'ikon'      => $f->icon,
        ];
    }

    public static function booking(Booking $b): array
    {
        return [
            'kode'        => $b->kode,
            'token'       => $b->token,
            'status'      => $b->status_booking,
            'nama'        => $b->nama_pendaftar,
            'ktp_samaran' => $b->ktp_samaran,
            'kontak'      => $b->kontak,
            'tanggal'     => self::tanggal($b->tanggal_booking),
            'periode'     => Periode::satuBulan($b->tanggal_booking),
            'batas_waktu' => $b->batas_waktu?->toIso8601String(),
            'batas_label' => $b->batas_waktu ? self::tanggalJam($b->batas_waktu) : null,
            'kamar'       => self::kamar($b->kamar),
            'urls'        => [
                'review'  => route('booking.review', $b),
                'payment' => route('booking.payment', $b),
                'pay'     => route('booking.pay', $b),
                'ubah'    => route('kamar.show', [$b->kamar, 'booking' => $b->token]),
            ],
        ];
    }

    public static function pembayaran(Pembayaran $p): array
    {
        return [
            'id'      => $p->id_pembayaran,
            'status'  => $p->status_pembayaran,
            'metode'  => $p->metode_pembayaran,
            'alasan'  => $p->alasan_penolakan,
            'jumlah'  => $p->jumlah_bayar,
            'tanggal' => self::tanggalJam($p->tanggal_bayar),
            'urls'    => [
                'bukti' => route('booking.bukti', $p),
                'cetak' => route('booking.bukti', [$p, 'cetak' => 1]),
            ],
        ];
    }

    public static function tanggal(?CarbonInterface $t): ?string
    {
        return $t?->translatedFormat('d F Y');
    }

    public static function tanggalJam(?CarbonInterface $t): ?string
    {
        return $t?->translatedFormat('d F Y · H.i') . ' WIB';
    }
}
