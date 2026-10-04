<?php

namespace App\Filament\Admin\Resources\GradeLevels\Pages;

use App\Filament\Admin\Resources\GradeLevels\GradeLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGradeLevels extends ListRecords
{
    protected static string $resource = GradeLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
