<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One audit-trail entry. Deliberately does NOT use BelongsToTenant — it's
 * written from model events (which already run inside a tenant context)
 * and stamps `tenant_id` explicitly; reads are scoped manually in the
 * viewer. The log is append-only — never updated or deleted by app code.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null; // append-only; only created_at

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'before',
        'after',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionLabelAr(): string
    {
        return [
            'created' => 'إنشاء',
            'updated' => 'تعديل',
            'deleted' => 'حذف',
            'viewed' => 'اطّلاع',
        ][$this->action] ?? $this->action;
    }
}
