<?php

declare(strict_types=1);

namespace App\Services\Documents;

/**
 * Replace `{{token}}` markers in a template body with values from a
 * data array. Missing tokens are left in place so the lawyer sees
 * exactly what's unfilled.
 *
 * Token syntax is intentionally narrow: `{{name}}` only. No expression
 * evaluation, no piping, no embedded PHP — that's an injection vector
 * for a document the firm will send to a court.
 */
final class TokenReplacer
{
    /** @param array<string, scalar|null> $data */
    public function replace(string $body, array $data): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/',
            function (array $m) use ($data): string {
                $key = $m[1];
                if (! array_key_exists($key, $data) || $data[$key] === null) {
                    return $m[0]; // leave the marker so the lawyer notices
                }

                return (string) $data[$key];
            },
            $body,
        ) ?? $body;
    }

    /** @return array<int, string> Unfilled token names found in the body */
    public function unfilled(string $body, array $data): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', $body, $matches);
        $names = array_unique($matches[1] ?? []);

        return array_values(array_filter(
            $names,
            fn (string $n) => ! array_key_exists($n, $data) || $data[$n] === null,
        ));
    }
}
