<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * "Case" is a reserved word in PHP so the model is named LegalCase.
 * The underlying table is still `cases`.
 */
class LegalCase extends Model
{
    use Auditable;
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cases';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'case_number',
        'title',
        'status',
        'dispute_value_piastres',
        'notes',
        'degree',
        'court_id',
        'case_type_id',
        'circuit',
        'roll_no',
        'parent_case_id',
        'appeal_type',
        'parties',
    ];

    protected function casts(): array
    {
        return [
            'dispute_value_piastres' => 'integer',
            'parties' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function caseType(): BelongsTo
    {
        return $this->belongsTo(CaseType::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_case_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_case_id');
    }

    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class);
    }

    public function judgments(): HasMany
    {
        return $this->hasMany(Judgment::class);
    }

    /**
     * Walk to the root of the chain (the original ابتدائي case) and return
     * every linked stage in ascending order — first instance → appeal →
     * cassation → ...
     */
    public function chain(): Collection
    {
        $root = $this;
        while ($root->parent) {
            $root = $root->parent;
        }

        $chain = collect([$root]);
        $this->collectDescendants($root, $chain);

        return new Collection($chain->all());
    }

    private function collectDescendants(self $case, \Illuminate\Support\Collection $chain): void
    {
        foreach ($case->children as $child) {
            $chain->push($child);
            $this->collectDescendants($child, $chain);
        }
    }
}
