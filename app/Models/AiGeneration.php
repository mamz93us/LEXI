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
     * Loads the entire tree in a SINGLE query (all rows sharing this
     * thread's root) and orders them, instead of recursively querying
     * each node's children — the old approach issued one query per node
     * and ran on every 2s wire:poll of the Detail page.
     *
     * @return Collection<int, AiGeneration>
     */
    public function chain(): Collection
    {
        $root = $this->root();

        // Every revision in a thread shares the same subject + the root in
        // its ancestry. Pull the root plus all descendants in one go by
        // matching the subject and walking parent_ids in memory.
        $all = static::query()
            ->where('subject_type', $root->subject_type)
            ->where('subject_id', $root->subject_id)
            ->orderBy('created_at')
            ->get();

        // Keep only rows reachable from this root via parent_id, so two
        // independent threads on the same subject don't bleed together.
        $inThread = collect([$root->id => $root]);
        // Iterate until no new descendants are added (bounded by row count).
        do {
            $added = false;
            foreach ($all as $node) {
                if ($node->parent_id
                    && $inThread->has($node->parent_id)
                    && ! $inThread->has($node->id)
                ) {
                    $inThread->put($node->id, $node);
                    $added = true;
                }
            }
        } while ($added);

        return $inThread->values()->sortBy('created_at')->values();
    }

    /**
     * @return EloquentCollection<int, AiGeneration>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
