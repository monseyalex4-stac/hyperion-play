<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Forzar HTTPS en producción o cuando Render envía cabecera X-Forwarded-Proto
        if (config('app.env') === 'production' || request()->server('HTTP_X_FORWARDED_PROTO') === 'https' || str_contains(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
