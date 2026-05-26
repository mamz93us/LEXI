<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Setting;

/**
 * Read/write tenant-scoped settings with env-config fallback.
 *
 * Usage:
 *   $settings->get('ai', 'anthropic_api_key', config('lexa.anthropic.api_key'))
 *   $settings->set('ai', 'anthropic_api_key', $value, isSecret: true)
 */
final class SettingsService
{
    /** @var array<string, mixed> request-scoped cache */
    private array $cache = [];

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $cacheKey = "{$group}.{$key}";
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey] ?? $default;
        }

        if (! tenancy()->initialized) {
            return $default;
        }

        $row = Setting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        $value = $row?->value;
        $this->cache[$cacheKey] = $value;

        return ($value === null || $value === '') ? $default : $value;
    }

    public function set(string $group, string $key, mixed $value, bool $isSecret = false): void
    {
        $existing = Setting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        if ($existing) {
            $existing->is_secret = $isSecret;
            $existing->value = $value;
            $existing->save();
        } else {
            Setting::create([
                'group' => $group,
                'key' => $key,
                'is_secret' => $isSecret,
                'value' => $value,
            ]);
        }

        $this->cache["{$group}.{$key}"] = $value;
    }

    public function has(string $group, string $key): bool
    {
        return $this->get($group, $key) !== null;
    }

    public function forget(string $group, string $key): void
    {
        Setting::query()->where('group', $group)->where('key', $key)->delete();
        unset($this->cache["{$group}.{$key}"]);
    }

    /**
     * Bulk-get all settings in a group as an associative array.
     *
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        if (! tenancy()->initialized) {
            return [];
        }

        return Setting::query()
            ->where('group', $group)
            ->get()
            ->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])
            ->all();
    }
}
