<?php

namespace App\Providers;

use App\Models\AllowedDomain;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
        $tableExists = Cache::remember('cors_table_exists', now()->addDays(1), function () {
            return Schema::hasTable('allowed_domains');
        });

        if (!$tableExists) {
            return;
        }

        try {
            $databaseOrigins = Cache::remember('cors_allowed_origins', now()->addMinutes(30), function () {
                return AllowedDomain::where('is_active', true)
                    ->pluck('domain')
                    ->toArray();
            });

            if (!empty($databaseOrigins)) {
                $defaultOrigins = config('cors.allowed_origins', []);

                $mergedOrigins = array_unique(array_merge($defaultOrigins, $databaseOrigins));

                config(['cors.allowed_origins' => $mergedOrigins]);
                
                Log::debug('CorsServiceProvider: Dynamic database origins successfully injected into runtime config.', [
                    'total_origins' => count($mergedOrigins),
                    'origins' => $mergedOrigins,
                    'from_cache' => true
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('CorsServiceProvider: Failed to inject runtime dynamic CORS origins.', [
                'error' => $e->getMessage()
            ]);
        }
    }
}