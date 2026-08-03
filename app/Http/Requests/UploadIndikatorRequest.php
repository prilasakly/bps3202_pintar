<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadIndikatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'indikator_id' => ['required', 'integer', 'exists:indikators,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'triwulan' => ['nullable', 'integer', 'in:1,2,3,4'],
            'sheet' => ['nullable', 'string'],
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'indikator_id.required' => 'Pilih indikator tujuan upload.',
            'indikator_id.exists' => 'Indikator tidak ditemukan.',
            'tahun.required' => 'Tahun data wajib diisi.',
            'triwulan.in' => 'Triwulan harus 1, 2, 3, atau 4.',
            'file.required' => 'File excel wajib diupload.',
            'file.mimes' => 'File harus berformat .xls atau .xlsx.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ];
    }
}
