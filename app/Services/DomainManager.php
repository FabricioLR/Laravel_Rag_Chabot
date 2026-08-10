<?php

namespace App\Services;

use App\Models\AllowedDomain;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Exception;

class DomainManager
{
    public function register(string $name, string $url): AllowedDomain
    {
        Log::info('DomainManager: Registering new allowed domain origin.', [
            'name' => $name,
            'url' => $url
        ]);

        try {
            $cleanUrl = rtrim($url, '/');

            return AllowedDomain::create([
                'name' => $name,
                'domain' => $cleanUrl,
                'is_active' => true
            ]);
        } catch (Exception $e) {
            Log::error('DomainManager: Failed to register domain.', [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Could not register domain: ' . $e->getMessage());
        }
    }

    public function revoke(int $id): bool
    {
        Log::info("DomainManager: Revoking domain access profile ID [{$id}].");

        try {
            $domain = AllowedDomain::findOrFail($id);
            return (bool)$domain->delete();
        } catch (Exception $e) {
            Log::error("DomainManager: Failed to revoke domain access profile ID [{$id}].", [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Could not complete domain revocation processing.');
        }
    }

    public function verify(string $token, string $incomingOrigin): bool
    {
        $cleanOrigin = Str::lower(rtrim($incomingOrigin, '/'));
        
        $cacheKey = "domain_token:{$token}";
        $ttlInSeconds = 86400;

        $registeredDomain = Cache::driver('redis')->tags(['domain_tokens'])->remember($cacheKey, $ttlInSeconds, function () use ($token) {
            $domainRecord = AllowedDomain::where('token', $token)
                ->where('is_active', true)
                ->first();

            return $domainRecord ? $domainRecord->domain : null;
        });

        if (!$registeredDomain) {
            Log::warning('DomainManager: Verification aborted. Client token not found or inactive.', [
                'token' => $token
            ]);
            return false;
        }

        $registeredPattern = Str::lower($registeredDomain);
        $isMatch = Str::is($registeredPattern, $cleanOrigin);

        if (!$isMatch) {
            Log::warning('DomainManager: Verification rejected. Origin mismatch detected for token.', [
                'registered_domain' => $registeredDomain,
                'incoming_origin'   => $cleanOrigin,
                'token'             => $token
            ]);
            return false;
        }

        return true;
    }
}