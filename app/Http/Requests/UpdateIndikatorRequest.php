<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIndikatorRequest extends FormRequest
{
    /**
     * Otorisasi sesungguhnya (permission "data.manage") sudah dicek middleware
     * EnsureHasPermission di routes/api.php, jadi di sini cukup true.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subsidebar_id' => ['sometimes', 'required', 'integer', 'exists:subsidebars,id'],
            'nama_judul' => ['sometimes', 'required', 'string', 'max:255'],
            'satuan' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tipe_baris' => ['sometimes', 'nullable', 'string', 'max:50'],
            'urutan' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'subsidebar_id.exists' => 'Kategori tidak ditemukan.',
            'nama_judul.required' => 'Nama/judul indikator wajib diisi.',
        ];
    }
}
