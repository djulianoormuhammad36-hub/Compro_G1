<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Foto yang tampil di website (kamar, kos, QRIS) disimpan di disk publik.
 * Bukti transfer TIDAK lewat sini: itu privat (lihat BookingService::DISK_BUKTI).
 */
class Media
{
    public const DISK = 'public';

    /** Batas aplikasi per foto (sesuai aturan validasi: 5 MB). */
    public const BATAS_APLIKASI = 5 * 1024 * 1024;

    /**
     * Batas unggah foto yang benar-benar berlaku: yang terkecil antara 5 MB,
     * upload_max_filesize, dan post_max_size di php.ini (bawaan PHP/XAMPP hanya 2 MB).
     */
    public static function batasUnggah(): int
    {
        $batas = array_filter([
            self::BATAS_APLIKASI,
            self::keByte(ini_get('upload_max_filesize')),
            self::keByte(ini_get('post_max_size')),
        ], fn ($b) => $b > 0);

        return (int) min($batas);
    }

    private static function keByte(string|false $nilai): int
    {
        if ($nilai === false || $nilai === '') {
            return 0;
        }

        $angka = (int) $nilai;

        return match (strtolower(substr(trim($nilai), -1))) {
            'g' => $angka * 1024 ** 3,
            'm' => $angka * 1024 ** 2,
            'k' => $angka * 1024,
            default => $angka,
        };
    }

    /** URL relatif agar tidak bergantung pada APP_URL. */
    public static function url(?string $path): ?string
    {
        return $path ? '/storage/' . ltrim($path, '/') : null;
    }

    public static function simpan(UploadedFile $file, string $folder, ?string $lama = null): string
    {
        $path = $file->store($folder, self::DISK);

        self::hapus($lama);

        return $path;
    }

    public static function hapus(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
