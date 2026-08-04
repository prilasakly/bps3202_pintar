<?php

namespace App\Providers;

use App\Models\Sidebar;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Data menu navigasi (Sidebar > Subsidebar > Indikator) dibagikan ke layout utama
        // supaya tiap halaman yang extends layouts.app otomatis punya menu navigasi tanpa
        // perlu mengulang query yang sama di setiap controller.
        View::composer('layouts.app', function ($view) {
            $view->with(
                'navSidebars',
                Sidebar::with(['subsidebars' => function ($q) {
                    $q->orderBy('urutan')->withCount('indikators');
                }, 'subsidebars.indikators:id,subsidebar_id,nama_judul,slug'])
                    ->orderBy('urutan')
                    ->get(['id', 'nama', 'slug', 'icon', 'type', 'route_name', 'url', 'urutan'])
            );
        });
    }
}
