<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Embeddings
    |--------------------------------------------------------------------------
    | Driver: which EmbeddingDriver implementation to use. The chosen model
    | MUST match the `dimension` value below — the `contract_embeddings`
    | table is allocated with `vector(dimension)` on Postgres and changing
    | it later requires a backfill migration.
    |
    | Decide the model only after the Phase 2 retrieval-quality eval on
    | real Arabic firm contracts (see docs/DECISIONS.md).
    */
    'embeddings' => [
        'driver' => env('EMBEDDINGS_DRIVER', 'null'),
        'model' => env('EMBEDDINGS_MODEL', 'embed-multilingual-v3.0'),
        'dimension' => (int) env('EMBEDDINGS_DIMENSION', 1024),
        'api_key' => env('EMBEDDINGS_API_KEY'),
        'base_url' => env('EMBEDDINGS_BASE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anthropic Claude API
    |--------------------------------------------------------------------------
    | Used by the RAG generator. Key MUST be server-side only. Use the
    | zero-retention tier in production and document it in the firm DPA.
    */
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-4-7'),
        'zero_retention' => (bool) env('ANTHROPIC_ZERO_RETENTION', true),
        // Long legal contracts can run 6k–12k tokens. 8192 is a safer default
        // than 4096 for drafting; admins can override per-tenant in Settings → AI.
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 8192),
        // HTTP read timeout in seconds. Claude on 8k tokens of Arabic output
        // routinely runs 60–240s, so we wait up to ~280s before giving up.
        // Kept under RunAiGenerationJob::$timeout (300s) so a clean error
        // surfaces before the worker is killed.
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 280),
        // Connect timeout — short so a real network failure (DNS / TLS) fails
        // fast instead of hanging the worker for minutes.
        'connect_timeout' => (int) env('ANTHROPIC_CONNECT_TIMEOUT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG retrieval
    |--------------------------------------------------------------------------
    */
    'rag' => [
        'top_k' => (int) env('RAG_TOP_K', 8),
        'distance' => env('RAG_DISTANCE', 'cosine'), // cosine | l2 | inner
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax
    |--------------------------------------------------------------------------
    | Egyptian VAT on legal services. Stored as a fraction (0.14 = 14%).
    | Never hardcode this in logic — invoicing reads it from here so a
    | rate change is a one-line config edit, and a future per-tenant
    | override can layer on top.
    */
    'tax' => [
        'vat_rate' => (float) env('VAT_RATE', 0.14),
    ],
];
