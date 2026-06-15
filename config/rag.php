<?php

return [
    'context' => [
        'chars_per_token'    => (float) env('RAG_CHARS_PER_TOKEN', 2.7),
        'max_tokens'         => (int) env('RAG_MAX_CONTEXT_TOKENS', 6000),
    ],
    'search' => [
        'max_distance'       => (float) env('RAG_MAX_VECTOR_DISTANCE', 0.45),
        'max_chunks'         => (int) env('RAG_MAX_RETRIEVAL_CHUNKS', 4),
        'min_rff_score'      => (float) env('RAG_MIN_RFF_SCORE', 0.015),
        'rff_k'              => (int) env('RAG_RFF_K_VALUE', 60),
    ],
    'history' => [
        'max_recent'         => (int) env('RAG_MAX_RECENT_CONVERSATION_HISTORY', 3),
    ],
    'ingestion' => [
        'max_words'          => (int) env('RAG_INGEST_MAX_WORDS', 500),
        'overlap_words'      => (int) env('RAG_INGEST_OVERLAP_WORDS', 50),
    ],
];