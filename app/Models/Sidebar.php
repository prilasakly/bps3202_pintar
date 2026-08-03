<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sidebar extends Model
{
    protected $fillable = ['nama', 'slug', 'urutan'];

    public function subsidebars(): HasMany
    {
        return $this->hasMany(Subsidebar::class)->orderBy('urutan');
    }
}
