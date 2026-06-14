<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'llm' => [
        'provider' => env('LLM_PROVIDER', 'groq'),
    ],

    'embedding' => [
        'provider' => env('EMBEDDING_PROVIDER', 'huggingface'),
    ],

    'huggingface' => [
        'key' => env('HUGGINGFACE_API_KEY'),
        'embedding_model' => env('HUGGINGFACE_EMBEDDING_MODEL', 'intfloat/multilingual-e5-large'),
        'embedding_model_dimensions' => env('HUGGINGFACE_EMBEDDING_MODEL_DIMENSIONS', 1024),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'llm_model' => env('GROQ_LLM_MODEL', 'llama-3.1-8b-instant'),
        'llm_model_temperature' => env('GROQ_LLM_MODEL_TEMPERATURE', 0.1),
        'llm_model_max_output_tokens' => env('GROQ_LLM_MODEL_MAX_OUTPUT_TOKENS', 1024),
    ],

    'openai' => [
        'llm_model' => env('OPENAI_LLM_MODEL', 'gpt-4o-mini'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'embedding_model_dimensions' => env('OPENAI_EMBEDDING_MODEL_DIMENSIONS', 1536),
        'llm_model_temperature' => env('OPENAI_LLM_MODEL_TEMPERATURE', 0.1),
        'llm_model_max_output_tokens' => env('OPENAI_LLM_MODEL_MAX_OUTPUT_TOKENS', 1024),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
