<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ConversationHistory
{
    /**
     * Store the new interaction.
     */
    public function store(string $sessionId, string $question, string $answer): int
    {
        return DB::table('conversation_histories')->insertGetId([
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

        if ($history->isEmpty()) {
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

    public function getMessagesForWidget(string $sessionId): Collection
    {
        $interactions = DB::table('conversation_histories')
            ->where('session_id', $sessionId)
            ->orderBy('id', 'asc')
            ->limit(5)
            ->get();

        $messages = collect();

        foreach ($interactions as $interaction) {
            $messages->push([
                'id' => $interaction->id,
                'feedback' => $interaction->feedback,
                'text' => $interaction->question,
                'sender' => 'user'
            ]);

            $messages->push([
                'id' => $interaction->id,
                'feedback' => $interaction->feedback,
                'text' => $interaction->answer,
                'sender' => 'bot'
            ]);
        }

        return $messages;
    }

    public function updateFeedback(int $conversationId, string $feedbackValue): bool
    {
        return DB::table('conversation_histories')
            ->where('id', $conversationId)
            ->update([
                'feedback' => $feedbackValue,
                'updated_at' => now(),
            ]) > 0;
    }
}