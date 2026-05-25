<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Partner = 'partner';
    case Associate = 'associate';
    case Paralegal = 'paralegal';
    case Admin = 'admin';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Partner => 'شريك',
            self::Associate => 'محامٍ',
            self::Paralegal => 'مساعد قانوني',
            self::Admin => 'مدير',
            self::Client => 'موكّل',
        };
    }
}
