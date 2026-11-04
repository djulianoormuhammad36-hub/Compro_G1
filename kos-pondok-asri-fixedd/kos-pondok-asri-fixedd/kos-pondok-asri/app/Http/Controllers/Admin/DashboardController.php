<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Support\Tampil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function index()
    {
        $tahun = now()->year;

        // ---- Kartu ringkasan ----
        $statusKamar = Kamar::select('status_kamar', DB::raw('COUNT(*) as total'))
            ->groupBy('status_kamar')->pluck('total', 'status_kamar');

        $lunas = Pembayaran::where('status_pembayaran', Pembayaran::TERKONFIRMASI);

        $stat = [
            'totalKamar'         => (int) $statusKamar->sum(),
            'penghuniAktif'      => Penghuni::where('status_aktif', true)->count(),
            'menungguKonfirmasi' => Pembayaran::where('status_pembayaran', Pembayaran::MENUNGGU)->count(),
            'pemasukanBulanIni'  => (int) (clone $lunas)->whereMonth('tanggal_bayar', now()->month)->whereYear('tanggal_bayar', $tahun)->sum('jumlah_bayar'),
            'pemasukanTahunIni'  => (int) (clone $lunas)->whereYear('tanggal_bayar', $tahun)->sum('jumlah_bayar'),
        ];

        // ---- Grafik 1: pemasukan per bulan (tahun berjalan) ----
        $driver = DB::connection()->getDriverName();
        $bulanExpr = match ($driver) {
            'sqlite' => "CAST(strftime('%m', tanggal_bayar) AS INTEGER)",
            'pgsql'  => 'CAST(EXTRACT(MONTH FROM tanggal_bayar) AS INTEGER)',
            default  => 'MONTH(tanggal_bayar)',
        };

        $perBulan = (clone $lunas)->whereYear('tanggal_bayar', $tahun)
            ->select(DB::raw($bulanExpr . ' as bulan'), DB::raw('SUM(jumlah_bayar) as total'))
            ->groupBy('bulan')->pluck('total', 'bulan');

        $pemasukanBulanan = collect(range(1, 12))->map(fn ($b) => [
            'bulan' => self::BULAN[$b - 1],
            'total' => (int) ($perBulan[$b] ?? 0),
        ])->all();

        // ---- Grafik 2: komposisi status kamar ----
        $komposisiKamar = [
            ['key' => Kamar::KOSONG,    'name' => 'Tersedia',   'value' => (int) ($statusKamar[Kamar::KOSONG] ?? 0)],
            ['key' => Kamar::RESERVASI, 'name' => 'Dipesan',    'value' => (int) ($statusKamar[Kamar::RESERVASI] ?? 0)],
            ['key' => Kamar::TERISI,    'name' => 'Terisi',     'value' => (int) ($statusKamar[Kamar::TERISI] ?? 0)],
        ];

        // ---- Grafik 3: booking masuk 14 hari terakhir ----
        $mulai = now()->subDays(13)->startOfDay();
        $perHari = Booking::where('created_at', '>=', $mulai)
            ->select(DB::raw('DATE(created_at) as hari'), DB::raw('COUNT(*) as jumlah'))
            ->groupBy('hari')->pluck('jumlah', 'hari');

        $bookingHarian = collect(range(0, 13))->map(function ($i) use ($mulai, $perHari) {
            $hari = $mulai->copy()->addDays($i);

            return ['hari' => $hari->translatedFormat('d M'), 'jumlah' => (int) ($perHari[$hari->toDateString()] ?? 0)];
        })->all();

        // ---- Grafik 4: metode pembayaran (pembayaran terkonfirmasi) ----
        $metode = (clone $lunas)->select('metode_pembayaran', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('metode_pembayaran')->get()
            ->map(fn ($m) => ['name' => $m->metode_pembayaran, 'value' => (int) $m->jumlah])->all();

        // ---- Aktivitas terbaru ----
        $aktivitas = Pembayaran::with(['sewaKamar.penghuni', 'sewaKamar.kamar', 'sewaKamar.booking'])
            ->latest('id_pembayaran')->take(6)->get()
            ->map(fn (Pembayaran $p) => [
                'nama'    => $p->sewaKamar->penghuni?->nama ?? $p->sewaKamar->booking?->nama_pendaftar ?? '-',
                'kamar'   => $p->sewaKamar->kamar?->nomor_kamar,
                'metode'  => $p->metode_pembayaran,
                'status'  => $p->status_pembayaran,
                'waktu'   => $p->created_at->diffForHumans(),
                'id'      => $p->id_pembayaran,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stat'             => $stat,
            'tahun'            => $tahun,
            'pemasukanBulanan' => $pemasukanBulanan,
            'komposisiKamar'   => $komposisiKamar,
            'bookingHarian'    => $bookingHarian,
            'metodePembayaran' => $metode,
            'aktivitas'        => $aktivitas,
        ]);
    }

    /**
     * Unduh ringkasan operasional (CSV).
     */
    public function exportReport()
    {
        $filename = 'laporan-operasional-' . date('Y-m-d') . '.csv';

        $callback = function () {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['RINGKASAN OPERASIONAL KOS', date('d-m-Y H:i:s')]);
            fputcsv($file, []);
            fputcsv($file, ['Metrik', 'Jumlah / Total']);
            fputcsv($file, ['Total Kamar', Kamar::count()]);
            fputcsv($file, ['Kamar Tersedia', Kamar::where('status_kamar', Kamar::KOSONG)->count()]);
            fputcsv($file, ['Kamar Dipesan', Kamar::where('status_kamar', Kamar::RESERVASI)->count()]);
            fputcsv($file, ['Kamar Terisi', Kamar::where('status_kamar', Kamar::TERISI)->count()]);
            fputcsv($file, ['Penghuni Aktif', Penghuni::where('status_aktif', true)->count()]);
            fputcsv($file, ['Pemasukan Bulan Ini', 'Rp ' . number_format(
                Pembayaran::where('status_pembayaran', Pembayaran::TERKONFIRMASI)
                    ->whereMonth('tanggal_bayar', now()->month)->whereYear('tanggal_bayar', now()->year)
                    ->sum('jumlah_bayar'), 0, ',', '.'
            )]);

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
