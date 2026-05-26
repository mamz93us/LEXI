<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Settings\SettingsService;
use Illuminate\Http\Client\Factory as HttpClient;
use RuntimeException;

/**
 * Thin wrapper around the Anthropic Messages API.
 *
 * Server-side only — never expose the key to the browser. Reads the
 * API key from the per-tenant Settings table at call time, with the
 * `.env`-backed config as fallback. So the firm can rotate the key
 * from Settings → AI without an SSH redeploy.
 *
 * Use the zero-retention tier in production. Every call site MUST log
 * a corresponding row to `ai_generations` for audit.
 */
final class AnthropicClient
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     */
    public function sendMessages(string $systemPrompt, array $messages): string
    {
        $apiKey = (string) $this->settings->get(
            'ai',
            'anthropic_api_key',
            config('lexa.anthropic.api_key', ''),
        );

        $model = (string) $this->settings->get(
            'ai',
            'anthropic_model',
            config('lexa.anthropic.model', 'claude-opus-4-7'),
        );

        $zeroRetention = (bool) $this->settings->get(
            'ai',
            'anthropic_zero_retention',
            config('lexa.anthropic.zero_retention', true),
        );

        $maxTokens = (int) $this->settings->get(
            'ai',
            'anthropic_max_tokens',
            config('lexa.anthropic.max_tokens', 4096),
        );

        $this->assertConfigured($apiKey);

        $headers = [
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ];

        if ($zeroRetention) {
            // The exact opt-out header is configured per-org in the Anthropic
            // contract. Replace this name once Anthropic confirms the header
            // value for the firm's zero-retention agreement.
            $headers['anthropic-beta'] = 'no-train-2025-01';
        }

        $response = $this->http
            ->withHeaders($headers)
            ->timeout(120)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => $maxTokens,
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

    private function assertConfigured(string $apiKey): void
    {
        if ($apiKey === '') {
            throw new RuntimeException(
                'Anthropic API key not set. Configure it from Settings → AI inside the app, '.
                'or set ANTHROPIC_API_KEY in .env as a fallback.'
            );
        }
    }
}
