<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class AiGeneration extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'subject_type',
        'subject_id',
        'parent_id',
        'model',
        'prompt',
        'user_instruction',
        'revision_kind',
        'retrieved_chunk_ids',
        'output',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'retrieved_chunk_ids' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Walk up to the original (initial) generation that started this thread.
     */
    public function root(): self
    {
        $root = $this;
        while ($root->parent_id && $root->parent) {
            $root = $root->parent;
        }

        return $root;
    }

    /**
     * Full revision chain from initial → latest, in chronological order.
     *
     * @return Collection<int, AiGeneration>
     */
    public function chain(): Collection
    {
        $root = $this->root();
        $chain = collect([$root]);
        $this->walkDescendants($root, $chain);

        return $chain->sortBy('created_at')->values();
    }

    private function walkDescendants(self $node, Collection $chain): void
    {
        $children = $node->revisions()->orderBy('created_at')->get();
        foreach ($children as $child) {
            $chain->push($child);
            $this->walkDescendants($child, $chain);
        }
    }

    /**
     * @return EloquentCollection<int, AiGeneration>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
