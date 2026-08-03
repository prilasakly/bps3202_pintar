<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndikatorKolom extends Model
{
    protected $fillable = [
        'indikator_id',
        'kolom_key',
        'kolom_label',
        'induk_label',
        'urutan',
    ];

    public function indikator(): BelongsTo
    {
        return $this->belongsTo(Indikator::class);
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(IndikatorNilai::class, 'kolom_id');
    }
}
