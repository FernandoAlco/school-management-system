<?php

namespace App\Filament\Resources\GradeLevels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GradeLevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('Grade 1'),
                TextInput::make('sort_order')
                    ->label('Order')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(255),
            ]);
    }
}
