<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerationTelemetry extends Model
{
    protected $fillable = [
        'conversation_history_id', 'model', 'temperature', 'max_tokens',
        'main_category', 'child_category', 'system_prompt', 'compiled_prompt',
        'prompt_tokens', 'completion_tokens', 'total_tokens',
        'llm_duration_ms', 'embedding_duration_ms', 'database_duration_ms', 'total_duration_ms'
    ];

    public function conversationHistory(): BelongsTo
    {
        return $this->belongsTo(ConversationHistory::class, 'conversation_history_id');
    }
}