<?php

namespace App\Filament\Admin\Resources\GradeLevels\Pages;

use App\Filament\Admin\Resources\GradeLevels\GradeLevelResource;
use App\Models\GradeLevel;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGradeLevel extends EditRecord
{
    protected static string $resource = GradeLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (GradeLevel $record): bool => $record->sections()->exists()),
        ];
    }
}
