<?php

namespace App\Services\LLM;

use App\Contracts\LLM;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class Groq implements LLM
{
    public function generateAnswer(string $userInput, string $sessionId, string $context, string $conversationHistory): array
    {
        $startTime = microtime(true);
        $apiKey = config('services.groq.api_key', env('GROQ_API_KEY'));

        $systemPrompt = "Você é um assistente virtual especialista no sistema ERP. Seu objetivo é responder às dúvidas dos usuários com base EXCLUSIVAMENTE nos fragmentos de documentos fornecidos abaixo.\n" .
                        "REGRAS OBRIGATÓRIAS E FORMATAÇÃO:\n" .
                        "- Sempre que você utilizar uma informação de um bloco de contexto para responder ao usuário, você DEVE incluir o link da 'Fonte' correspondente logo após a afirmação, além de sempre utilizar o título do post como nome do link(ex: [Título do Post](url_da_fonte)).\n" .
                        "- Nunca invente URLs. Use estritamente as URLs fornecidas dentro das tags de contexto.\n" .
                        "- Seja direto, claro e profissional.\n" .
                        "- Se a resposta não puder ser extraída do contexto fornecido, responda honestamente que não possui essa informação.\n" . 
                        "- Se nenhum contexto for fornecido, responda ao usuário dando sugestão de reformular a pergunta ou mudar a categoria que ele está utilizando.";

        $prompt = "# [HISTÓRICO DA CONVERSA]\n" .
              "Abaixo está o histórico das últimas interações para lhe dar contexto do que foi discutido:\n\n" .
              $conversationHistory . "\n" .
              "---\n\n" .
              "# [CONTEXTO RECUPERADO]\n" .
              $context . "\n" .
              "---\n\n" .
              "# [PERGUNTA ATUAL DO USUÁRIO]\n" .
              $userInput . "\n\n" .
              "---\n\n" .
              "# [RESPOSTA DO ASSISTENTE]\n";

        Log::debug("Full string context compiled for LLM request.", [
            'session_id' => $sessionId,
            'full_user_prompt' => $prompt
        ]);

        $response = Http::withToken($apiKey)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.1-8b-instant',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1,
                'max_tokens' => 1024
            ]);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($response->failed()) { throw new Exception('Groq API Error'); }

        $data = $response->json();
        return [
            'answer' => $data['choices'][0]['message']['content'] ?? '',
            'duration' => $duration,
            'total_tokens' => $data['usage']['total_tokens'] ?? 0,
            'tokens' => [
                'prompt' => $data['usage']['prompt_tokens'] ?? 0,
                'completion' => $data['usage']['completion_tokens'] ?? 0,
            ]
        ];
    }
}