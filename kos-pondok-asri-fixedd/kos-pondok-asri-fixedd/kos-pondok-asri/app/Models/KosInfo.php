<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KosInfo extends Model
{
    use HasFactory;

    protected $table = 'kos_info';
    protected $primaryKey = 'id_kos';

    protected $fillable = [
        'nama_kos',
        'peraturan_kos',
        'kontak_pemilik',
        'lokasi',
        'foto_kos',
        'deskripsi_kos',
        'info_pembayaran',
        'foto_qris',
    ];

    /** Tautan WhatsApp pemilik (nomor 08xx dinormalisasi ke 62xx). */
        /** Tautan WhatsApp pemilik (nomor 08xx dinormalisasi ke 62xx); null bila kontak kosong. */
    public function getWhatsappUrlAttribute(): ?string
    {
        return \App\Support\Whatsapp::link($this->kontak_pemilik);
    }

    /** Tautan WhatsApp dengan pesan awal (opsional). */
    public function whatsappLink(?string $pesan = null): ?string
    {
        return \App\Support\Whatsapp::link($this->kontak_pemilik, $pesan);
    }

    public function getFotoUrlAttribute(): ?string
    {
        return \App\Support\Media::url($this->foto_kos);
    }

    public function getQrisUrlAttribute(): ?string
    {
        return \App\Support\Media::url($this->foto_qris);
    }

    public function getMapsUrlAttribute(): ?string
    {
        return $this->lokasi
            ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($this->lokasi)
            : null;
    }

    /** Peraturan kos sebagai daftar (satu baris = satu aturan). */
    public function getDaftarPeraturanAttribute(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->peraturan_kos))));
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}