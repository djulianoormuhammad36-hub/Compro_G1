<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SewaKamar extends Model
{
    use HasFactory;

    protected $table = 'sewa_kamar';
    protected $primaryKey = 'id_sewa';

    protected $fillable = [
        'id_booking',
        'id_penghuni',
        'id_kamar',
        'tanggal_masuk',
        'tanggal_keluar',
        'tenggat_pembayaran',
    ];

    protected $casts = [
        'tanggal_masuk'      => 'date',
        'tanggal_keluar'     => 'date',
        'tenggat_pembayaran' => 'date',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'id_booking', 'id_booking');
    }

    public function penghuni()
    {
        return $this->belongsTo(
            Penghuni::class,
            'id_penghuni',
            'id_penghuni'
        );
    }

    public function kamar()
    {
        return $this->belongsTo(
            Kamar::class,
            'id_kamar',
            'id_kamar'
        );
    }

    public function pembayarans()
    {
        return $this->hasMany(
            Pembayaran::class,
            'id_sewa',
            'id_sewa'
        );
    }
}