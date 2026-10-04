<?php

namespace App\Filament\Admin\Resources\Students\Schemas;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('birth_date')
                            ->required()
                            ->maxDate(now()),
                        Select::make('gender')
                            ->options(Gender::class),
                        TextInput::make('national_id')
                            ->label('National ID')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('address')
                            ->maxLength(255),
                        FileUpload::make('photo_path')
                            ->label('Photo')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('students')
                            ->columnSpanFull(),
                    ]),
                Section::make('School')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('student_number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->options(StudentStatus::class)
                            ->default(StudentStatus::Active)
                            ->required(),
                    ]),
            ]);
    }
}
