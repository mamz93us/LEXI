<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Models\Client;
use App\Models\Court;
use Illuminate\Support\Carbon;

/**
 * Build the flat token → value map the wizard / TokenReplacer / Claude
 * prompt all expect.
 *
 * The wizard collects:
 *   - parties: ['seller' => clientId, 'buyer' => clientId, ...]
 *   - contractMeta: ['contract.place' => 'القاهرة', 'court.name' => '12', ...]
 *   - freeText: ['user_intent' => '...'] (passed through untouched)
 *
 * This service expands each party id into its full set of dotted tokens
 * (`seller.name`, `seller.national_id`, ...) using Client::toAiVariables(),
 * resolves court IDs to court names, fills `today` automatically, and
 * returns one merged array ready for TokenReplacer::replace().
 */
final class VariableResolver
{
    /**
     * @param  array<string, int|string|null>  $parties  namespace → client id
     * @param  array<string, mixed>  $contractMeta  contract.* / court.* / firm.* tokens
     * @param  array<string, mixed>  $extra  passthrough extras (legacy filled vars)
     * @return array<string, scalar|null> flat dotted-key → value map
     */
    public function resolve(array $parties, array $contractMeta, array $extra = []): array
    {
        $out = [];

        foreach ($parties as $namespace => $clientId) {
            if (! $clientId) {
                continue;
            }
            // BelongsToTenant scope auto-filters; a foreign tenant's id will not resolve.
            $client = Client::query()->find($clientId);
            if (! $client) {
                continue;
            }
            foreach ($client->toAiVariables() as $field => $value) {
                $out["{$namespace}.{$field}"] = $value;
            }
        }

        foreach ($contractMeta as $token => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if ($token === 'court.name' && is_numeric($value)) {
                // Resolve a court FK id to its Arabic name and infer the city.
                $court = Court::query()->find((int) $value);
                if ($court) {
                    $out['court.name'] = $court->name_ar ?: $court->name_en;
                    if (! array_key_exists('court.city', $contractMeta) || $contractMeta['court.city'] === '') {
                        $out['court.city'] = $court->governorate;
                    }

                    continue;
                }
            }
            $out[$token] = is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        // Always provide `today` so a template can stamp the draft date.
        if (! array_key_exists('today', $out) || $out['today'] === null || $out['today'] === '') {
            $out['today'] = Carbon::now()->format('Y-m-d');
        }

        // Free-form extras win when they don't clash with a resolved token.
        foreach ($extra as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (! array_key_exists($key, $out)) {
                $out[$key] = is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return $out;
    }
}
