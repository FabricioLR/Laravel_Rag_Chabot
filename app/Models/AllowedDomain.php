<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllowedDomain extends Model
{
    use HasFactory;
    protected $fillable = ['client_token_id', 'domain', 'is_active'];

    public function clientToken(): BelongsTo
    {
        return $this->belongsTo(ClientToken::class);
    }

    protected static function booted()
    {
        $clearCorsCache = function () {
            Cache::driver('redis')->forget('cors_allowed_origins');
            Cache::driver('redis')->tags(['domain_tokens'])->flush();
        };

        static::saved($clearCorsCache);
        static::updated($clearCorsCache);
        static::deleted($clearCorsCache);
    }
}