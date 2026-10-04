<?php

namespace App\Filament\Admin\Resources\Sections\Pages;

use App\Filament\Admin\Resources\Sections\SectionResource;
use App\Models\Section;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSection extends EditRecord
{
    protected static string $resource = SectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (Section $record): bool => $record->enrollments()->exists()),
        ];
    }
}
