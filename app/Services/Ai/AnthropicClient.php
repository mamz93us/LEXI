<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Http\Client\Factory as HttpClient;
use RuntimeException;

/**
 * Thin wrapper around the Anthropic Messages API.
 *
 * Server-side only — never expose the key to the browser. Use the
 * zero-retention tier in production (set ANTHROPIC_ZERO_RETENTION=true)
 * so prompts and outputs are not used for training. Every call site
 * MUST log a corresponding row to `ai_generations` for audit.
 */
final class AnthropicClient
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly bool $zeroRetention,
        private readonly int $maxTokens,
    ) {}

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     */
    public function sendMessages(string $systemPrompt, array $messages): string
    {
        $this->assertConfigured();

        $headers = [
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ];

        if ($this->zeroRetention) {
            // The exact opt-out header is configured per-org in the Anthropic
            // contract. Replace this name once Anthropic confirms the header
            // value for the firm's zero-retention agreement.
            $headers['anthropic-beta'] = 'no-train-2025-01';
        }

        $response = $this->http
            ->withHeaders($headers)
            ->timeout(120)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Anthropic request failed: '.$response->status().' '.$response->body()
            );
        }

        $blocks = $response->json('content') ?? [];
        $text = '';
        foreach ($blocks as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= ($block['text'] ?? '');
            }
        }

        return $text;
    }

    private function assertConfigured(): void
    {
        if ($this->apiKey === '') {
            throw new RuntimeException(
                'Anthropic API key not set. Configure ANTHROPIC_API_KEY in .env. '.
                'See config/lexa.php and docs/DECISIONS.md for the zero-retention requirement.'
            );
        }
    }
}
