<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'company_id',
        'number',
        'issue_date',
        'due_date',
        'status',
        'currency',
        'subtotal_piastres',
        'tax_piastres',
        'total_piastres',
        'paid_piastres',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal_piastres' => 'integer',
            'tax_piastres' => 'integer',
            'total_piastres' => 'integer',
            'paid_piastres' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function balancePiastres(): int
    {
        return max(0, $this->total_piastres - $this->paid_piastres);
    }

    public function recalculate(): void
    {
        $subtotal = (int) $this->lines()->sum('amount_piastres');
        $tax = (int) round($subtotal * 0.14);
        $total = $subtotal + $tax;
        $paid = (int) $this->payments()->sum('amount_piastres');

        $this->update([
            'subtotal_piastres' => $subtotal,
            'tax_piastres' => $tax,
            'total_piastres' => $total,
            'paid_piastres' => $paid,
            'status' => $this->resolveStatus($total, $paid),
        ]);
    }

    private function resolveStatus(int $total, int $paid): string
    {
        if ($this->status === 'void') {
            return 'void';
        }
        if ($total === 0) {
            return 'draft';
        }
        if ($paid <= 0) {
            return $this->status === 'sent' ? 'sent' : 'draft';
        }
        if ($paid < $total) {
            return 'partly_paid';
        }

        return 'paid';
    }
}
