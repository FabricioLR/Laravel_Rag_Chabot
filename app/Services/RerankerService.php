<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RerankerService
{
    public function rerank(string $query, array $context, int $topN = 4): array
    {
        try {
            $startTime = microtime(true);
            $apiKey = config('services.voyage.api_key', env('VOYAGE_API_KEY', ''));
            $model  = config('services.voyage.reranker_model', env('VOYAGE_RERANKER_MODEL', 'rerank-2.5'));
            $enabled = config("rag.reranker.enabled", env("RAG_ENABLE_RERANKER", true));

            if (empty($context)) {
                return [
                    'documents' => [],
                    'telemetry'       => null,
                ];
            }

            if (!$enabled) {
                return [
                    'documents' => array_slice($context, 0, $topN),
                    'telemetry'       => null,
                ];
            }

            if (count($context) <= $topN){
                return [
                    'documents' => $context,
                    'telemetry' => [
                        'duration' => 0,
                        'total_tokens' => 0,
                    ]
                ];
            }

            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.voyageai.com/v1/rerank', [
                    'query'     => $query,
                    'documents' => $context,
                    'model'     => $model,
                    'top_k'     => $topN,
                ]);

            if ($response->failed()) {
                Log::error('Voyage Reranker API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return [
                    'documents' => array_slice($context, 0, $topN),
                    'telemetry'       => null,
                ];
            }

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $data = $response->json();
            $rerankedDocs = [];

            foreach ($data['data'] as $item) {
                $originalIndex = $item['index'];
                $rerankedDocs[] = $context[$originalIndex];
            }

            return [
                'documents' => $rerankedDocs,
                'telemetry'       => [
                    'duration' => $durationMs,
                    'total_tokens' => $data['usage']['total_tokens'] ?? 0,
                ],
            ];

        } catch (\Throwable $e) {
            Log::error('Reranker exception triggered: ' . $e->getMessage());

            return [
                'documents' => array_slice($context, 0, $topN),
                'telemetry'       => null,
            ];
        }
    }
}