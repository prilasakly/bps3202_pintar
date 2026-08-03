<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subsidebar extends Model
{
    protected $fillable = ['sidebar_id', 'nama', 'slug', 'urutan'];

    public function sidebar(): BelongsTo
    {
        return $this->belongsTo(Sidebar::class);
    }

    public function indikators(): HasMany
    {
        return $this->hasMany(Indikator::class)->orderBy('urutan');
    }
}
