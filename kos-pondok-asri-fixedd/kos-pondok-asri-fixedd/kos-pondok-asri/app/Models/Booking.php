<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    public const MENUNGGU = 'Menunggu';              // form diisi, belum bayar
    public const DIBAYAR = 'Dibayar';                // bukti bayar dikirim, menunggu admin
    public const TERKONFIRMASI = 'Terkonfirmasi';    // pembayaran disetujui admin
    public const DITOLAK = 'Ditolak';                // pembayaran ditolak admin
    public const HANGUS = 'Hangus';                  // lewat batas waktu 1x24 jam

    protected $table = 'booking';
    protected $primaryKey = 'id_booking';

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->token ??= (string) Str::uuid();
        });
    }

    /**
     * Route model binding memakai token acak (bukan ID berurutan),
     * supaya halaman publik tidak bisa ditebak.
     */
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    protected $fillable = [
        'id_kamar',
        'nama_pendaftar',
        'no_ktp',
        'kontak',
        'tanggal_booking',
        'status_booking',
        'batas_waktu',
    ];

    protected $casts = [
        'no_ktp'          => 'encrypted',
        'tanggal_booking' => 'date',
        'batas_waktu'     => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    /** Kode booking yang mudah dibaca, mis. BK-20261020-001. */
    public function getKodeAttribute(): string
    {
        return 'BK-' . ($this->created_at ?? now())->format('Ymd') . '-' . str_pad((string) $this->id_booking, 3, '0', STR_PAD_LEFT);
    }

    /** KTP tersamarkan: hanya 4 digit terakhir yang terlihat. */
    public function getKtpSamaranAttribute(): string
    {
        return str_repeat('•', 12) . substr((string) $this->no_ktp, -4);
    }

    public function sewaKamar()
    {
        return $this->hasOne(SewaKamar::class, 'id_booking', 'id_booking');
    }

    public function kamar()
    {
        return $this->belongsTo(
            Kamar::class,
            'id_kamar',
            'id_kamar'
        );
    }
}