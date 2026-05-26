<?php

declare(strict_types=1);

namespace App\Services\Documents;

/**
 * Replace `{{token}}` markers in a template body with values from a
 * data array. Missing tokens are left in place so the lawyer sees
 * exactly what's unfilled.
 *
 * Supports both flat tokens (`{{name}}`) and dotted tokens
 * (`{{seller.name}}`, `{{contract.place}}`). For dotted tokens the data
 * array may be either flat with the dotted key as-is, or nested:
 *   ['seller' => ['name' => '...']]
 *
 * Token syntax is intentionally narrow: identifier + dots only. No
 * expression evaluation, no piping, no embedded PHP — that's an
 * injection vector for a document the firm will send to a court.
 */
final class TokenReplacer
{
    /** Pattern accepts `identifier` or `identifier.identifier[.identifier...]` */
    private const TOKEN_PATTERN = '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*)\s*\}\}/';

    /** @param array<string, mixed> $data */
    public function replace(string $body, array $data): string
    {
        return preg_replace_callback(
            self::TOKEN_PATTERN,
            function (array $m) use ($data): string {
                $value = $this->lookup($m[1], $data);
                if ($value === null) {
                    return $m[0]; // leave the marker so the lawyer notices
                }

                return (string) $value;
            },
            $body,
        ) ?? $body;
    }

    /** @return array<int, string> Unfilled token names found in the body */
    public function unfilled(string $body, array $data): array
    {
        preg_match_all(self::TOKEN_PATTERN, $body, $matches);
        $names = array_unique($matches[1] ?? []);

        return array_values(array_filter(
            $names,
            fn (string $n) => $this->lookup($n, $data) === null,
        ));
    }

    /**
     * Look up a (possibly dotted) key in the data array.
     * First tries the dotted key as a flat lookup; falls back to walking
     * the array tree using each segment.
     *
     * @param  array<string, mixed>  $data
     */
    private function lookup(string $key, array $data): mixed
    {
        if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
            return is_scalar($data[$key]) ? $data[$key] : null;
        }

        if (! str_contains($key, '.')) {
            return null;
        }

        $segments = explode('.', $key);
        $cursor = $data;
        foreach ($segments as $seg) {
            if (! is_array($cursor) || ! array_key_exists($seg, $cursor)) {
                return null;
            }
            $cursor = $cursor[$seg];
        }
        if ($cursor === null || $cursor === '') {
            return null;
        }

        return is_scalar($cursor) ? $cursor : null;
    }
}
