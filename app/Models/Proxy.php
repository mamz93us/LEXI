<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proxy extends Model
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'proxies';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'type',
        'notary_serial',
        'issue_date',
        'expiry_date',
        'scope',
        'status',
        'file_path',
        'file_mime',
        'extracted_text',
        'extracted_data',
        'extraction_status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'extracted_data' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function authorizedLawyers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proxy_user');
    }

    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(LegalCase::class, 'proxy_case', 'proxy_id', 'case_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function hasFile(): bool
    {
        return ! empty($this->file_path);
    }

    /**
     * Surface the AI-extracted structured fields as a flat token map ready
     * for the variable system: `proxy.notary_serial`, `proxy.scope`,
     * `principal.name`, `agent.name`, etc.
     *
     * Used by the QuickDraft wizard so "link this proxy" auto-fills the
     * party + proxy.* tokens without the lawyer retyping anything.
     *
     * @return array<string, scalar|null>
     */
    public function toAiVariables(): array
    {
        $out = [
            'proxy.notary_serial' => $this->notary_serial,
            'proxy.type' => $this->type,
            'proxy.scope' => $this->scope,
            'proxy.issue_date' => $this->issue_date?->format('Y-m-d'),
            'proxy.expiry_date' => $this->expiry_date?->format('Y-m-d'),
        ];

        $data = is_array($this->extracted_data) ? $this->extracted_data : [];

        // Flatten parties from extracted_data['parties'][namespace] = {...fields}
        // into dotted tokens (principal.name, agent.national_id, etc.).
        foreach (($data['parties'] ?? []) as $namespace => $fields) {
            if (! is_array($fields)) {
                continue;
            }
            foreach ($fields as $field => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $key = is_string($namespace) && is_string($field)
                    ? "{$namespace}.{$field}"
                    : null;
                if ($key && is_scalar($value)) {
                    $out[$key] = $value;
                }
            }
        }

        // Top-level extracted fields ({notary_serial, scope, witnesses, ...})
        // override the basic proxy.* placeholders if present.
        foreach (($data['proxy'] ?? []) as $field => $value) {
            if ($value === null || $value === '' || ! is_scalar($value)) {
                continue;
            }
            $out["proxy.{$field}"] = $value;
        }

        return array_filter($out, fn ($v) => $v !== null && $v !== '');
    }
}
