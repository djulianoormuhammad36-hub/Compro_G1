<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KosInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_kos'        => ['required', 'string', 'max:100'],
            'deskripsi_kos'   => ['nullable', 'string', 'max:3000'],
            'kontak_pemilik'  => ['nullable', 'regex:/^\+?[0-9][0-9\s\-]{7,18}$/'], // opsional: hanya untuk tombol "Hubungi" di website
            'lokasi'          => ['required', 'string', 'max:255'],
            'peraturan_kos'   => ['nullable', 'string', 'max:3000'],
            'info_pembayaran' => ['nullable', 'string', 'max:2000'],
            'foto_kos'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'foto_qris'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'hapus_foto_kos'  => ['nullable', 'boolean'],
            'hapus_foto_qris' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kos.required'       => 'Nama kos wajib diisi.',
            'kontak_pemilik.regex'    => 'Format kontak tidak valid (contoh: 081234567890).',
            'lokasi.required'         => 'Alamat/lokasi kos wajib diisi.',
            'foto_kos.uploaded'       => 'Foto gagal diunggah. Ukuran file kemungkinan melebihi batas server (upload_max_filesize di php.ini, bawaan 2 MB). Kecilkan foto atau naikkan batas tersebut.',
            'foto_qris.uploaded'      => 'Foto gagal diunggah. Ukuran file kemungkinan melebihi batas server (upload_max_filesize di php.ini, bawaan 2 MB). Kecilkan foto atau naikkan batas tersebut.',
            'foto_kos.max'            => 'Ukuran foto kos maksimal 5MB.',
            'foto_qris.max'           => 'Ukuran gambar QRIS maksimal 5MB.',
            'foto_kos.image'          => 'Foto kos harus berupa gambar.',
            'foto_qris.image'         => 'Gambar QRIS harus berupa gambar.',
        ];
    }
}