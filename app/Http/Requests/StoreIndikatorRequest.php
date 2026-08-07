<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndikatorRequest extends FormRequest
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
            'subsidebar_id' => ['required', 'integer', 'exists:subsidebars,id'],
            'nama_judul' => ['required', 'string', 'max:255'],
            'satuan' => ['nullable', 'string', 'max:100'],
            'tipe_baris' => ['nullable', 'string', 'max:50'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'subsidebar_id.required' => 'Kategori (subsidebar) wajib dipilih.',
            'subsidebar_id.exists' => 'Kategori tidak ditemukan.',
            'nama_judul.required' => 'Nama/judul indikator wajib diisi.',
        ];
    }
}
