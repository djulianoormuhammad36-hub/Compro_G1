<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

        /** Nomor HP boleh diketik dengan spasi/tanda hubung/titik: 0812-3456-7890 -> 081234567890. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->kontak)) {
            $this->merge(['kontak' => preg_replace('/[\s\-\.\(\)]/', '', $this->kontak)]);
        }
    }

    public function rules(): array
    {
        return [
            'booking'         => ['nullable', 'string', 'max:64'],
            'nama_pendaftar'  => ['required', 'string', 'max:255'],
            'no_ktp'          => ['required', 'digits:16'],
            'kontak'          => ['required', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'tanggal_booking' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addYear()->toDateString()],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_pendaftar.required'        => 'Nama lengkap wajib diisi sesuai KTP.',
            'no_ktp.required'                => 'Nomor KTP wajib diisi.',
            'no_ktp.digits'                  => 'Nomor KTP harus 16 digit angka.',
            'kontak.required'                => 'Nomor handphone/WhatsApp wajib diisi.',
            'kontak.regex'                   => 'Format nomor HP tidak valid (contoh: 081234567890).',
            'tanggal_booking.required'       => 'Tanggal mulai sewa wajib dipilih.',
            'tanggal_booking.after_or_equal' => 'Tanggal mulai sewa tidak boleh lampau.',
            'tanggal_booking.before_or_equal' => 'Tanggal mulai sewa maksimal 1 tahun ke depan.',
        ];
    }
}
