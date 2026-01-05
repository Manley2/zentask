<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'webhook/midtrans',
        'webhook/midtrans-test',
        'midtrans/notification',
        // Allow logout to work even if CSRF cookie/session gets dropped (e.g. via proxy)
        'logout',
    ];
}
