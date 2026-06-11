<?php

namespace App\Contracts;

interface LLM
{
    /**
     * @return array{answer: string, duration: float, total_tokens: int, tokens: array{prompt: int, completion: int}}
     */
    public function generateAnswer(string $userInput, string $sessionId, string $context, string $conversationHistory): array;
}