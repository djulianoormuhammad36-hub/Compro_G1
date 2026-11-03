<?php

namespace App\Support;

class Whatsapp
{
    /** Nomor ke format internasional tanpa tanda: 0812-3456-7890 / +62 812... -> 6281234567890. */
    public static function nomor(?string $kontak): ?string
    {
        $digit = preg_replace('/\D/', '', (string) $kontak);

        if (strlen($digit) < 8) {
            return null;
        }

        if (str_starts_with($digit, '0')) {
            return '62' . substr($digit, 1);
        }

        return str_starts_with($digit, '8') ? '62' . $digit : $digit;
    }

    /** Tautan "klik untuk chat" WhatsApp dengan pesan yang sudah terisi (opsional). */
    public static function link(?string $kontak, ?string $pesan = null): ?string
    {
        $nomor = self::nomor($kontak);

        if (! $nomor) {
            return null;
        }

        return 'https://wa.me/' . $nomor . ($pesan ? '?text=' . rawurlencode($pesan) : '');
    }
}