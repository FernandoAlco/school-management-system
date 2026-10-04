<?php

namespace App\Filament\Admin\Resources\AcademicYears\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class TermsRelationManager extends RelationManager
{
    protected static string $relationship = 'terms';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Semester 1'),
                TextInput::make('sort_order')
                    ->label('Order')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(255)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('academic_year_id', $this->getOwnerRecord()->getKey())),
                DatePicker::make('starts_on')
                    ->required()
                    ->minDate(fn () => $this->getOwnerRecord()->starts_on)
                    ->maxDate(fn () => $this->getOwnerRecord()->ends_on),
                DatePicker::make('ends_on')
                    ->required()
                    ->after('starts_on')
                    ->maxDate(fn () => $this->getOwnerRecord()->ends_on),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('starts_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
