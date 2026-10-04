<?php

namespace App\Filament\Admin\Resources\Students\RelationManagers;

use App\Enums\GuardianRelationship;
use App\Models\Guardian;
use App\Models\Student;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GuardiansRelationManager extends RelationManager
{
    protected static string $relationship = 'guardians';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                ...static::pivotFields(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (Guardian $record): string => $record->full_name)
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('pivot.relationship')
                    ->label('Relationship')
                    ->badge(),
                IconColumn::make('pivot.is_primary')
                    ->label('Primary')
                    ->boolean(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(fn (array $data, Guardian $record) => $this->syncPrimaryGuardian($data, $record)),
                AttachAction::make()
                    ->recordSelectSearchColumns(['first_name', 'last_name', 'email'])
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query->orderBy('last_name')->orderBy('first_name'))
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        ...static::pivotFields(),
                    ])
                    ->after(fn (array $data) => $this->syncPrimaryGuardian($data, Guardian::findOrFail($data['recordId']))),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn (array $data, Guardian $record) => $this->syncPrimaryGuardian($data, $record)),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<int, Select|Toggle>
     */
    public static function pivotFields(): array
    {
        return [
            Select::make('relationship')
                ->options(GuardianRelationship::class)
                ->required(),
            Toggle::make('is_primary')
                ->label('Primary guardian'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncPrimaryGuardian(array $data, Guardian $guardian): void
    {
        if (! ($data['is_primary'] ?? false)) {
            return;
        }

        /** @var Student $student */
        $student = $this->getOwnerRecord();

        $student->makePrimaryGuardian($guardian);
    }
}
