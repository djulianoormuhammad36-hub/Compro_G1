<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Pembayaran extends Model
{
    use HasFactory;

    public const MENUNGGU = 'Menunggu Konfirmasi';
    public const TERKONFIRMASI = 'Terkonfirmasi';
    public const DITOLAK = 'Ditolak';
    public const HANGUS = 'Hangus';

    public const METODE_TRANSFER = 'Transfer Bank';
    public const METODE_QRIS = 'QRIS';
    public const METODE_LANGSUNG = 'Bayar Langsung';

    protected $table = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';

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
        'id_sewa',
        'tanggal_bayar',
        'jumlah_bayar',
        'metode_pembayaran',
        'bukti_bayar',
        'status_pembayaran',
        'alasan_penolakan',
    ];

    protected $casts = [
        'tanggal_bayar' => 'datetime',
        'jumlah_bayar'  => 'integer',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function sewaKamar()
    {
        return $this->belongsTo(
            SewaKamar::class,
            'id_sewa',
            'id_sewa'
        );
    }
}