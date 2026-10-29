<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('expiry.api_key');

        if ($key === '') {
            return response()->json(['error' => 'API is disabled: set REMINDER_API_KEY in .env'], 503);
        }

        if (!hash_equals($key, (string) $request->header('x-api-key'))) {
            return response()->json(['error' => 'invalid or missing x-api-key'], 401);
        }

        return $next($request);
    }
}
