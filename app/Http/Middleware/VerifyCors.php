<?php

namespace App\Http\Middleware;

use App\Models\AllowedDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class VerifyCors
{
    public function handle(Request $request, Closure $next): Response
    {

        Log::info("verify cors middleware called");
        $origin = $request->header('Origin') ?? $request->header('Referer');

        $cleanOrigin = Str::lower(rtrim($origin, '/'));

        $allowedOrigins = $this->getAllowedOrigins();
        $defaultOrigins = config('cors.allowed_origins', []);
        
        $allPatterns = array_map(
            fn($o) => Str::lower(rtrim($o, '/')), 
            array_merge($defaultOrigins, $allowedOrigins)
        );

        $isAllowed = $this->isOriginAllowed($cleanOrigin, $allPatterns);

        if ($request->isMethod('OPTIONS')) {
            if ($isAllowed) {
                return response()->json([], 204, [
                    'Access-Control-Allow-Origin'      => $origin,
                    'Access-Control-Allow-Methods'     => 'GET, POST, PUT, DELETE, OPTIONS',
                    'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With, X-Client-Token',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Max-Age'           => '86400',
                ]);
            }

            return response()->json(['error' => 'CORS origin not allowed.'], 403);
        }

        $response = $next($request);

        if ($isAllowed) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    private function isOriginAllowed(string $origin, array $patterns): bool
    {
        $hostOnly = parse_url($origin, PHP_URL_HOST) ?? $origin;

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $origin) || Str::is($pattern, $hostOnly)) {
                return true;
            }
        }

        return false;
    }

    private function getAllowedOrigins(): array
    {
        try {
            return Cache::driver('redis')->remember('cors_allowed_origins', now()->addDays(1), function () {
                Log::info('VerifyCors: Cache expired or missing. Fetching fresh domains from database.');

                return AllowedDomain::where('is_active', true)
                    ->pluck('domain')
                    ->toArray();
            });
        } catch (\Throwable $e) {
            Log::error('VerifyCors: Failed to fetch dynamic CORS origins.', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}