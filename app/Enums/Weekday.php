<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Days of the week numbered as in ISO-8601 (1 = Monday … 7 = Sunday).
 */
enum Weekday: int implements HasLabel
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    public function getLabel(): string
    {
        return $this->name;
    }

    public function getShortLabel(): string
    {
        return substr($this->name, 0, 3);
    }

    /**
     * Days with classes: Monday to Friday.
     *
     * @return list<self>
     */
    public static function schoolDays(): array
    {
        return [self::Monday, self::Tuesday, self::Wednesday, self::Thursday, self::Friday];
    }
}
