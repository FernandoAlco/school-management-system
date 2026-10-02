<?php

namespace App\Filament\Pages;

use App\Settings\SchoolSettings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSchoolSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'School Settings';

    protected static ?string $title = 'School Settings';

    protected static string $settings = SchoolSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('School')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('principal_name')
                            ->label('Principal')
                            ->maxLength(255),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('school')
                            ->columnSpanFull(),
                    ]),
                Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        Textarea::make('address')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Grading')
                    ->description('Grades use a 0-100 scale.')
                    ->schema([
                        TextInput::make('passing_grade')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(100),
                    ]),
            ]);
    }
}
