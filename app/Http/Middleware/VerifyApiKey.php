<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');
        $validKey = \App\Models\Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY'));

        if (empty($validKey) || empty($apiKey) || !hash_equals((string) $validKey, (string) $apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Invalid API Key.',
            ], 401);
        }

        return $next($request);
    }
}
