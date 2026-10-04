<?php

namespace App\Filament\Admin\Resources\Students\Pages;

use App\Filament\Admin\Resources\Students\StudentResource;
use App\Models\Student;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make()
                ->hidden(fn (Student $record): bool => ! $record->trashed() || $record->enrollments()->exists()),
            RestoreAction::make(),
        ];
    }
}
