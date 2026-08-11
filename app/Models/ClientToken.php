<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientToken extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'token', 'is_active'];

    public function allowedDomains(): HasMany
    {
        return $this->hasMany(AllowedDomain::class);
    }
}