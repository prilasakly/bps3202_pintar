<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indikator extends Model
{
    protected $fillable = [
        'subsidebar_id',
        'nama_judul',
        'slug',
        'satuan',
        'tipe_baris',
        'nama_file_asli',
        'urutan',
    ];

    public function subsidebar(): BelongsTo
    {
        return $this->belongsTo(Subsidebar::class);
    }

    public function kolom(): HasMany
    {
        return $this->hasMany(IndikatorKolom::class)->orderBy('urutan');
    }

    public function periode(): HasMany
    {
        return $this->hasMany(IndikatorPeriode::class)->orderBy('tahun');
    }

    public function baris(): HasMany
    {
        return $this->hasMany(IndikatorBaris::class)->orderBy('urutan');
    }

    /**
     * Ambil data dalam bentuk tabel pivot: baris x (tahun x kolom).
     * Dipakai untuk tampilan tabel/time-series di web ketika user pilih beberapa tahun.
     *
     * @param  array<int>  $tahunList
     */
    public function ambilTabel(array $tahunList)
    {
        return IndikatorNilai::query()
            ->whereHas('periode', function ($q) use ($tahunList) {
                $q->where('indikator_id', $this->id)->whereIn('tahun', $tahunList);
            })
            ->with(['periode', 'baris', 'kolom'])
            ->get();
    }
}
