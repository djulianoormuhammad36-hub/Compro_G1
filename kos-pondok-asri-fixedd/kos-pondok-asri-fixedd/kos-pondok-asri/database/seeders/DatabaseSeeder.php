<?php

namespace Database\Seeders;

use App\Models\Fasilitas;
use App\Models\Kamar;
use App\Models\KosInfo;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data contoh agar halaman publik & admin langsung terisi.
     */
    public function run(): void
    {
        $this->seedAdmin();

        KosInfo::firstOrCreate(['nama_kos' => 'Pondok Asri'], [
            'deskripsi_kos'  => 'Pondok Asri menghadirkan hunian yang bersih, tenang, dan praktis. Kami ingin setiap penghuni merasa betah melalui fasilitas terawat, informasi yang jelas, dan suasana yang saling menghargai.',
            'info_pembayaran' => "CONTOH — ganti di menu Info Kos:\nBCA 1234567890\na.n. Pengelola Pondok Asri",
            'peraturan_kos'  => "Menjaga kebersihan kamar dan area bersama.\nTamu wajib lapor ke pengelola.\nJam tenang mulai pukul 22.00.\nDilarang membawa barang berbahaya.",
            'kontak_pemilik' => '+62 812-3456-7890',
            'lokasi'         => 'Jl. Contoh No. 12, Purbalingga, Jawa Tengah',
        ]);

        $fasilitas = collect([
            ['Wi-Fi', 'Internet cepat 24 jam di seluruh area kos.'],
            ['Dapur Bersama', 'Dapur lengkap dengan kompor dan peralatan masak.'],
            ['Parkir Motor', 'Area parkir luas dan aman.'],
            ['Laundry', 'Area jemur dan mesin cuci bersama.'],
            ['AC', 'Pendingin ruangan di kamar.'],
            ['Kamar Mandi Dalam', 'Kamar mandi pribadi di dalam kamar.'],
        ])->mapWithKeys(fn ($f) => [
            $f[0] => Fasilitas::firstOrCreate(['nama_fasilitas' => $f[0]], ['deskripsi_fasilitas' => $f[1]]),
        ]);

        $kamars = [
            ['101', 700000, 'kosong',  'Kamar standar 3x3 m dengan jendela.',               ['Wi-Fi', 'Parkir Motor']],
            ['102', 850000, 'kosong',  'Kamar 3x4 m, kasur dan lemari tersedia.',           ['Wi-Fi', 'Kamar Mandi Dalam']],
            ['103', 1100000, 'kosong', 'Kamar luas ber-AC dengan kamar mandi dalam.',       ['Wi-Fi', 'AC', 'Kamar Mandi Dalam']],
            ['201', 900000, 'terisi',  'Kamar lantai 2 dengan balkon kecil.',               ['Wi-Fi', 'Laundry']],
            ['202', 1200000, 'reservasi', 'Kamar ber-AC lantai 2, pencahayaan bagus.',      ['Wi-Fi', 'AC']],
        ];

        foreach ($kamars as [$nomor, $harga, $status, $detail, $fas]) {
            $kamar = Kamar::firstOrCreate(['nomor_kamar' => $nomor], [
                'biaya_sewa'   => $harga,
                'status_kamar' => $status,
                'detail_kamar' => $detail,
            ]);
            $kamar->fasilitas()->syncWithoutDetaching(array_map(fn ($n) => $fasilitas[$n]->id_fasilitas, $fas));
        }
    }

        /**
     * Akun admin dari seeder hanya dibuat bila ADMIN_EMAIL & ADMIN_PASSWORD diisi di .env.
     * Cara yang disarankan: buka /setup di browser (atau php artisan admin:create).
     */
    private function seedAdmin(): void
    {
        $email = config('app.admin.email');
        $password = config('app.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->info('Akun admin tidak dibuat oleh seeder. Buka /setup di browser untuk membuat akun admin pertama.');

            return;
        }

        if (User::where('email', $email)->exists()) {
            return;
        }

        $admin = new User();
        $admin->name = config('app.admin.name');
        $admin->email = $email;
        $admin->password = Hash::make($password);
        $admin->is_admin = true;
        $admin->email_verified_at = now();
        $admin->save();
    }
}
