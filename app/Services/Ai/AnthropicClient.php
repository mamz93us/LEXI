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
 * Zero-data-retention (ZDR) is enabled by Anthropic on the **account /
 * organisation** side via your Enterprise / commercial agreement — not
 * via a request header. The `anthropic_zero_retention` setting in this
 * app is purely informational so admins know what compliance posture
 * the firm has agreed to with Anthropic; we do not send it on the
 * wire. Verify the org-level ZDR status in your Anthropic Console.
 *
 * Every call site MUST log a corresponding row to `ai_generations`
 * for audit (handled by RagGenerator).
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

        // NOTE: We deliberately do NOT send an `anthropic-beta` header for ZDR.
        // Anthropic enables zero-data-retention at the org level (Console /
        // contract), not via a per-request beta flag. Sending a guessed value
        // here makes their validator reject the entire request.

        // Claude on 8k tokens of Arabic legal output can run 60–240 seconds.
        // Read-timeout is the time waiting for the response body; connect-
        // timeout stays short so a real network failure fails fast instead of
        // hanging the queue worker for minutes. The read timeout is intentionally
        // a bit shorter than RunAiGenerationJob::$timeout so the HTTP error
        // surfaces cleanly before the worker is killed.
        $timeout = (int) config('lexa.anthropic.timeout', 280);
        $connectTimeout = (int) config('lexa.anthropic.connect_timeout', 20);

        $response = $this->http
            ->withHeaders($headers)
            ->timeout($timeout)
            ->connectTimeout($connectTimeout)
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
