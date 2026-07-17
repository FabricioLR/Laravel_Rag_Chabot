<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\LLM\LLMManager;
use App\Contracts\LLM;
use App\Services\Embedding\EmbeddingManager;
use App\Contracts\Embedding;
use App\Models\GenerationTelemetry;

class AnswerGeneration
{
    public function __construct(
        protected Knowledge $knowledgeBaseService,
        protected ConversationHistory $historyService,
        protected Category $categoryService,
        protected LLMManager $llmManager,
        protected EmbeddingManager $embeddingManager
    ) {}

    public function generate(string $userInput, string $sessionId, ?string $mainCategory = null, ?string $childCategory = null): array
    {
        Log::info('Chatbot pipeline started.', compact('userInput', 'sessionId', 'mainCategory', 'childCategory'));
        $totalStartTime = microtime(true);

        $conversationHistory = $this->historyService->getFormattedHistory($sessionId);
        $embeddingResult = $this->embeddingManager->make()->generate($userInput, "query");
        
        $searchResult = $this->knowledgeBaseService->searchContext(
            $userInput, 
            $embeddingResult['vector'], 
            $mainCategory, 
            $childCategory
        );

        [$systemPrompt, $prompt] = empty($searchResult['context'])
            ? $this->compileFallbackPrompt($userInput, $mainCategory, $childCategory)
            : $this->compileStandardPrompt($userInput, $searchResult['context'], $conversationHistory);

        Log::debug("Full string context compiled for LLM request.", [
            'session_id' => $sessionId,
            'full_user_prompt' => $prompt
        ]);

        $llmResult = $this->llmManager->make()->generateAnswer($prompt, $systemPrompt, $sessionId);
        $conversationId = $this->historyService->store($sessionId, $userInput, $llmResult['answer']);

        $totalDuration = round((microtime(true) - $totalStartTime) * 1000, 2);
        $this->logPerformanceMetrics($userInput, $conversationHistory, $searchResult, $llmResult, $totalDuration, $sessionId);
        $this->saveTelemetry($conversationId, $llmResult, $embeddingResult, $searchResult, $totalDuration, $mainCategory, $childCategory);

        return [
            'answer' => $llmResult['answer'], 
            'conversationId' => $conversationId
        ];
    }

    private function compileFallbackPrompt(string $userInput, ?string $mainCategory, ?string $childCategory): array
    {
        $formattedCategories = $this->categoryService->getFormatedChildCategories($mainCategory, $childCategory);

        $systemPrompt = <<<'PROMPT'
Você é um assistente virtual especialista em UX (Experiência do Usuário) e suporte técnico de sistemas.
O usuário fez uma pergunta, mas não encontramos documentos específicos no nosso banco de dados RAG para respondê-la diretamente. 

Sua tarefa é agir como um guia dinâmico:
1. Explique brevemente ao usuário que você não localizou um artigo exato sobre o assunto.
2. Analise a lista de categorias e subcategorias disponíveis fornecidas nos "Dados de Entrada".
3. Com base na pergunta atual do usuário, recomende em qual dessas subcategorias ele provavelmente encontrará a resposta ou onde ele deve clicar no sistema para resolver o problema dele.
4. Mantenha um tom profissional, corporativo, direto e extremamente amigável em português do Brasil.
PROMPT;

        $prompt = <<<PROMPT
# [DADOS DE ENTRADA - CATEGORIAS DISPONÍVEIS]
Abaixo estão as seções do sistema disponíveis para este módulo:
{$formattedCategories}

---

# [PERGUNTA ATUAL DO USUÁRIO]
O usuário digitou a seguinte dúvida no chat:
"{$userInput}"

---

# [EXEMPLO DE SAÍDA ESPERADA]
Infelizmente não encontrei um manual específico sobre esse tema. No entanto, olhando o módulo selecionado, recomendo verificar:
* **1.5.2 - Movimentações Financeiras**: Onde você realiza conciliações e baixas de títulos.
* **1.5.5 - Apoio Financeiro**: Caso precise configurar parâmetros bancários preliminares.

Como você gostaria de prosseguir?
PROMPT;

        return [$systemPrompt, $prompt];
    }

    private function compileStandardPrompt(string $userInput, string $context, string $history): array
    {
        $systemPrompt = config('services.llm.system_prompt');
        
        if (empty($systemPrompt)) {
            throw new Exception("LLM System Prompt must be provided in config directory.");
        }

        $prompt = <<<PROMPT
# [HISTÓRICO DA CONVERSA]
Abaixo está o histórico das últimas interações para lhe dar contexto do que foi discutido:

{$history}
---

# [CONTEXTO RECUPERADO]
{$context}
---

# [PERGUNTA ATUAL DO USUÁRIO]
{$userInput}

---

# [RESPOSTA DO ASSISTENTE]
PROMPT;

        return [$systemPrompt, $prompt];
    }

    private function logPerformanceMetrics(string $query, string $history, array $search, array $llm, float $duration, string $sessionId): void
    {
        $queryLen = strlen($query);
        $contextLen = strlen($search['context'] ?? '');
        $historyLen = strlen($history);

        Log::info('Chatbot pipeline fully completed.', [
            'question' => $query,
            'answer' => $llm['answer'],
            'session_id' => $sessionId,
            'total_duration_ms' => $duration,
            'breakdown_ms' => [
                'embeddings' => $llm['duration'] ?? 0,
                'database' => $search['duration'] ?? 0,
                'llm' => $llm['duration'] ?? 0
            ],
            'total_payload_chars' => $queryLen + $contextLen + $historyLen,
            'llm_input_string_lengths' => [
                'user_query_chars' => $queryLen,
                'retrieved_context_chars' => $contextLen,
                'chat_history_chars' => $historyLen,
            ],
            'total_tokens' => $llm['total_tokens'] ?? 0
        ]);
    }

    private function saveTelemetry($conversationId, array $llm, array $embedding, array $search, float $totalDuration, ?string $mainCat, ?string $childCat): void
    {
        try {
            GenerationTelemetry::create([
                'conversation_history_id' => $conversationId,
                'model' => $llm['model'] ?? null,
                'temperature' => $llm['temperature'] ?? null,
                'max_tokens' => $llm['max_tokens'] ?? null,
                'main_category' => $mainCat,
                'child_category' => $childCat,
                'system_prompt' => $llm['system_prompt'] ?? null,
                'compiled_prompt' => $llm['compiled_prompt'] ?? null,
                'prompt_tokens' => $llm['tokens']['prompt'] ?? 0,
                'completion_tokens' => $llm['tokens']['completion'] ?? 0,
                'total_tokens' => $llm['total_tokens'] ?? 0,
                'llm_duration_ms' => (int) ($llm['duration'] ?? 0),
                'embedding_duration_ms' => (int) ($embedding['duration'] ?? 0),
                'database_duration_ms' => (int) ($search['duration'] ?? 0),
                'total_duration_ms' => (int) $totalDuration,
            ]);
        } catch (Exception $e) {
            Log::error('Failed logging LLM generation metrics to DB: ' . $e->getMessage());
        }
    }
}