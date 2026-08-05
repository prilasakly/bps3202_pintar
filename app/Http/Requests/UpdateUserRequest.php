<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        // Route model binding: {user} pada route PUT/PATCH /api/users/{user}.
        $userId = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            // Kata sandi opsional saat update -- kosongkan kalau tidak mau ganti.
            'password' => ['nullable', 'string', 'min:8'],
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
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'roles.*.exists' => 'Salah satu role yang dipilih tidak valid.',
        ];
    }
}
