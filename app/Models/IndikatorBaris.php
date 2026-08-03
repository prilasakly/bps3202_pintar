<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndikatorBaris extends Model
{
    protected $table = 'indikator_baris';

    protected $fillable = [
        'indikator_id',
        'kecamatan_id',
        'baris_key',
        'baris_label',
        'urutan',
    ];

    public function indikator(): BelongsTo
    {
        return $this->belongsTo(Indikator::class);
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(IndikatorNilai::class, 'baris_id');
    }
}
