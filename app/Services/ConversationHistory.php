<?php

namespace App\Services;

use App\Models\ConversationHistory as ConversationHistoryModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ConversationHistory
{
    public function store(string $sessionId, string $question, string $answer): int
    {
        // Using Eloquent create() is cleaner and automatically handles timestamps
        $record = ConversationHistoryModel::create([
            'session_id' => $sessionId,
            'question'   => $question,
            'answer'     => $answer,
        ]);

        return $record->id;
    }

    public function getFormattedHistory(string $sessionId): string
    {
        $maxRecentConversationHistory = config("rag.history.max_recent", env("RAG_MAX_RECENT_CONVERSATION_HISTORY", 3));
        
        Log::info('Retrieving conversation history context window.', [
            'session_id' => $sessionId,
            'limit' => $maxRecentConversationHistory
        ]);

        $history = ConversationHistoryModel::where('session_id', $sessionId)
            ->orderBy('id', 'desc')
            ->limit($maxRecentConversationHistory)
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

        return $formatted;
    }

    public function getMessagesForWidget(string $sessionId): Collection
    {
        $interactions = ConversationHistoryModel::where('session_id', $sessionId)
            ->orderBy('id', 'asc')
            ->limit(5)
            ->get();

        $messages = collect();

        foreach ($interactions as $interaction) {
            $messages->push([
                'id'       => $interaction->id,
                'feedback' => $interaction->feedback,
                'text'     => $interaction->question,
                'sender'   => 'user'
            ]);

            $messages->push([
                'id'       => $interaction->id,
                'feedback' => $interaction->feedback,
                'text'     => $interaction->answer,
                'sender'   => 'bot'
            ]);
        }

        return $messages;
    }

    public function updateFeedback(int $conversationId, string $feedbackValue): bool
    {
        $interaction = ConversationHistoryModel::find($conversationId);

        if (!$interaction) {
            return false;
        }

        // Eloquent update returns a boolean automatically
        return $interaction->update([
            'feedback' => $feedbackValue,
        ]);
    }
}