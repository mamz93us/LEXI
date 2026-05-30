<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will be used as the subdomain.
    |
    */
    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    */
    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    */
    'waits' => [
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    */
    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    */
    'silenced' => [],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    */
    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    */
    'memory_limit' => 128,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | The `timeout` here is the per-job kill switch enforced by the Horizon
    | supervisor. It MUST exceed `RunAiGenerationJob::$timeout` (320s) and
    | the upstream HTTP timeout (`config('lexa.anthropic.timeout')` = 280s).
    |
    | The `retry_after` setting in config/queue.php must in turn exceed THIS
    | value — that's the queue's "presumed dead, re-queue" threshold.
    |
    | Order of timeouts (each must exceed the previous):
    |   1. Anthropic HTTP read timeout: 280s
    |   2. Job timeout:                 320s
    |   3. Horizon supervisor timeout:  360s
    |   4. Queue retry_after:           400s
    */
    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            // Memory threshold (MB) at which the worker gracefully restarts
            // BETWEEN jobs. Vision-OCR ingestion + embedding is memory-heavy,
            // so give headroom; the per-job ini_set('memory_limit') in
            // IngestDocumentJob is the hard ceiling during a job.
            'memory' => 512,
            'tries' => 1,
            'timeout' => 360,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 2,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'timeout' => 360,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 1,
                'timeout' => 360,
            ],
        ],
    ],

];
