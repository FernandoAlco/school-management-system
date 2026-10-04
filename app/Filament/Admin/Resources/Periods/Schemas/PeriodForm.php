<?php

namespace App\Filament\Admin\Resources\Periods\Schemas;

use App\Models\Period;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Class period')
                    ->description('Breaks are the gaps between periods.')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('1st period'),
                        TimePicker::make('starts_at')
                            ->label('Starts at')
                            ->seconds(false)
                            ->required(),
                        TimePicker::make('ends_at')
                            ->label('Ends at')
                            ->seconds(false)
                            ->required()
                            ->after('starts_at')
                            ->rule(fn (Get $get, ?Period $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                if (blank($get('starts_at')) || blank($value)) {
                                    return;
                                }

                                $overlappingPeriod = Period::query()
                                    ->overlapping($get('starts_at'), $value)
                                    ->when($record, fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()))
                                    ->first();

                                if ($overlappingPeriod !== null) {
                                    $fail("The period overlaps {$overlappingPeriod->getLabel()}.");
                                }
                            }),
                    ]),
            ]);
    }
}
