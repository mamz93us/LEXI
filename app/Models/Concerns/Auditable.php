<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\AuditLog;

/**
 * Append an audit-trail row whenever the model is created / updated /
 * deleted. Apply to legal-privilege models (cases, clients, documents,
 * proxies, companies) per CLAUDE.md §7.
 *
 * - Writes tenant_id from the active tenant, user_id from the auth user,
 *   ip from the request.
 * - Strips never-log fields (passwords, secrets, raw OCR/extracted blobs)
 *   and the noisy timestamps from the diff.
 * - `recordView()` is called explicitly from Detail/show components to log
 *   reads of sensitive records (PII / privileged content).
 *
 * The write is wrapped so an audit failure NEVER blocks the user's actual
 * operation — a logging hiccup must not lose a lawyer's edit.
 */
trait Auditable
{
    /** Fields that must never appear in an audit diff. */
    protected array $auditExclude = [
        'password',
        'remember_token',
        'extracted_text',
        'extracted_data',
        'updated_at',
        'created_at',
    ];

    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAudit('created', null, $model->auditableAttributes($model->getAttributes()));
        });

        static::updated(function ($model) {
            $changes = $model->auditableAttributes($model->getChanges());
            if (empty($changes)) {
                return; // nothing meaningful changed (e.g. only timestamps)
            }
            $before = array_intersect_key($model->getOriginal(), $changes);
            $model->writeAudit('updated', $model->auditableAttributes($before), $changes);
        });

        static::deleted(function ($model) {
            $model->writeAudit('deleted', $model->auditableAttributes($model->getOriginal()), null);
        });
    }

    /** Log an explicit read of this record (call from a Detail component). */
    public function recordView(): void
    {
        $this->writeAudit('viewed', null, null);
    }

    protected function auditableAttributes(array $attrs): array
    {
        return collect($attrs)
            ->except($this->auditExclude)
            ->all();
    }

    protected function writeAudit(string $action, ?array $before, ?array $after): void
    {
        try {
            $tenantId = function_exists('tenant') ? tenant('id') : null;
            if (! $tenantId) {
                return; // no tenant context (console/seed) — skip silently
            }

            AuditLog::create([
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'action' => $action,
                'auditable_type' => $this->getMorphClass(),
                'auditable_id' => $this->getKey(),
                'before' => $before ?: null,
                'after' => $after ?: null,
                'ip_address' => request()?->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let an audit write break the user's operation.
            report($e);
        }
    }
}
