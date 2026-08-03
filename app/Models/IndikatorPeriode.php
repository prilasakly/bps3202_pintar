<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndikatorPeriode extends Model
{
    protected $fillable = [
        'indikator_id',
        'tahun',
        'triwulan',
        'file_asal',
        'file_hash',
        'diupload_pada',
    ];

    protected $casts = [
        'diupload_pada' => 'datetime',
    ];

    public function indikator(): BelongsTo
    {
        return $this->belongsTo(Indikator::class);
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(IndikatorNilai::class, 'periode_id');
    }
}
