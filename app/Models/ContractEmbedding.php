<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractEmbedding extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'document_id',
        'source_version_id',
        'chunk_index',
        'chunk_text',
        'metadata',
        // `embedding` is set via raw SQL in the RagRetrievalService — pgvector
        // type isn't natively mass-assignable through Eloquent.
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
