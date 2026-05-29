<?php

namespace App\Providers;

use App\Models\AllowedDomain;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CorsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!Schema::hasTable('allowed_domains')) {
            return;
        }

        try {
            $databaseOrigins = AllowedDomain::where('is_active', true)
                ->pluck('domain')
                ->toArray();

            if (!empty($databaseOrigins)) {
                $defaultOrigins = config('cors.allowed_origins', []);

                $mergedOrigins = array_unique(array_merge($defaultOrigins, $databaseOrigins));

                config(['cors.allowed_origins' => $mergedOrigins]);
                
                Log::debug('CorsServiceProvider: Dynamic database origins successfully injected into runtime runtime config.', [
                    'total_origins' => count($mergedOrigins)
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('CorsServiceProvider: Failed to inject runtime dynamic CORS origins.', [
                'error' => $e->getMessage()
            ]);
        }
    }
}