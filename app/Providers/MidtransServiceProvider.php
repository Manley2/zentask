<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Midtrans\Config as MidtransConfig;

class MidtransServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        MidtransConfig::$serverKey    = config('midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production');
        MidtransConfig::$isSanitized  = (bool) config('midtrans.is_sanitized');
        MidtransConfig::$is3ds        = (bool) config('midtrans.is_3ds');
    }
}
