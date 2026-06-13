<?php

namespace App\Services\LLM;

use App\Contracts\LLM;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class OpenaiLLM implements LLM
{
    public function generateAnswer(string $userInput, string $sessionId, string $context, string $conversationHistory): array
    {
        try {
            $startTime = microtime(true);
            $model = config('services.openai.llm_model', env('OPENAI_LLM_MODEL', 'gpt-4o-mini'));
            $temperature = config('services.openai.llm_model_temperature', env('OPENAI_LLM_MODEL_TEMPERATURE', 0.1));
            $maxOutputTokens = config('services.openai.llm_model_max_output_tokens', env('OPENAI_LLM_MODEL_MAX_OUTPUT_TOKENS', 1024));

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

            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];

            $response = OpenAI::chat()->create([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxOutputTokens,
            ]);

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $promptTokens = $response->usage->promptTokens ?? 0;
            $completionTokens = $response->usage->completionTokens ?? 0;
            $totalTokens = $response->usage->totalTokens ?? ($promptTokens + $completionTokens);

            return [
                'answer' => $response->choices[0]->message->content,
                'duration' => $durationMs,
                'total_tokens' => $totalTokens,
                'tokens' => [
                    'prompt' => $promptTokens,
                    'completion' => $completionTokens,
                ]
            ];

        } catch (\Exception $e) {
            Log::emergency("OpenAI LLM failed to generate answer catastrophically.", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}