<?php

namespace App\Services;

use App\Models\ConversationHistory as ConversationHistoryModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Exceptions\SessionExpiredException;
use Carbon\Carbon;

class ConversationHistory
{
    public function store(string $sessionId, string $question, string $answer): int
    {
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
        $expirationMinutes = (int) config('api.session.expiration_minutes', env('SESSION_EXPIRATION_MINUTES', 45));

        $lastInteraction = ConversationHistoryModel::where('session_id', $sessionId)
            ->latest('updated_at')
            ->first();

        if ($lastInteraction) {
            $lastActiveTime = Carbon::parse($lastInteraction->updated_at);
            
            if ($lastActiveTime->diffInMinutes(now()) >= $expirationMinutes) {
                throw new SessionExpiredException('Session has expired due to inactivity.');
            }
        }

        $interactions = ConversationHistoryModel::where('session_id', $sessionId)
            ->orderBy('id', 'asc')
            ->limit(5)
            ->get();

        $messages = collect();

        foreach ($interactions as $interaction) {
            $messages->push([
                'id'       => $interaction->id,
                'feedback' => $interaction->feedback ?? null,
                'text'     => $interaction->question,
                'sender'   => 'user'
            ]);

            $messages->push([
                'id'       => $interaction->id,
                'feedback' => $interaction->feedback ?? null,
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

        return $interaction->update([
            'feedback' => $feedbackValue,
        ]);
    }
}