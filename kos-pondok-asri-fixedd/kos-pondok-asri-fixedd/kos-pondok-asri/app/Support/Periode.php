<?php

namespace App\Support;

use Carbon\CarbonInterface;

class Periode
{
    /**
     * Periode sewa 1 bulan, mis. "20 Okt – 19 Nov 2026".
     */
    public static function satuBulan(CarbonInterface $mulai): string
    {
        $selesai = $mulai->copy()->addMonthNoOverflow()->subDay();

        $awal = $mulai->year === $selesai->year
            ? $mulai->translatedFormat('d M')
            : $mulai->translatedFormat('d M Y');

        return $awal . ' – ' . $selesai->translatedFormat('d M Y');
    }

    /** "Rp 1,2 jt" / "Rp 700 rb" untuk label harga ringkas. */
    public static function hargaRingkas(int $nilai): string
    {
        if ($nilai >= 1_000_000) {
            return 'Rp ' . number_format($nilai / 1_000_000, 1, ',', '.') . ' jt';
        }

        return 'Rp ' . number_format($nilai / 1000, 0, ',', '.') . ' rb';
    }
}
