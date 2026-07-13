<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConversationHistory extends Model
{
    protected $fillable = [
        'session_id', 
        'question', 
        'answer', 
        'feedback'
    ];

    public function telemetry(): HasOne
    {
        return $this->hasOne(GenerationTelemetry::class);
    }
}