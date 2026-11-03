<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Http\Requests\PembayaranRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Kamar;
use App\Models\KosInfo;
use App\Models\Pembayaran;
use App\Services\BookingService;
use App\Support\Periode;
use App\Support\Tampil;
use Inertia\Inertia;

class BookingProcessController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    /**
     * Langkah 1: kirim identitas. Kamar otomatis ditahan (status reservasi) selama 24 jam.
     */
    public function storeBooking(StoreBookingRequest $request, Kamar $kamar)
    {
        $data = $request->safe()->only(['nama_pendaftar', 'no_ktp', 'kontak', 'tanggal_booking']);

        try {
            $booking = $this->bookings->buatBooking($kamar, $data, $request->validated('booking'));
        } catch (BookingException $e) {
            return redirect()->route('kamar.index')->with('error', $e->getMessage());
        }

        return redirect()->route('booking.review', $booking)
            ->with('success', 'Data tersimpan. Kamar ' . $kamar->nomor_kamar . ' ditahan untukmu selama 24 jam.');
    }

    /**
     * Langkah 2: ringkasan booking.
     */
    public function reviewBooking(Booking $booking)
    {
        $booking->load('kamar.fasilitas');

        return Inertia::render('Booking/Review', ['booking' => Tampil::booking($booking)]);
    }

    /**
     * Langkah 3: halaman pembayaran. Sebelum dibayar: pilih metode (transfer/QRIS + foto bukti, atau bayar langsung);
     * setelah dibayar: status dan tombol cetak bukti.
     */
    public function paymentMethod(Booking $booking)
    {
        $booking->load('kamar.fasilitas');

        $pembayaran = $booking->sewaKamar?->pembayarans()->latest('id_pembayaran')->first();
        $kos = KosInfo::first();

        return Inertia::render('Booking/Payment', [
            'booking'    => Tampil::booking($booking),
            'pembayaran' => $pembayaran ? Tampil::pembayaran($pembayaran) : null,
            'bayar'      => [
                'info_transfer' => $kos?->info_pembayaran,
                'qris'          => $kos?->qris_url,
                'jam_tahan'     => BookingService::JAM_TAHAN,
                'kontak'        => $kos?->kontak_pemilik,
                'lokasi'        => $kos?->lokasi,
            ],
        ]);
    }

    /**
     * Langkah 4: kirim pembayaran. Aturan bisnis ada di BookingService.
     */
    public function processPembayaran(PembayaranRequest $request, Booking $booking)
    {
        try {
            $pembayaran = $this->bookings->kirimPembayaran(
                $booking,
                $request->validated('metode_pembayaran'),
                $request->file('bukti_bayar'),
            );
        } catch (BookingException $e) {
            return redirect()->route('booking.payment', $booking)->with('error', $e->getMessage());
        }

        $pesan = $pembayaran->metode_pembayaran === Pembayaran::METODE_LANGSUNG
            ? 'Pilihan bayar langsung tersimpan. Datang ke kos dalam 24 jam agar booking tidak hangus.'
            : 'Bukti pembayaran terkirim dan akan diperiksa pengelola.';

        return redirect()->route('booking.payment', $booking)->with('success', $pesan);
    }

    /**
     * Langkah 5: bukti pembayaran (dapat dicetak).
     */
    public function buktiPembayaran(Pembayaran $pembayaran)
    {
        $pembayaran->load(['sewaKamar.penghuni', 'sewaKamar.kamar', 'sewaKamar.booking']);
        $sewa = $pembayaran->sewaKamar;
        $booking = $sewa->booking;

        return Inertia::render('Booking/Receipt', [
            'pembayaran' => Tampil::pembayaran($pembayaran) + [
                'kode'    => $booking?->kode ?? 'PAY-' . $pembayaran->id_pembayaran,
                'nama'    => $sewa->penghuni?->nama ?? $booking?->nama_pendaftar ?? '-',
                'kamar'   => $sewa->kamar->nomor_kamar,
                'periode' => Periode::satuBulan($sewa->tanggal_masuk),
                'batas'   => $booking?->batas_waktu ? Tampil::tanggalJam($booking->batas_waktu) : null,
                'dicetak' => Tampil::tanggalJam(now()),
            ],
            'kembali'    => $booking ? route('booking.payment', $booking) : route('home'),
            'cetak'      => request()->boolean('cetak'),
        ]);
    }
}
