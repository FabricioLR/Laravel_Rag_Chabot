<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class AllowedDomain extends Model
{
    protected $fillable = ['name', 'domain', 'token', 'is_active'];

    protected static function booted()
    {
        $clearCorsCache = function () {
            Cache::forget('cors_allowed_origins');
        };

        static::saved($clearCorsCache);
        static::deleted($clearCorsCache);

        static::creating(function ($allowedDomain) {
            $allowedDomain->token = Str::random(32);
        });
    }
}