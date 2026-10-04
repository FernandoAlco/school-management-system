<?php

namespace App\Filament\Admin\Resources\AcademicYears\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AcademicYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Academic year')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('2026-2027'),
                        DatePicker::make('starts_on')
                            ->required(),
                        DatePicker::make('ends_on')
                            ->required()
                            ->after('starts_on'),
                    ]),
            ]);
    }
}
