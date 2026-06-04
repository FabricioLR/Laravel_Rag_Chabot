<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\AllowedDomain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LocalDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $url = env('APP_URL');
        $token = env('LOCAL_WIDGET_TOKEN');
        

        if (!$url || !$token) {
            Log::warning('LocalDomainSeeder skipped: APP_URL or LOCAL_WIDGET_TOKEN environment variables are not set.');
            return;
        }

        AllowedDomain::updateOrCreate(
            ['domain' => rtrim($url, '/')],
            [
                'name' => 'Local Widget Access',
                'domain' => rtrim($url, '/'),
                'token' => $token,
                'is_active' => true
            ]
        );

        Log::info('Local domain successfully synchronized via environment variables.', ['domain' => rtrim($url, '/')]);
    }
}
