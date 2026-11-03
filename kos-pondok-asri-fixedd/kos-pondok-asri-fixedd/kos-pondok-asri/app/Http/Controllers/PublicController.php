<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Fasilitas;
use App\Models\Kamar;
use App\Models\KosInfo;
use App\Support\Periode;
use App\Support\Tampil;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicController extends Controller
{
    /**
     * Beranda: hero, fasilitas, dan kamar pilihan (kamar tersedia lebih dulu).
     */
    public function home()
    {
        $kamarList = Kamar::with('fasilitas')
            ->orderByRaw("CASE WHEN status_kamar = 'kosong' THEN 0 ELSE 1 END")
            ->orderBy('nomor_kamar')
            ->take(3)
            ->get()
            ->map(fn ($k) => Tampil::kamar($k));

        $hargaMulai = Kamar::where('status_kamar', Kamar::KOSONG)->min('biaya_sewa') ?? Kamar::min('biaya_sewa');

        return Inertia::render('Home', [
            'kamarList'     => $kamarList,
            'fasilitasList' => Fasilitas::take(4)->get()->map(fn ($f) => Tampil::fasilitas($f)),
            'hargaMulai'    => $hargaMulai ? Periode::hargaRingkas((int) $hargaMulai) : null,
        ]);
    }

    /**
     * Katalog kamar dengan pencarian nomor, filter status, dan urut harga.
     */
    public function katalogKamar(Request $request)
    {
        $query = Kamar::with('fasilitas');

        if ($request->filled('search')) {
            $query->where('nomor_kamar', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status_kamar', $request->status);
        }

        match ($request->sort) {
            'lowest'  => $query->orderBy('biaya_sewa', 'asc'),
            'highest' => $query->orderBy('biaya_sewa', 'desc'),
            default   => $query->orderBy('nomor_kamar', 'asc'),
        };

        return Inertia::render('Kamar/Index', [
            'kamars'  => $query->paginate(9)->withQueryString()->through(fn ($k) => Tampil::kamar($k)),
            'filters' => [
                'search' => (string) $request->search,
                'status' => $request->status ?: 'all',
                'sort'   => (string) $request->sort,
            ],
        ]);
    }

    /**
     * Detail kamar + form booking.
     */
    public function detailKamar(Request $request, Kamar $kamar)
    {
        $kamar->load('fasilitas');

        // "Ubah Data" dari halaman review mengisi ulang form dengan data booking sebelumnya.
        $prefill = $request->filled('booking')
            ? Booking::where('token', $request->booking)
                ->where('id_kamar', $kamar->id_kamar)
                ->where('status_booking', Booking::MENUNGGU)
                ->first()
            : null;

        $kos = KosInfo::first();

        return Inertia::render('Kamar/Show', [
            'kamar'    => Tampil::kamar($kamar),
            'tersedia' => $kamar->status_kamar === Kamar::KOSONG || $prefill !== null,
            'prefill'  => $prefill ? [
                'token'           => $prefill->token,
                'nama_pendaftar'  => $prefill->nama_pendaftar,
                'no_ktp'          => $prefill->no_ktp,
                'kontak'          => $prefill->kontak,
                'tanggal_booking' => $prefill->tanggal_booking->toDateString(),
            ] : null,
            'wa'       => $kos?->whatsappLink('Halo, saya ingin bertanya tentang kamar ' . $kamar->nomor_kamar . '.'),
            'hariIni'  => now()->toDateString(),
        ]);
    }

    /**
     * Tentang kos: cerita, peraturan, dan lokasi (semua diatur admin).
     */
    public function tentang()
    {
        $kos = KosInfo::first();

        return Inertia::render('Tentang', [
            'info' => $kos ? [
                'nama'      => $kos->nama_kos,
                'deskripsi' => $kos->deskripsi_kos,
                'lokasi'    => $kos->lokasi,
                'kontak'    => $kos->kontak_pemilik,
                'peraturan' => $kos->daftar_peraturan,
                'wa'        => $kos->whatsappLink('Halo, saya ingin bertanya tentang ' . $kos->nama_kos . '.'),
                'maps'      => $kos->maps_url,
            ] : null,
        ]);
    }
}
