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
        'name_ar',
        'email',
        'password',
        'role',
        'phone',
        'national_id',
        'bar_association_no',
        'nationality',
        'address',
        'is_active',
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
            'is_active' => 'boolean',
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

    public function canManageUsers(): bool
    {
        return in_array($this->role, [UserRole::Partner, UserRole::Admin], true);
    }

    /**
     * Map this firm lawyer into the predefined party-field schema so they
     * can stand in for a Client when used as the agent (الوكيل) on a
     * توكيل. Identity fields that are populated on the user row map
     * directly; anything blank stays null so the AI prompt knows it
     * still needs filling.
     *
     * @return array<string, string|null>
     */
    public function toAiVariables(): array
    {
        return [
            'name' => $this->name_ar ?: $this->name,
            'name_en' => $this->name,
            'national_id' => $this->national_id,
            'commercial_register_no' => null,
            'address' => $this->address,
            'phone' => $this->phone,
            'whatsapp' => null,
            'email' => $this->email,
            'nationality' => $this->nationality ?: 'مصري',
            'religion' => null,
            'profession' => 'محامٍ'.($this->bar_association_no
                ? ' (نقابة المحامين رقم '.$this->bar_association_no.')'
                : ''),
            'date_of_birth' => null,
            'type' => 'lawyer',
        ];
    }
}
