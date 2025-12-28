<?php

namespace App\Http\Middleware;

use App\Models\Visit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->isMethod('get')) {
            return $response;
        }

        if ($request->expectsJson()) {
            return $response;
        }

        $path = ltrim($request->path(), '/');
        if ($this->shouldSkip($path)) {
            return $response;
        }

        $ip = $request->ip() ?? '';
        $userAgent = (string) $request->userAgent();

        Visit::create([
            'user_id' => optional($request->user())->id,
            'path' => $path === '' ? '/' : $path,
            'ip_hash' => hash('sha256', $ip),
            'user_agent' => substr($userAgent, 0, 255),
            'visited_at' => now(),
        ]);

        return $response;
    }

    private function shouldSkip(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        $prefixes = [
            'api',
            'storage',
            'build',
            'vendor',
            'resources',
            '_debugbar',
            'livewire',
            'telescope',
            'horizon',
            'up',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
