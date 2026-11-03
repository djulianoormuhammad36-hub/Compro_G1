<?php

namespace App\Providers;

use App\Models\KosInfo;
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
        // Info kos (nama, kontak, lokasi) dipakai di layout publik; satu query per request.
        View::composer(['layouts.app', 'components.navbar', 'components.footer', 'booking.*', 'kamar.*', 'home', 'tentang'], function ($view) {
            // Disimpan di container (bukan static) agar aman antar request/test.
            if (! app()->bound('kos.info')) {
                app()->instance('kos.info', [KosInfo::first()]);
            }

            $view->with('kosInfo', app('kos.info')[0]);
        });
    }
}
