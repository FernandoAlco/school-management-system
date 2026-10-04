<?php

namespace App\Filament\Admin\Resources\Teachers\Pages;

use App\Filament\Admin\Resources\Teachers\TeacherResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;
}
