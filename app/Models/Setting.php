<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Tenant-scoped key/value setting. Secret values (API keys) are
 * encrypted at rest using APP_KEY.
 */
class Setting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'group',
        'key',
        'value',
        'is_secret',
    ];

    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
        ];
    }

    public function setValueAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['value'] = null;

            return;
        }

        $this->attributes['value'] = $this->is_secret
            ? Crypt::encryptString((string) $value)
            : (string) $value;
    }

    public function getValueAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! $this->is_secret) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return null;
        }
    }
}
