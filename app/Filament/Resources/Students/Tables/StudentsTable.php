<?php

namespace App\Filament\Resources\Students\Tables;

use App\Enums\StudentStatus;
use App\Models\GradeLevel;
use App\Models\Student;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('student_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name', 'first_name']),
                TextColumn::make('birth_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('gender')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('national_id')
                    ->label('National ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('address')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge(),
                IconColumn::make('has_account')
                    ->label('Account')
                    ->state(fn (Student $record): bool => $record->user_id !== null)
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_name')
            ->filters([
                SelectFilter::make('status')
                    ->options(StudentStatus::class),
                SelectFilter::make('current_grade_level')
                    ->label('Current grade')
                    ->options(fn (): array => GradeLevel::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'],
                        fn (Builder $query, string $gradeLevelId): Builder => $query->whereHas('enrollments', fn (Builder $query): Builder => $query
                            ->whereHas('academicYear', fn (Builder $query): Builder => $query->where('is_current', true))
                            ->whereHas('section', fn (Builder $query): Builder => $query->where('grade_level_id', $gradeLevelId))),
                    )),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
