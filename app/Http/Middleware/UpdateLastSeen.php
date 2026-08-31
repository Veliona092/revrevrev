<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $cacheKey = "last_seen_updated:{$userId}";

            // Only write to DB at most once per minute per user
            if (! Cache::has($cacheKey)) {
                Auth::user()->updateQuietly(['last_seen_at' => now()]);
                Cache::put($cacheKey, true, now()->addMinute());
            }
        }

        return $next($request);
    }
}
