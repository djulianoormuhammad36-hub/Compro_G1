<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\KosInfoRequest;
use App\Models\KosInfo;
use App\Support\Media;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class InfoKosController extends Controller
{
    /** Kolom baru dari migrasi terbaru; bila belum ada, penyimpanan akan gagal. */
    private function perluMigrasi(): bool
    {
        return ! Schema::hasColumns('kos_info', ['deskripsi_kos', 'info_pembayaran', 'foto_qris']);
    }

    public function edit()
    {
        $kos = KosInfo::first();

        return Inertia::render('Admin/InfoKos/Edit', [
            'perluMigrasi'  => $this->perluMigrasi(),
            'batasUnggah'   => Media::batasUnggah(),
            'kos' => [
                'nama_kos'        => $kos?->nama_kos ?? '',
                'deskripsi_kos'   => $kos?->deskripsi_kos ?? '',
                'kontak_pemilik'  => $kos?->kontak_pemilik ?? '',
                'lokasi'          => $kos?->lokasi ?? '',
                'peraturan_kos'   => $kos?->peraturan_kos ?? '',
                'info_pembayaran' => $kos?->info_pembayaran ?? '',
                'foto_url'        => $kos?->foto_url,
                'qris_url'        => $kos?->qris_url,
            ],
        ]);
    }

    public function update(KosInfoRequest $request)
    {
        if ($this->perluMigrasi()) {
            $pesan = 'Database belum diperbarui. Jalankan "php artisan migrate" di folder proyek, lalu coba simpan lagi.';

            return $request->wantsJson() ? response()->json(['message' => $pesan], 409) : back()->with('error', $pesan);
        }

        $kos = KosInfo::first() ?? new KosInfo();
        $v = $request->validated();

        $data = [
            'nama_kos'        => $v['nama_kos'],
            'deskripsi_kos'   => $v['deskripsi_kos'] ?? null,
            'kontak_pemilik'  => $v['kontak_pemilik'] ?? '',
            'lokasi'          => $v['lokasi'],
            'peraturan_kos'   => $v['peraturan_kos'] ?? '',
            'info_pembayaran' => $v['info_pembayaran'] ?? null,
            'foto_kos'        => $kos->foto_kos,
            'foto_qris'       => $kos->foto_qris,
        ];

        if ($request->hasFile('foto_kos')) {
            $data['foto_kos'] = Media::simpan($request->file('foto_kos'), 'kos', $kos->foto_kos);
        } elseif ($request->boolean('hapus_foto_kos')) {
            Media::hapus($kos->foto_kos);
            $data['foto_kos'] = null;
        }

        if ($request->hasFile('foto_qris')) {
            $data['foto_qris'] = Media::simpan($request->file('foto_qris'), 'kos', $kos->foto_qris);
        } elseif ($request->boolean('hapus_foto_qris')) {
            Media::hapus($kos->foto_qris);
            $data['foto_qris'] = null;
        }

        $kos->fill($data)->save();

        // Form React mengirim lewat fetch (Accept: application/json) dan menampilkan hasilnya sendiri.
        if ($request->wantsJson()) {
            return response()->json(['message' => 'Info kos berhasil diperbarui.']);
        }

        return redirect()->route('admin.info-kos.edit')->with('success', 'Info kos berhasil diperbarui.');
    }
}
