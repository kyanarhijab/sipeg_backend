<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKeyIsValid
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = env('SIPEG_API_KEY', '');
        $providedKey = $request->header('X-API-KEY');

        if (! $providedKey || $providedKey !== $expectedKey) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak: API Key tidak valid atau belum disertakan pada header X-API-KEY.'
            ], 403);
        }

        return $next($request);
    }
}