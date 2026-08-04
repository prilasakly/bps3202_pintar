<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sidebar extends Model
{
    protected $fillable = ['nama', 'slug', 'icon', 'type', 'route_name', 'url', 'urutan'];

    public function subsidebars(): HasMany
    {
        return $this->hasMany(Subsidebar::class)->orderBy('urutan');
    }

    public function isDropdown(): bool
    {
        return $this->type === 'dropdown';
    }

    /**
     * URL tujuan menu ini: pakai route_name kalau ada (dan valid), fallback ke kolom url,
     * fallback terakhir "#" supaya tidak error kalau keduanya kosong (menu belum dikonfigurasi).
     */
    public function href(): string
    {
        if ($this->route_name && \Illuminate\Support\Facades\Route::has($this->route_name)) {
            return route($this->route_name);
        }

        return $this->url ?: '#';
    }
}
