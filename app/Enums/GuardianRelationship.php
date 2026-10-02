<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GuardianRelationship: string implements HasLabel
{
    case Mother = 'mother';
    case Father = 'father';
    case LegalGuardian = 'legal_guardian';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mother => 'Mother',
            self::Father => 'Father',
            self::LegalGuardian => 'Legal Guardian',
            self::Other => 'Other',
        };
    }
}
