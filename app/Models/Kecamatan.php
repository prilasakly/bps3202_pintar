<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kecamatan extends Model
{
    protected $fillable = ['nama', 'kode_bps', 'urutan'];

    public function indikatorBaris(): HasMany
    {
        return $this->hasMany(IndikatorBaris::class);
    }
}
