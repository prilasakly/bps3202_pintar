<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadIndikatorRequest;
use App\Models\Indikator;
use App\Services\IndikatorExcelImporter;

class UploadApiController extends Controller
{
    public function store(UploadIndikatorRequest $request, IndikatorExcelImporter $importer)
    {
        $indikator = Indikator::findOrFail($request->integer('indikator_id'));
        $file = $request->file('file');

        $hasil = $importer->import(
            indikator: $indikator,
            filePath: $file->getRealPath(),
            tahun: $request->integer('tahun'),
            triwulan: $request->filled('triwulan') ? $request->integer('triwulan') : null,
            namaFileAsli: $file->getClientOriginalName(),
            namaSheet: $request->input('sheet'),
            force: $request->boolean('force'),
        );

        $statusCode = match ($hasil['status']) {
            'sukses' => 201,
            'diabaikan' => 200,
            default => 422,
        };

        return response()->json($hasil, $statusCode);
    }
}
