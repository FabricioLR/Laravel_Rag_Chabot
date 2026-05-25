<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{

    public function chat(Request $request)
    {
        $request->validate([
            'chatInput' => 'required|string',
        ]);

        $userInput = $request->input('chatInput');
        Log::info('Chatbot pipeline started.', ['user_input' => $userInput]);

        $totalStartTime = microtime(true);

        // ==========================================
        // 1. STEP: HUGGING FACE EMBEDDINGS
        // ==========================================
        $hfStartTime = microtime(true);
        
        $hfResponse = Http::withToken(env('HUGGINGFACE_API_KEY'))
            ->post('https://router.huggingface.co/hf-inference/models/intfloat/multilingual-e5-large/pipeline/feature-extraction', [
                'inputs' => "query: " . $userInput,
            ]);

        $hfDuration = round((microtime(true) - $hfStartTime) * 1000, 2);

        if ($hfResponse->failed()) {
            Log::error('Failed to generate text embeddings via Hugging Face.', [
                'duration_ms' => $hfDuration,
                'status' => $hfResponse->status(),
                'response' => $hfResponse->body()
            ]);
            return response()->json(['error' => 'Failed to generate text embeddings.'], 500);
        }

        Log::info('Hugging Face embeddings retrieved successfully.', ['duration_ms' => $hfDuration]);

        $vectorArray = $hfResponse->json();
        $vectorString = '[' . implode(',', $vectorArray) . ']';

        // ==========================================
        // 2. STEP: POSTGRESQL HYBRID RRF SEARCH
        // ==========================================
        $dbStartTime = microtime(true);

        $results = DB::connection('pgvector')->select("
            SELECT
                searches.id,
                searches.text,
                searches.metadata,
                sum(rrf_score(searches.rank::int, 60)) AS score
            FROM (
                (
                    SELECT
                        id,
                        text,
                        metadata,
                        rank() OVER (ORDER BY :vector1::vector <=> embedding) AS rank
                    FROM vectors
                    WHERE (:vector2::vector <=> embedding) < 0.45
                    ORDER BY :vector3::vector <=> embedding
                    LIMIT 40
                )
                UNION ALL
                (
                    SELECT
                        id,
                        text,
                        metadata,
                        rank() OVER (ORDER BY ts_rank_cd(to_tsvector('portuguese', text), plainto_tsquery('portuguese', :input1)) DESC) AS rank
                    FROM vectors
                    WHERE
                        plainto_tsquery('portuguese', :input2) @@ to_tsvector('portuguese', text)
                    ORDER BY rank
                    LIMIT 40
                )
            ) searches
            GROUP BY searches.id, searches.text, searches.metadata
            HAVING sum(rrf_score(searches.rank::int, 60)) > 0.032
            ORDER BY score DESC
            LIMIT 10;
        ", [
            'vector1' => $vectorString,
            'vector2' => $vectorString,
            'vector3' => $vectorString,
            'input1'  => $userInput,
            'input2'  => $userInput,
        ]);

        $dbDuration = round((microtime(true) - $dbStartTime) * 1000, 2);

        if (empty($results)) {
            Log::warning('Hybrid Search returned 0 results above the 0.032 threshold score.', [
                'duration_ms' => $dbDuration,
                'user_input' => $userInput
            ]);
            return response()->json(['error' => 'Failed to retrieve context.'], 500);
        }

        Log::info('Hybrid search execution completed.', [
            'duration_ms' => $dbDuration,
            'results_count' => count($results),
            'highest_rrf_score' => $results[0]->score ?? null
        ]);

        // Build the Context String
        $context = "";
        foreach ($results as $index => $item) {
            $metadata = json_decode($item->metadata, true);
            $sourceUrl = $metadata['source_post_url'] ?? 'N/A';
            $sourceTitle = $metadata['source_post_title'] ?? 'N/A';
            
            $context .= "<context_" . ($index + 1) . ">\n";
            $context .= "[Fonte]: " . $sourceUrl . "\n";
            $context .= "[Título]: " . $sourceTitle . "\n";
            $context .= "[Texto]: " . $item->text . "\n";
            $context .= "</context_" . ($index + 1) . ">\n\n";
        }

        // Prompts Configuration
        $systemPrompt = "Você é um assistente virtual especialista no sistema ERP. Seu objetivo é responder às dúvidas dos usuários com base EXCLUSIVAMENTE nos fragmentos de documentos fornecidos abaixo.\n" .
                        "REGRAS OBRIGATÓRIAS E FORMATAÇÃO:\n" .
                        "- Sempre que você utilizar uma informação de um bloco de contexto para responder ao usuário, você DEVE incluir o link da 'Fonte' correspondente logo após a afirmação (ex: [Texto do Link](url_da_fonte)).\n" .
                        "- Nunca invente URLs. Use estritamente as URLs fornecidas dentro das tags de contexto.\n" .
                        "- Seja direto, claro e profissional.\n" .
                        "- Se a resposta não puder ser extraída do contexto fornecido, responda honestamente que não possui essa informação.";

        $prompt = "# [CONTEXTO RECUPERADO / DADOS DA SEARCH]\n" .
                "Abaixo estão as informações extraídas da base de conhecimento que podem ajudar a responder à pergunta.\n\n" .
                $context . "\n" .
                "---\n\n" .
                "# [PERGUNTA DO USUÁRIO]\n" .
                $userInput . "\n\n" .
                "---\n\n" .
                "# [RESPOSTA DO ASSISTENTE]\n";

        // ==========================================
        // 3. STEP: GROQ LLM INFERENCE
        // ==========================================
        $groqStartTime = microtime(true);

        $groqResponse = Http::withToken(env('GROQ_API_KEY'))
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.1-8b-instant',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1,
                'max_tokens' => 1024
            ]);

        $groqDuration = round((microtime(true) - $groqStartTime) * 1000, 2);

        if ($groqResponse->failed()) {
            Log::error('Failed to get LLM response from Groq API.', [
                'duration_ms' => $groqDuration,
                'status' => $groqResponse->status(),
                'response' => $groqResponse->body()
            ]);
            return response()->json(['error' => 'Failed to generate AI response.'], 500);
        }

        $answer = $groqResponse->json()['choices'][0]['message']['content'] ?? 'Desculpe, ocorreu um erro.';
        
        $totalDuration = round((microtime(true) - $totalStartTime) * 1000, 2);
        
        Log::info('Chatbot pipeline fully completed.', [
            'total_duration_ms' => $totalDuration,
            'breakdown_ms' => [
                'embeddings' => $hfDuration,
                'database' => $dbDuration,
                'llm' => $groqDuration
            ]
        ]);

        return response()->json(['answer' => $answer, 'question' => $userInput]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}