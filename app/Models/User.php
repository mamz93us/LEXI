<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPartner(): bool
    {
        return $this->role === UserRole::Partner;
    }

    public function isCentral(): bool
    {
        return $this->tenant_id === null;
    }

    /**
     * Map this firm lawyer into the predefined party-field schema so they
     * can stand in for a Client when used as the agent (الوكيل) on a
     * توكيل. Only fields the User model actually carries are populated;
     * the rest stay null and the AI sees missing data so it can prompt
     * the lawyer to fill in (or use [...] placeholders in the draft).
     *
     * @return array<string, string|null>
     */
    public function toAiVariables(): array
    {
        return [
            'name' => $this->name,
            'name_en' => $this->name,
            'national_id' => null,
            'commercial_register_no' => null,
            'address' => null,
            'phone' => $this->phone,
            'whatsapp' => null,
            'email' => $this->email,
            'nationality' => 'مصري',
            'religion' => null,
            'profession' => 'محامٍ',
            'date_of_birth' => null,
            'type' => 'lawyer',
        ];
    }
}
