<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Penghuni;
use App\Services\PenghuniService;
use App\Support\Tampil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PenghuniController extends Controller
{
    public function __construct(private PenghuniService $penghunis)
    {
    }

    public function index(Request $request)
    {
        $query = Penghuni::with(['sewaKamars' => fn ($q) => $q->with('kamar')->latest('id_sewa')]);

        if ($request->filled('search')) {
            $s = $request->search;
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($s, $like) {
                $q->where('nama', $like, "%{$s}%")->orWhere('kontak', $like, "%{$s}%");
            });
        }

        $status = $request->input('status', 'all');
        if ($status === 'aktif') {
            $query->where('status_aktif', true);
        } elseif ($status === 'nonaktif') {
            $query->where('status_aktif', false);
        }

        return Inertia::render('Admin/Penghuni/Index', [
            'penghunis' => $query->latest('id_penghuni')->paginate(10)->withQueryString()->through(function (Penghuni $p) {
                $sewa = $p->sewaKamars->first();

                return [
                    'id'      => $p->id_penghuni,
                    'nama'    => $p->nama,
                    'kontak'  => $p->kontak,
                    'aktif'   => $p->status_aktif,
                    'kamar'   => $sewa?->kamar?->nomor_kamar,
                    'masuk'   => Tampil::tanggal($sewa?->tanggal_masuk),
                    'keluar'  => Tampil::tanggal($sewa?->tanggal_keluar),
                ];
            }),
            'filters'   => ['search' => (string) $request->search, 'status' => $status],
        ]);
    }

    /**
     * Identitas lengkap penghuni (hanya untuk admin) beserta riwayat sewa & pembayaran.
     */
    public function show($id)
    {
        $p = Penghuni::with(['sewaKamars.kamar', 'sewaKamars.pembayarans'])->findOrFail($id);

        return Inertia::render('Admin/Penghuni/Show', [
            'penghuni' => [
                'id'     => $p->id_penghuni,
                'nama'   => $p->nama,
                'no_ktp' => $p->no_ktp,
                'kontak' => $p->kontak,
                'aktif'  => $p->status_aktif,
                'terdaftar' => Tampil::tanggal($p->created_at),
            ],
            'riwayat'  => $p->sewaKamars->sortByDesc('id_sewa')->values()->map(fn ($s) => [
                'id'       => $s->id_sewa,
                'kamar'    => $s->kamar?->nomor_kamar,
                'masuk'    => Tampil::tanggal($s->tanggal_masuk),
                'keluar'   => Tampil::tanggal($s->tanggal_keluar),
                'pembayaran' => $s->pembayarans->map(fn ($b) => [
                    'id'      => $b->id_pembayaran,
                    'tanggal' => Tampil::tanggal($b->tanggal_bayar),
                    'jumlah'  => $b->jumlah_bayar,
                    'metode'  => $b->metode_pembayaran,
                    'status'  => $b->status_pembayaran,
                ])->values(),
            ]),
            'bisaHapus' => ! $p->status_aktif,
        ]);
    }

    public function keluar($id)
    {
        $p = Penghuni::findOrFail($id);
        $this->penghunis->keluar($p);

        return back()->with('success', $p->nama . ' ditandai sudah keluar. Kamar yang ditempati kembali kosong.');
    }

    public function destroy($id)
    {
        $p = Penghuni::findOrFail($id);

        try {
            $this->penghunis->hapus($p);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.penghuni.index')
            ->with('success', 'Data penghuni dihapus. Catatan pembayaran tetap tersimpan tanpa identitas.');
    }
}
