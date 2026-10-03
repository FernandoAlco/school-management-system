<?php

namespace App\Filament\Resources\Teachers\Schemas;

use App\Enums\TeacherStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                    ]),
                Section::make('Employment')
                    ->columns(2)
                    ->schema([
                        TextInput::make('employee_number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        DatePicker::make('hire_date'),
                        Select::make('status')
                            ->options(TeacherStatus::class)
                            ->default(TeacherStatus::Active)
                            ->required(),
                    ]),
            ]);
    }
}
