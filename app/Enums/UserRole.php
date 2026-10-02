<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Guardian = 'guardian';
    case Student = 'student';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Teacher => 'Teacher',
            self::Guardian => 'Guardian',
            self::Student => 'Student',
        };
    }

    /**
     * @return list<self>
     */
    public static function adminRoles(): array
    {
        return [self::SuperAdmin, self::Admin];
    }
}
