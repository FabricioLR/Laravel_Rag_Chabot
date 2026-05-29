<?php

namespace App\Services;

use App\Models\AllowedDomain;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        $cleanOrigin = rtrim($incomingOrigin, '/');

        $domainRecord = AllowedDomain::where('token', $token)
            ->where('is_active', true)
            ->first();

        if (!$domainRecord) {
            Log::warning('DomainManager: Verification aborted. Client token not found or inactive.', [
                'token' => $token
            ]);
            return false;
        }

        $isMatch = ($domainRecord->domain === $cleanOrigin || $domainRecord->domain === '*');

        if (!$isMatch) {
            Log::warning('DomainManager: Verification rejected. Origin mismatch detected for token.', [
                'registered_domain' => $domainRecord->domain,
                'incoming_origin' => $cleanOrigin,
                'token' => $token
            ]);
            return false;
        }

        Log::info("DomainManager: Successfully authenticated access request for domain [{$domainRecord->name}].");
        return true;
    }
}