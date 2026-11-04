<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\KosInfo;
use App\Models\Pembayaran;
use App\Services\BookingService;
use App\Support\PesanKonfirmasi;
use App\Support\Tampil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KonfirmasiPembayaranController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    public function index(Request $request)
    {
        $query = Pembayaran::with(['sewaKamar.penghuni', 'sewaKamar.kamar', 'sewaKamar.booking']);

        $status = $request->input('status', Pembayaran::MENUNGGU);
        if ($status !== 'all') {
            $query->where('status_pembayaran', $status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($outer) use ($search, $like) {
                $outer->whereHas('sewaKamar.penghuni', fn ($q) => $q->where('nama', $like, '%' . $search . '%'))
                    ->orWhereHas('sewaKamar.kamar', fn ($q) => $q->where('nomor_kamar', $like, '%' . $search . '%'))
                    ->orWhereHas('sewaKamar.booking', fn ($q) => $q->where('nama_pendaftar', $like, '%' . $search . '%'));
            });
        }

        $namaKos = KosInfo::first()?->nama_kos;

        return Inertia::render('Admin/Konfirmasi/Index', [
            'pembayarans' => $query->latest('tanggal_bayar')->paginate(10)->withQueryString()
                ->through(fn (Pembayaran $p) => $this->ringkas($p, $namaKos)),
            'filters'     => ['search' => (string) $request->search, 'status' => $status],
        ]);
    }

    public function show($id_pembayaran)
    {
        $p = Pembayaran::with(['sewaKamar.penghuni', 'sewaKamar.kamar', 'sewaKamar.booking'])->findOrFail($id_pembayaran);
        $sewa = $p->sewaKamar;
        $booking = $sewa->booking;
        $wa = PesanKonfirmasi::untuk($p, KosInfo::first()?->nama_kos);

        return Inertia::render('Admin/Konfirmasi/Show', [
            'pembayaran' => $this->ringkas($p) + [
                'wa_pesan'   => $wa['pesan'] ?? null,
                'alasan'     => $p->alasan_penolakan,
                'ada_bukti'  => (bool) $p->bukti_bayar,
                'bukti_url'  => $p->bukti_bayar ? route('admin.konfirmasi.bukti', $p->id_pembayaran) : null,
                'masuk'      => Tampil::tanggal($sewa->tanggal_masuk),
                'tenggat'    => Tampil::tanggal($sewa->tenggat_pembayaran),
                'batas'      => $booking?->batas_waktu ? Tampil::tanggalJam($booking->batas_waktu) : null,
                'kamar_status' => $sewa->kamar?->status_kamar,
                'penghuni'   => $sewa->penghuni ? [
                    'id'     => $sewa->penghuni->id_penghuni,
                    'nama'   => $sewa->penghuni->nama,
                    'no_ktp' => $sewa->penghuni->no_ktp,
                    'kontak' => $sewa->penghuni->kontak,
                ] : null,
            ],
        ]);
    }

    /**
     * Foto bukti transfer dari disk privat (hanya admin yang login).
     */
    public function bukti($id_pembayaran)
    {
        $pembayaran = Pembayaran::findOrFail($id_pembayaran);

        abort_unless(
            $pembayaran->bukti_bayar && Storage::disk(BookingService::DISK_BUKTI)->exists($pembayaran->bukti_bayar),
            404
        );

        return Storage::disk(BookingService::DISK_BUKTI)->response($pembayaran->bukti_bayar);
    }

    public function approve($id_pembayaran)
    {
        try {
            $pembayaran = $this->bookings->setujui((int) $id_pembayaran);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.konfirmasi.index')
            ->with('success', 'Pembayaran ' . ($pembayaran->sewaKamar->penghuni?->nama ?? '') . ' dikonfirmasi. Kamar kini berstatus TERISI.')
            ->with('wa', $this->kabar($pembayaran));
    }

    public function reject(Request $request, $id_pembayaran)
    {
        $data = $request->validate(['alasan_penolakan' => 'required|string|max:255'], [
            'alasan_penolakan.required' => 'Alasan penolakan pembayaran wajib diisi.',
        ]);

        try {
            $pembayaran = $this->bookings->tolak((int) $id_pembayaran, $data['alasan_penolakan']);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.konfirmasi.index')
            ->with('warning', 'Pembayaran ' . ($pembayaran->sewaKamar->penghuni?->nama ?? '') . ' ditolak. Kamar dilepas kembali.')
            ->with('wa', $this->kabar($pembayaran));
    }

    /** Tautan WhatsApp untuk memberi kabar ke penyewa (null bila tidak ada nomor). */
    private function kabar(Pembayaran $p): ?array
    {
        $p->load(['sewaKamar.booking', 'sewaKamar.kamar', 'sewaKamar.penghuni']);

        $wa = PesanKonfirmasi::untuk($p, KosInfo::first()?->nama_kos);

        return $wa ? ['url' => $wa['url'], 'nama' => $wa['nama'], 'jenis' => $wa['jenis']] : null;
    }

    private function ringkas(Pembayaran $p, ?string $namaKos = null): array
    {
        $sewa = $p->sewaKamar;
        $wa = PesanKonfirmasi::untuk($p, $namaKos ?? KosInfo::first()?->nama_kos);

        return [
            'id'       => $p->id_pembayaran,
            'nama'     => $sewa->penghuni?->nama ?? $sewa->booking?->nama_pendaftar ?? '-',
            'kontak'   => $sewa->penghuni?->kontak,
            'kamar'    => $sewa->kamar?->nomor_kamar,
            'jumlah'   => $p->jumlah_bayar,
            'metode'   => $p->metode_pembayaran,
            'status'   => $p->status_pembayaran,
            'tanggal'  => Tampil::tanggalJam($p->tanggal_bayar),
            'ada_bukti' => (bool) $p->bukti_bayar,
            'wa'        => $wa ? ['url' => $wa['url'], 'jenis' => $wa['jenis']] : null,
        ];
    }
}