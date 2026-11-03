<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kamar extends Model
{
    use HasFactory;

    public const KOSONG = 'kosong';
    public const RESERVASI = 'reservasi';
    public const TERISI = 'terisi';

    protected $table = 'kamar';
    protected $primaryKey = 'id_kamar';

    protected $fillable = [
        'nomor_kamar',
        'detail_kamar',
        'foto_kamar',
        'biaya_sewa',
        'status_kamar',
    ];

    public function getFotoUrlAttribute(): ?string
    {
        return \App\Support\Media::url($this->foto_kamar);
    }

    protected $casts = [
        'biaya_sewa' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function fasilitas()
    {
        return $this->belongsToMany(
            Fasilitas::class,
            'kamar_fasilitas',
            'id_kamar',
            'id_fasilitas'
        );
    }

    public function bookings()
    {
        return $this->hasMany(
            Booking::class,
            'id_kamar',
            'id_kamar'
        );
    }

    public function sewaKamars()
    {
        return $this->hasMany(
            SewaKamar::class,
            'id_kamar',
            'id_kamar'
        );
    }

    public function isTersedia(): bool
    {
        return $this->status_kamar === self::KOSONG;
    }

    public function isTerisi(): bool
    {
        return $this->status_kamar === self::TERISI;
    }
}