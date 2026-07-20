<?php

namespace App\Services;
use Exception;

class PromptBuilder
{
    public function buildFallbackPrompt(string $userInput, string $formattedCategories): array
    {
        $systemPrompt = config('services.llm.fallback_system_prompt', env('LLM_FALLBACK_SYSTEM_PROMPT', ''));

        if (empty($systemPrompt)) {
            throw new Exception("LLM Fallback Default System Prompt envinronment variable must be provided.");
        }

        $userPrompt = <<<PROMPT
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

        return [$systemPrompt, $userPrompt];
    }

    public function buildStandardPrompt(string $userInput, string $context, string $history): array
    {
        $systemPrompt = config('services.llm.default_system_prompt', env('LLM_DEFAULT_SYSTEM_PROMPT', ''));

        if (empty($systemPrompt)) {
            throw new Exception("LLM Default System Prompt envinronment variable must be provided.");
        }

        $userPrompt = <<<PROMPT
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

        return [$systemPrompt, $userPrompt];
    }

    public function buildQueryRewriterPrompt(string $userInput, string $conversationHistory): array
    {
        $systemPrompt = config('services.llm.query_rewriter_system_prompt', env('LLM_QUERY_REWRITER_SYSTEM_PROMPT', ''));

        if (empty($systemPrompt)) {
            throw new Exception("LLM Query Rewriter System Prompt envinronment variable must be provided.");
        }

        $userPrompt = <<<PROMPT
# [HISTÓRICO DA CONVERSA]
{$conversationHistory}

# [ÚLTIMA MENSAGEM DO USUÁRIO]
"{$userInput}"

# [PERGUNTA REESCRITA]
PROMPT;

        return [$systemPrompt, $userPrompt];
    }
}