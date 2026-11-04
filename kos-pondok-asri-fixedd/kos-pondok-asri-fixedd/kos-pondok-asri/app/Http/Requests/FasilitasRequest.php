<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FasilitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unique = Rule::unique('fasilitas', 'nama_fasilitas');
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $id = $this->route('fasilitas') ?? collect($this->route()?->parameters() ?? [])->first() ?? $this->id;
            $unique->ignore($id, 'id_fasilitas');
        }

        return [
            'nama_fasilitas'      => ['required', 'string', 'max:100', $unique],
            'deskripsi_fasilitas' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_fasilitas.required' => 'Nama fasilitas wajib diisi.',
            'nama_fasilitas.unique'   => 'Fasilitas dengan nama itu sudah ada.',
        ];
    }
}
