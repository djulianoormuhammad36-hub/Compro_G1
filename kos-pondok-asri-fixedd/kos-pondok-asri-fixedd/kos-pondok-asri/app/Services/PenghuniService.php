<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\SewaKamar;
use Illuminate\Support\Facades\DB;

class PenghuniService
{
    /**
     * Penghuni berhenti ngekos: sewa aktif ditutup hari ini dan kamar kembali kosong.
     *
     * Hanya sewa yang pembayarannya sudah TERKONFIRMASI yang diproses. Sewa dari booking
     * yang ditolak/hangus dibiarkan, supaya kamar yang kini dihuni orang lain tidak ikut dikosongkan.
     */
    public function keluar(Penghuni $penghuni): int
    {
        return DB::transaction(function () use ($penghuni) {
            $sewas = SewaKamar::where('id_penghuni', $penghuni->id_penghuni)
                ->whereNull('tanggal_keluar')
                ->whereHas('pembayarans', fn ($q) => $q->where('status_pembayaran', Pembayaran::TERKONFIRMASI))
                ->get();

            foreach ($sewas as $sewa) {
                $sewa->update(['tanggal_keluar' => now()->toDateString()]);

                // Jangan kosongkan kamar bila masih ada penghuni lain yang sewanya aktif di kamar yang sama.
                $adaPenghuniLain = SewaKamar::where('id_kamar', $sewa->id_kamar)
                    ->where('id_sewa', '!=', $sewa->id_sewa)
                    ->whereNull('tanggal_keluar')
                    ->whereHas('pembayarans', fn ($q) => $q->where('status_pembayaran', Pembayaran::TERKONFIRMASI))
                    ->exists();

                if (! $adaPenghuniLain) {
                    Kamar::where('id_kamar', $sewa->id_kamar)
                        ->where('status_kamar', Kamar::TERISI)
                        ->update(['status_kamar' => Kamar::KOSONG]);
                }
            }

            $penghuni->update(['status_aktif' => false]);

            return $sewas->count();
        });
    }

    /**
     * Hapus data penghuni yang sudah tidak ngekos. Identitas (nama, KTP, kontak) dihapus,
     * sedangkan catatan pembayaran tetap ada untuk laporan keuangan.
     */
    public function hapus(Penghuni $penghuni): void
    {
        if ($penghuni->status_aktif) {
            throw new BookingException('Penghuni masih aktif ngekos. Tandai keluar terlebih dahulu sebelum menghapus.');
        }

        $idSewa = SewaKamar::where('id_penghuni', $penghuni->id_penghuni)->pluck('id_sewa');
        $idBooking = SewaKamar::whereIn('id_sewa', $idSewa)->whereNotNull('id_booking')->pluck('id_booking');

        if (Booking::whereIn('id_booking', $idBooking)->whereIn('status_booking', [Booking::MENUNGGU, Booking::DIBAYAR])->exists()) {
            throw new BookingException('Penghuni masih memiliki booking yang sedang diproses. Selesaikan atau tolak pembayarannya terlebih dahulu.');
        }

        DB::transaction(function () use ($penghuni, $idBooking) {
            // hapus jejak identitas pada booking terkait
            Booking::whereIn('id_booking', $idBooking)->get()->each->update([
                'nama_pendaftar' => 'Penghuni dihapus',
                'no_ktp'         => '-',
                'kontak'         => '-',
            ]);

            SewaKamar::where('id_penghuni', $penghuni->id_penghuni)->update(['id_penghuni' => null]);

            $penghuni->delete();
        });
    }
}