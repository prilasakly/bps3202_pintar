<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportUsersRequest extends FormRequest
{
    /**
     * Otorisasi sesungguhnya (harus role "superadmin") sudah dicek middleware
     * EnsureHasRole di routes/api.php, jadi di sini cukup true.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File excel wajib diupload.',
            'file.mimes' => 'File harus berformat .xls atau .xlsx.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ];
    }
}