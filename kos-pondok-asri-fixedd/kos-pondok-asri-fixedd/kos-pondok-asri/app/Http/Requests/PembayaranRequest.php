<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PembayaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metode_pembayaran' => ['required', 'in:Transfer Bank,QRIS,Bayar Langsung'],
            // Transfer & QRIS wajib mengirim foto bukti; bayar langsung tidak perlu.
            'bukti_bayar'       => ['required_if:metode_pembayaran,Transfer Bank,QRIS', 'nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'metode_pembayaran.required' => 'Silakan pilih metode pembayaran.',
            'metode_pembayaran.in'       => 'Metode pembayaran tidak valid.',
            'bukti_bayar.required_if'    => 'Foto bukti pembayaran wajib diunggah untuk transfer atau QRIS.',
            'bukti_bayar.image'          => 'File bukti pembayaran harus berupa gambar.',
            'bukti_bayar.mimes'          => 'Bukti pembayaran harus berformat JPG atau PNG.',
            'bukti_bayar.max'            => 'Ukuran bukti pembayaran maksimal 5MB.',
        ];
    }
}
