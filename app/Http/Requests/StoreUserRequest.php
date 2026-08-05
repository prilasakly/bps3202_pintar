<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Otorisasi sesungguhnya (harus role "superadmin") sudah dicek middleware
     * EnsureHasRole di routes/api.php sebelum request ini diproses, jadi di sini
     * cukup true -- FormRequest ini murni validasi bentuk data.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'nip_lama' => ['nullable', 'string', 'max:50'],
            'nip_baru' => ['nullable', 'string', 'max:50'],
            'golongan' => ['nullable', 'string', 'max:50'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah dipakai user lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'roles.*.exists' => 'Salah satu role yang dipilih tidak valid.',
        ];
    }
}
