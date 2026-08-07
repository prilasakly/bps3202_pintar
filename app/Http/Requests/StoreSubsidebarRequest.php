<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubsidebarRequest extends FormRequest
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
            'sidebar_id' => ['required', 'integer', 'exists:sidebars,id'],
            'nama' => ['required', 'string', 'max:255'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sidebar_id.required' => 'Menu induk (sidebar) wajib dipilih.',
            'sidebar_id.exists' => 'Menu induk tidak ditemukan.',
            'nama.required' => 'Nama kategori wajib diisi.',
        ];
    }
}
