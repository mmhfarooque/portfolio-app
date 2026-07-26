<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyNasToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.nas.analytics_token');

        abort_unless(
            is_string($expected) && $expected !== ''
                && hash_equals($expected, (string) $request->bearerToken()),
            401,
            'Invalid or missing token.'
        );

        return $next($request);
    }
}
