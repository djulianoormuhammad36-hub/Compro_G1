<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\SewaKamar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Aturan alur: isi identitas (kamar otomatis ditahan) -> bayar -> konfirmasi admin.
 *
 *  - Begitu calon penghuni mengirim identitas, kamar otomatis berstatus "reservasi" (sold).
 *  - Kamar ditahan 24 jam. Lewat itu tanpa pembayaran/kedatangan -> booking HANGUS otomatis
 *    dan kamar kembali "kosong".
 *  - Transfer/QRIS: wajib unggah foto bukti; admin yang memverifikasi (tidak hangus selama diperiksa).
 *  - Bayar langsung: calon penghuni harus datang ke kos dalam 24 jam; lewat itu -> hangus.
 *  - Admin menyetujui -> kamar "terisi" dan penghuni aktif. Admin juga bisa mengubah status kamar kapan saja.
 */
class BookingService
{
    public const DISK_BUKTI = 'local'; // privat: storage/app/private
    public const JAM_TAHAN = 24;

    /**
     * Calon penghuni mengirim identitas. Kamar otomatis ditahan.
     * Bila $token milik booking aktif di kamar yang sama ("Ubah Data"), data diperbarui, bukan dibuat baru.
     */
    public function buatBooking(Kamar $kamar, array $data, ?string $token = null): Booking
    {
        $this->hanguskanKedaluwarsa();

        return DB::transaction(function () use ($kamar, $data, $token) {
            $kamar = Kamar::lockForUpdate()->findOrFail($kamar->id_kamar);

            if ($token) {
                $ada = Booking::where('token', $token)
                    ->where('id_kamar', $kamar->id_kamar)
                    ->where('status_booking', Booking::MENUNGGU)
                    ->lockForUpdate()
                    ->first();

                if ($ada) {
                    $ada->update($data);

                    return $ada;
                }
            }

            if ($kamar->status_kamar !== Kamar::KOSONG) {
                throw new BookingException("Maaf, kamar {$kamar->nomor_kamar} sudah tidak tersedia.");
            }

            $booking = Booking::create($data + [
                'id_kamar'       => $kamar->id_kamar,
                'status_booking' => Booking::MENUNGGU,
                'batas_waktu'    => now()->addHours(self::JAM_TAHAN),
            ]);

            $kamar->update(['status_kamar' => Kamar::RESERVASI]);

            return $booking;
        });
    }

    /**
     * Calon penghuni memilih metode pembayaran.
     */
    public function kirimPembayaran(Booking $booking, string $metode, ?UploadedFile $bukti): Pembayaran
    {
        $this->hanguskanKedaluwarsa();

        if ($metode !== Pembayaran::METODE_LANGSUNG && ! $bukti) {
            throw new BookingException('Foto bukti pembayaran wajib diunggah untuk metode ' . $metode . '.');
        }

        return DB::transaction(function () use ($booking, $metode, $bukti) {
            $booking = Booking::lockForUpdate()->findOrFail($booking->id_booking);

            if ($booking->status_booking === Booking::HANGUS) {
                throw new BookingException('Waktu booking sudah habis (hangus). Silakan booking ulang kamar yang tersedia.');
            }

            if ($booking->status_booking !== Booking::MENUNGGU) {
                throw new BookingException('Booking ini sudah pernah dibayar atau diproses.');
            }

            $kamar = Kamar::lockForUpdate()->findOrFail($booking->id_kamar);

            if ($kamar->status_kamar === Kamar::TERISI) {
                throw new BookingException("Maaf, kamar {$kamar->nomor_kamar} sudah tidak tersedia.");
            }

            if ($kamar->status_kamar === Kamar::KOSONG) {
                // admin sempat melepas kamar; tahan kembali bila belum ada booking aktif lain
                if ($this->adaBookingAktifLain($kamar->id_kamar, $booking->id_booking)) {
                    throw new BookingException("Maaf, kamar {$kamar->nomor_kamar} sudah dipesan calon penghuni lain.");
                }
                $kamar->update(['status_kamar' => Kamar::RESERVASI]);
            }

            $penghuni = Penghuni::findByKtp($booking->no_ktp);
            if ($penghuni) {
                // Penghuni aktif: data TIDAK ditimpa, karena nomor KTP bisa diketik siapa saja.
                // Data hanya diperbarui untuk calon penghuni yang belum aktif.
                if (! $penghuni->status_aktif) {
                    $penghuni->update(['nama' => $booking->nama_pendaftar, 'kontak' => $booking->kontak]);
                }
            } else {
                $penghuni = Penghuni::create([
                    'nama'         => $booking->nama_pendaftar,
                    'no_ktp'       => $booking->no_ktp,
                    'kontak'       => $booking->kontak,
                    'status_aktif' => false, // aktif setelah admin menyetujui
                ]);
            }

            $sewa = SewaKamar::create([
                'id_booking'         => $booking->id_booking,
                'id_penghuni'        => $penghuni->id_penghuni,
                'id_kamar'           => $kamar->id_kamar,
                'tanggal_masuk'      => $booking->tanggal_booking,
                'tenggat_pembayaran' => $booking->tanggal_booking->copy()->addMonth(),
            ]);

            $path = $bukti ? $bukti->store('bukti_pembayaran', self::DISK_BUKTI) : null;

            try {
                $pembayaran = Pembayaran::create([
                    'id_sewa'           => $sewa->id_sewa,
                    'tanggal_bayar'     => now(),
                    'jumlah_bayar'      => $kamar->biaya_sewa,
                    'metode_pembayaran' => $metode,
                    'bukti_bayar'       => $path,
                    'status_pembayaran' => Pembayaran::MENUNGGU,
                ]);
            } catch (Throwable $e) {
                if ($path) {
                    Storage::disk(self::DISK_BUKTI)->delete($path);
                }
                throw $e;
            }

            $booking->update([
                'status_booking' => Booking::DIBAYAR,
                // bayar langsung: 24 jam untuk datang ke kos; transfer/QRIS: menunggu admin (tanpa batas)
                'batas_waktu'    => $metode === Pembayaran::METODE_LANGSUNG ? now()->addHours(self::JAM_TAHAN) : null,
            ]);

            return $pembayaran;
        });
    }

    /**
     * Admin menyetujui pembayaran -> kamar "terisi", penghuni aktif.
     */
    public function setujui(int $idPembayaran): Pembayaran
    {
        return DB::transaction(function () use ($idPembayaran) {
            $pembayaran = Pembayaran::lockForUpdate()->findOrFail($idPembayaran);
            $this->pastikanMenunggu($pembayaran);

            $sewa = $pembayaran->sewaKamar()->with(['booking', 'penghuni'])->firstOrFail();
            $kamar = Kamar::lockForUpdate()->findOrFail($sewa->id_kamar);

            if ($kamar->status_kamar === Kamar::TERISI) {
                throw new BookingException("Kamar {$kamar->nomor_kamar} sudah berstatus terisi. Tolak pembayaran ini atau ubah status kamar terlebih dahulu.");
            }

            if ($this->adaBookingAktifLain($kamar->id_kamar, $sewa->id_booking)) {
                throw new BookingException("Kamar {$kamar->nomor_kamar} sedang ditahan booking lain. Selesaikan atau tolak booking tersebut terlebih dahulu.");
            }

            $pembayaran->update(['status_pembayaran' => Pembayaran::TERKONFIRMASI, 'alasan_penolakan' => null]);
            $kamar->update(['status_kamar' => Kamar::TERISI]);
            $sewa->booking?->update(['status_booking' => Booking::TERKONFIRMASI, 'batas_waktu' => null]);
            $sewa->penghuni?->update(['status_aktif' => true]);

            return $pembayaran->load('sewaKamar.penghuni');
        });
    }

    /**
     * Admin menolak pembayaran -> kamar dilepas kembali.
     */
    public function tolak(int $idPembayaran, string $alasan): Pembayaran
    {
        return DB::transaction(function () use ($idPembayaran, $alasan) {
            $pembayaran = Pembayaran::lockForUpdate()->findOrFail($idPembayaran);
            $this->pastikanMenunggu($pembayaran);

            $sewa = $pembayaran->sewaKamar()->with('booking')->firstOrFail();

            $pembayaran->update(['status_pembayaran' => Pembayaran::DITOLAK, 'alasan_penolakan' => $alasan]);
            $sewa->booking?->update(['status_booking' => Booking::DITOLAK, 'batas_waktu' => null]);
            $this->lepasKamar($sewa->id_kamar, $sewa->id_booking);

            return $pembayaran->load('sewaKamar.penghuni');
        });
    }

    /**
     * Hanguskan booking yang lewat batas waktu. Aman dipanggil berulang.
     * Dipicu penjadwal (booking:kedaluwarsa) dan secara berkala dari request web.
     *
     * @return int jumlah booking yang dihanguskan
     */
    public function hanguskanKedaluwarsa(): int
    {
        $sekarang = now();
        $jumlah = 0;

        $kandidat = Booking::query()
            ->whereNotNull('batas_waktu')
            ->where('batas_waktu', '<', $sekarang)
            ->whereIn('status_booking', [Booking::MENUNGGU, Booking::DIBAYAR])
            ->pluck('id_booking');

        foreach ($kandidat as $id) {
            $jumlah += DB::transaction(function () use ($id, $sekarang) {
                $booking = Booking::lockForUpdate()->find($id);

                if (! $booking || ! $booking->batas_waktu || $booking->batas_waktu->gte($sekarang)) {
                    return 0;
                }

                $sewa = $booking->sewaKamar()->first();
                $bayarLangsung = $sewa?->pembayarans()
                    ->where('metode_pembayaran', Pembayaran::METODE_LANGSUNG)
                    ->where('status_pembayaran', Pembayaran::MENUNGGU)
                    ->get() ?? collect();

                // sudah dibayar via transfer/QRIS & menunggu admin: jangan dihanguskan
                if ($booking->status_booking === Booking::DIBAYAR && $bayarLangsung->isEmpty()) {
                    return 0;
                }

                $bayarLangsung->each->update(['status_pembayaran' => Pembayaran::HANGUS]);
                $booking->update(['status_booking' => Booking::HANGUS]);
                $this->lepasKamar($booking->id_kamar, $booking->id_booking);

                return 1;
            });
        }

        return $jumlah;
    }

    /** Kamar yang ditahan kembali "kosong" bila tidak ada booking aktif lain (dan admin tidak mengubahnya). */
    private function lepasKamar(int $idKamar, int $kecualiBooking): void
    {
        $kamar = Kamar::lockForUpdate()->find($idKamar);

        if ($kamar && $kamar->status_kamar === Kamar::RESERVASI && ! $this->adaBookingAktifLain($idKamar, $kecualiBooking)) {
            $kamar->update(['status_kamar' => Kamar::KOSONG]);
        }
    }

    private function adaBookingAktifLain(int $idKamar, ?int $kecualiBooking): bool
    {
        return Booking::where('id_kamar', $idKamar)
            ->when($kecualiBooking, fn ($q) => $q->where('id_booking', '!=', $kecualiBooking))
            ->whereIn('status_booking', [Booking::MENUNGGU, Booking::DIBAYAR])
            ->where(fn ($q) => $q->whereNull('batas_waktu')->orWhere('batas_waktu', '>=', now()))
            ->exists();
    }

    private function pastikanMenunggu(Pembayaran $pembayaran): void
    {
        if ($pembayaran->status_pembayaran !== Pembayaran::MENUNGGU) {
            throw new BookingException('Pembayaran ini sudah diproses sebelumnya (status: ' . $pembayaran->status_pembayaran . ').');
        }
    }
}