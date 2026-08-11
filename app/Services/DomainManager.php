<?php

namespace App\Services;

use App\Models\AllowedDomain;
use App\Models\ClientToken;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DomainManager
{
    public function createToken(string $name, ?string $customToken = null): ClientToken
    {
        Log::info('DomainManager: Creating new client token.', ['name' => $name]);

        try {
            return ClientToken::create([
                'name'      => $name,
                'token'     => $customToken ?? Str::random(40),
                'is_active' => true,
            ]);
        } catch (Exception $e) {
            Log::error('DomainManager: Failed to create client token.', ['error' => $e->getMessage()]);
            throw new Exception('Could not create client token: ' . $e->getMessage());
        }
    }
    public function addDomain(int $clientTokenId, string $url): AllowedDomain
    {
        Log::info("DomainManager: Adding domain to token ID [{$clientTokenId}].", ['url' => $url]);

        try {
            $token = ClientToken::findOrFail($clientTokenId);
            $cleanUrl = rtrim($url, '/');

            $domain = $token->allowedDomains()->create([
                'domain'    => $cleanUrl,
                'is_active' => true,
            ]);

            return $domain;
        } catch (Exception $e) {
            Log::error("DomainManager: Failed to add domain to token ID [{$clientTokenId}].", [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Could not add domain: ' . $e->getMessage());
        }
    }
    public function revokeDomain(int $domainId): bool
    {
        Log::info("DomainManager: Revoking domain ID [{$domainId}].");

        try {
            $domain = AllowedDomain::findOrFail($domainId);

            $deleted = (bool)$domain->delete();

            return $deleted;
        } catch (Exception $e) {
            Log::error("DomainManager: Failed to revoke domain ID [{$domainId}].", [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Could not complete domain revocation.');
        }
    }

    public function updateToken(int $tokenId, string $name): ClientToken
    {
        Log::info("DomainManager: Updating client token ID [{$tokenId}].", [
            'name'       => $name,
        ]);

        try {
            $token = ClientToken::findOrFail($tokenId);

            $token->update([
                'name'    => $name,
            ]);

            return $token;
        } catch (Exception $e) {
            Log::error("DomainManager: Failed to update client token ID [{$tokenId}].", [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Could not update client token: ' . $e->getMessage());
        }
    }

    public function verify(string $token, string $incomingOrigin): bool
    {
        $cleanOrigin = Str::lower(rtrim($incomingOrigin, '/'));
        $cacheKey = "domain_token:{$token}";
        $ttlInSeconds = 86400;

        $registeredDomains = Cache::driver('redis')->tags(['domain_tokens'])->remember($cacheKey, $ttlInSeconds, function () use ($token) {
            return AllowedDomain::whereHas('clientToken', function ($query) use ($token) {
                    $query->where('token', $token)->where('is_active', true);
                })
                ->where('is_active', true)
                ->pluck('domain')
                ->toArray();
        });

        if (empty($registeredDomains)) {
            Log::warning('DomainManager: Verification aborted. Client token not found, inactive, or has no assigned domains.', [
                'token' => $token,
            ]);
            return false;
        }

        $hostOnly = parse_url($cleanOrigin, PHP_URL_HOST) ?? $cleanOrigin;

        foreach ($registeredDomains as $domainPattern) {
            $pattern = Str::lower(rtrim($domainPattern, '/'));

            if (Str::is($pattern, $cleanOrigin) || Str::is($pattern, $hostOnly)) {
                return true;
            }
        }

        Log::warning('DomainManager: Verification rejected. Origin mismatch detected for token.', [
            'allowed_domains' => $registeredDomains,
            'incoming_origin' => $cleanOrigin,
            'token'           => $token,
        ]);

        return false;
    }
}