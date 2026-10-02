<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('school.name', 'My School');
        $this->migrator->add('school.logo_path', null);
        $this->migrator->add('school.address', null);
        $this->migrator->add('school.phone', null);
        $this->migrator->add('school.email', null);
        $this->migrator->add('school.principal_name', null);
        $this->migrator->add('school.passing_grade', 60);
    }
};
