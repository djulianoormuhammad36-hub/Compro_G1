<?php

namespace App\Http\Requests;

use App\Models\Kamar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KamarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware auth + admin pada route
    }

    public function rules(): array
    {
        $unique = Rule::unique('kamar', 'nomor_kamar');
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $unique->ignore($this->route('kamar'), 'id_kamar');
        }

        return [
            'nomor_kamar'  => ['required', 'string', 'max:20', $unique],
            'biaya_sewa'   => ['required', 'integer', 'min:0'],
            'detail_kamar' => ['nullable', 'string', 'max:2000'],
            'status_kamar' => ['required', Rule::in([Kamar::KOSONG, Kamar::RESERVASI, Kamar::TERISI])],
            'foto_kamar'   => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'hapus_foto'   => ['nullable', 'boolean'],
            'fasilitas'    => ['nullable', 'array'],
            'fasilitas.*'  => ['exists:fasilitas,id_fasilitas'],
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_kamar.required'  => 'Nomor kamar wajib diisi.',
            'nomor_kamar.unique'    => 'Nomor kamar sudah terdaftar.',
            'biaya_sewa.required'   => 'Harga sewa per bulan wajib diisi.',
            'biaya_sewa.integer'    => 'Harga sewa harus berupa angka bulat (rupiah).',
            'status_kamar.required' => 'Status kamar wajib dipilih.',
            'foto_kamar.uploaded'        => 'Foto gagal diunggah. Ukuran file kemungkinan melebihi batas server (upload_max_filesize di php.ini, bawaan 2 MB). Kecilkan foto atau naikkan batas tersebut.',
            'foto_kamar.image'      => 'File foto harus berupa gambar.',
            'foto_kamar.max'        => 'Ukuran foto maksimal 5MB.',
        ];
    }
}
