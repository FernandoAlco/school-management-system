<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SchoolSettings extends Settings
{
    public string $name;

    public ?string $logo_path;

    public ?string $address;

    public ?string $phone;

    public ?string $email;

    public ?string $principal_name;

    /**
     * Minimum score (on a 0-100 scale) a student needs to pass a course.
     */
    public int $passing_grade;

    public static function group(): string
    {
        return 'school';
    }
}
