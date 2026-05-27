<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConversationHistory
{
    /**
     * Store the new interaction.
     */
    public function store(string $sessionId, string $question, string $answer): void
    {
        DB::table('conversation_histories')->insert([
            'session_id' => $sessionId,
            'question' => $question,
            'answer' => $answer,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Retrieve the last 5 interactions formatted as a context string.
     */
    public function getFormattedHistory(string $sessionId): string
    {
        Log::info('Retrieving conversation history context window.', [
            'session_id' => $sessionId,
            'limit' => 3
        ]);

        $history = DB::table('conversation_histories')
            ->where('session_id', $sessionId)
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get()
            ->reverse();
    
        Log::debug('Raw conversation history dataset loaded.', [
            'session_id' => $sessionId,
            'records_found' => $history->count(),
            'raw_payload' => $history->toArray() // Allows you to see all columns/messages in your storage logs
        ]);

        if ($history->isEmpty()) {
            Log::info('No prior interactions found for this session profile.', ['session_id' => $sessionId]);
            return "Nenhuma conversa anterior.\n";
        }

        $formatted = "";
        foreach ($history as $interaction) {
            $formatted .= "Usuário: " . $interaction->question . "\n";
            $formatted .= "Assistente: " . $interaction->answer . "\n\n";
        }

        Log::info('Conversation history successfully compiled and formatted for LLM ingestion context.', [
            'session_id' => $sessionId,
            'string_length' => strlen($formatted)
        ]);

        return $formatted;
    }
}