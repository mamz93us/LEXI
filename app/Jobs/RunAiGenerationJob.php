<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\InitialisesTenantFromRow;
use App\Models\AiGeneration;
use App\Services\Ai\AnthropicClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Run the Anthropic call for one AiGeneration row in the background.
 *
 * The wizard creates the row in `pending` status with the assembled prompt
 * payload embedded, then dispatches this job. The job updates `status` to
 * `generating` while it's working, then to `draft` (success) or `failed`
 * (any throwable). The Detail page polls until status leaves the in-flight
 * states, then shows the output.
 *
 * Why async: a long contract can take 30–120 seconds for Claude to draft.
 * Doing that inside a Livewire request risks hitting PHP-FPM's
 * `request_terminate_timeout` or an upstream proxy timeout, leaving the
 * UI spinner spinning forever. Queueing decouples the LLM latency from
 * the web request.
 *
 * Tenancy: the AnthropicClient reads its API key from the per-tenant
 * Settings table, so the job must run inside the same tenant context as
 * the originating request. We re-initialize tenancy from the row's
 * `tenant_id` before calling Anthropic.
 */
final class RunAiGenerationJob implements ShouldQueue
{
    use Dispatchable;
    use InitialisesTenantFromRow;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Allow the worker up to 5 minutes. Must exceed
     * `config('lexa.anthropic.timeout')` (default 280s) so the HTTP
     * timeout surfaces cleanly as a `cURL error 28` we can catch and
     * write to `output`, rather than the worker getting SIGKILL'd
     * mid-call and leaving the row stuck in `generating`.
     */
    public int $timeout = 320;

    /** Don't retry on failure — a bad prompt won't get better on a re-run. */
    public int $tries = 1;

    /**
     * @param  int  $generationId  ID of the AiGeneration row to fill
     * @param  array<int, array{role:string, content:string}>  $messages
     */
    public function __construct(
        public readonly int $generationId,
        public readonly string $systemPrompt,
        public readonly array $messages,
    ) {}

    public function handle(AnthropicClient $anthropic): void
    {
        // Bypass the global tenant scope to load the row by its raw id;
        // we'll re-establish tenancy from its tenant_id below.
        $gen = AiGeneration::withoutGlobalScopes()->find($this->generationId);
        if (! $gen) {
            return;
        }

        // Re-enter the originating tenant so settings & relationship lookups
        // (e.g. anthropic_api_key, model) resolve to the right firm — even if
        // a warm worker still has a DIFFERENT tenant initialised from the
        // previous job.
        $this->initialiseTenant($gen->tenant_id);

        $gen->update(['status' => 'generating']);

        try {
            $output = $anthropic->sendMessages($this->systemPrompt, $this->messages);
            $gen->update([
                'output' => $output,
                'status' => 'draft',
            ]);
        } catch (Throwable $e) {
            $gen->update([
                'output' => '[AI call failed: '.$e->getMessage().']',
                'status' => 'failed',
            ]);
            // Re-throw so Horizon shows the failed job and the developer can debug.
            throw $e;
        }
    }

    /**
     * Called by the queue worker if `handle()` throws and no retries remain.
     */
    public function failed(Throwable $e): void
    {
        $gen = AiGeneration::withoutGlobalScopes()->find($this->generationId);
        if ($gen && $gen->status !== 'failed') {
            $gen->update([
                'output' => '[AI call failed: '.$e->getMessage().']',
                'status' => 'failed',
            ]);
        }
    }
}
