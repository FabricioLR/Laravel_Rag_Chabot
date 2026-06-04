<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\DomainManager;

class VerifyWidgetCredentials
{
    public function __construct(
        protected DomainManager $domainManager
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Client-Token') ?? $request->input('token');
        $origin = $request->header('Origin') ?? $request->header('Referer');

        if (!$token || !$origin) {
            return response()->json([
                'error' => 'Missing authorization credentials context.'
            ], 401);
        }

        $isAuthorized = $this->domainManager->verify($token, $origin);

        if (!$isAuthorized) {
            return response()->json([
                'error' => 'Unauthorized embed code environment connection.'
            ], 403);
        }

        return $next($request);
    }
}