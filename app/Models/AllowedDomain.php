<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AllowedDomain extends Model
{
    protected $fillable = ['name', 'domain', 'token', 'is_active'];

    protected static function booted()
    {
        static::creating(function ($allowedDomain) {
            $allowedDomain->token = Str::random(32);
        });
    }
}