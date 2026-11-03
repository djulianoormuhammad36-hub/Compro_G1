<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penghuni extends Model
{
    use HasFactory;

    protected $table = 'penghuni';

    protected static function booted(): void
    {
        // Simpan hash deterministik agar KTP terenkripsi tetap bisa dicari.
        static::saving(function (self $penghuni) {
            if ($penghuni->isDirty('no_ktp') || empty($penghuni->no_ktp_hash)) {
                $penghuni->no_ktp_hash = self::hashKtp($penghuni->no_ktp);
            }
        });
    }

    public static function hashKtp(string $noKtp): string
    {
        return hash_hmac('sha256', preg_replace('/\D/', '', $noKtp), config('app.key'));
    }

    public static function findByKtp(string $noKtp): ?self
    {
        return static::where('no_ktp_hash', self::hashKtp($noKtp))->first();
    }
    protected $primaryKey = 'id_penghuni';

    protected $fillable = [
        'nama',
        'no_ktp',
        'kontak',
        'status_aktif',
    ];

    protected $casts = [
        'no_ktp'       => 'encrypted',
        'status_aktif' => 'boolean',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function sewaKamars()
    {
        return $this->hasMany(
            SewaKamar::class,
            'id_penghuni',
            'id_penghuni'
        );
    }
}