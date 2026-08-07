<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubsidebarRequest extends FormRequest
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
            'sidebar_id' => ['sometimes', 'required', 'integer', 'exists:sidebars,id'],
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'urutan' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sidebar_id.exists' => 'Menu induk tidak ditemukan.',
            'nama.required' => 'Nama kategori wajib diisi.',
        ];
    }
}
