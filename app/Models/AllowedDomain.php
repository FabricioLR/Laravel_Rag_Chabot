<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class AllowedDomain extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'domain', 'token', 'is_active'];

    protected static function booted()
    {
        $clearCorsCache = function () {
            Cache::driver('redis')->forget('cors_allowed_origins');
            Cache::driver('redis')->tags(['domain_tokens'])->flush();
        };

        static::saved($clearCorsCache);
        static::deleted($clearCorsCache);

        static::creating(function ($allowedDomain) {
            $allowedDomain->token = Str::random(32);
        });
    }
}