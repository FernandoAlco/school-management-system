<?php

namespace App\Filament\Admin\Resources\Subjects\Pages;

use App\Filament\Admin\Resources\Subjects\SubjectResource;
use App\Models\Subject;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubject extends EditRecord
{
    protected static string $resource = SubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (Subject $record): bool => $record->courses()->exists()),
        ];
    }
}
