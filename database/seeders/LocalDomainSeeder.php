<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\AllowedDomain;
use App\Models\ClientToken;
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
        $url = config('app.url', env('APP_URL'));
        $token = config('admin.widget.token', env('LOCAL_WIDGET_TOKEN'));

        if (!$url || !$token) {
            Log::warning('LocalDomainSeeder skipped: APP_URL or LOCAL_WIDGET_TOKEN environment variables are not set.');
            return;
        }

        $clientToken = ClientToken::updateOrCreate(
            ['token' => $token],
            [
                'name' => 'Local',
                'is_active' => true
            ]
        );

        AllowedDomain::updateOrCreate(
            ['domain' => rtrim($url, '/')],
            [
                'client_token_id' => $clientToken->id,
                'domain' => rtrim($url, '/'),
                'is_active' => true
            ]
        );

        Log::info('Local domain successfully synchronized via environment variables.', ['domain' => rtrim($url, '/')]);
    }
}
