<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fasilitas extends Model
{
    use HasFactory;

    protected $table = 'fasilitas';
    protected $primaryKey = 'id_fasilitas';

    protected $fillable = [
        'nama_fasilitas',
        'deskripsi_fasilitas',
        'foto_fasilitas',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** Ikon FontAwesome berdasarkan nama fasilitas. */
    public function getIconAttribute(): string
    {
        $nama = strtolower($this->nama_fasilitas);

        return match (true) {
            str_contains($nama, 'wi-fi'), str_contains($nama, 'wifi'), str_contains($nama, 'internet') => 'fa-wifi',
            str_contains($nama, 'parkir')  => 'fa-square-parking',
            str_contains($nama, 'dapur')   => 'fa-kitchen-set',
            str_contains($nama, 'laundry'), str_contains($nama, 'cuci') => 'fa-shirt',
            str_contains($nama, 'ac'), str_contains($nama, 'pendingin') => 'fa-snowflake',
            str_contains($nama, 'mandi')   => 'fa-shower',
            str_contains($nama, 'cctv'), str_contains($nama, 'aman') => 'fa-shield-halved',
            default => 'fa-circle-check',
        };
    }

    public function kamars()
    {
        return $this->belongsToMany(
            Kamar::class,
            'kamar_fasilitas',
            'id_fasilitas',
            'id_kamar'
        );
    }
}