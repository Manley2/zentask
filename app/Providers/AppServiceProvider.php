<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

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
        // Pastikan URL generator konsisten HTTPS jika APP_URL pakai https (mis. ngrok)
        // Ini mencegah redirect/asset jadi http:// saat sebenarnya akses https://
        if (!app()->runningInConsole()) {
            $appUrl = (string) config('app.url');

            if (Str::startsWith($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
