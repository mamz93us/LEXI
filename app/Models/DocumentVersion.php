<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'document_id',
        'version_no',
        'template_version_id',
        'clause_version_ids',
        'filled_data',
        'storage_ref',
        'pdf_storage_ref',
        'created_by_user_id',
        'locked',
    ];

    protected function casts(): array
    {
        return [
            'clause_version_ids' => 'array',
            'filled_data' => 'array',
            'locked' => 'boolean',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
