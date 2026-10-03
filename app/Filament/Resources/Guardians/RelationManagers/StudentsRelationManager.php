<?php

namespace App\Filament\Resources\Guardians\RelationManagers;

use App\Filament\Resources\Students\RelationManagers\GuardiansRelationManager;
use App\Models\Guardian;
use App\Models\Student;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(GuardiansRelationManager::pivotFields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (Student $record): string => $record->full_name)
            ->columns([
                TextColumn::make('student_number')
                    ->searchable(),
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('pivot.relationship')
                    ->label('Relationship')
                    ->badge(),
                IconColumn::make('pivot.is_primary')
                    ->label('Primary')
                    ->boolean(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectSearchColumns(['first_name', 'last_name', 'student_number'])
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query->orderBy('last_name')->orderBy('first_name'))
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        ...GuardiansRelationManager::pivotFields(),
                    ])
                    ->after(fn (array $data) => $this->syncPrimaryGuardian($data, Student::findOrFail($data['recordId']))),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn (array $data, Student $record) => $this->syncPrimaryGuardian($data, $record)),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncPrimaryGuardian(array $data, Student $student): void
    {
        if (! ($data['is_primary'] ?? false)) {
            return;
        }

        /** @var Guardian $guardian */
        $guardian = $this->getOwnerRecord();

        $student->makePrimaryGuardian($guardian);
    }
}
