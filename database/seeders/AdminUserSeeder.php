<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $name = env('ADMIN_NAME', 'Administrator');
        $password = env('ADMIN_PASSWORD');

        if (!$email || !$password) {
            Log::warning('AdminUserSeeder skipped: ADMIN_EMAIL or ADMIN_PASSWORD environment variables are not set.');
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        Log::info('Default administrator account successfully synchronized via environment variables.', ['email' => $email]);
    }
}
