<?php

namespace App\Filament\Admin\Resources\Subjects\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subject')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('MATH'),
                    ]),
            ]);
    }
}
