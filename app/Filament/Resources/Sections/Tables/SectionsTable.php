<?php

namespace App\Filament\Resources\Sections\Tables;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Section;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Academic year')
                    ->sortable(),
                TextColumn::make('gradeLevel.name')
                    ->label('Grade'),
                TextColumn::make('name')
                    ->label('Section')
                    ->searchable(),
                TextColumn::make('homeroomTeacher.full_name')
                    ->label('Homeroom teacher')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('classroom.name')
                    ->label('Classroom'),
                TextColumn::make('active_enrollments_count')
                    ->label('Students')
                    ->counts(['enrollments as active_enrollments_count' => fn (Builder $query): Builder => $query->where('status', EnrollmentStatus::Active)])
                    ->formatStateUsing(fn (int $state, Section $record): string => $record->capacity === null ? (string) $state : "{$state} / {$record->capacity}"),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy(GradeLevel::query()->select('sort_order')->whereColumn('grade_levels.id', 'sections.grade_level_id'))
                ->orderBy('name'))
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label('Academic year')
                    ->relationship('academicYear', 'name', fn (Builder $query): Builder => $query->orderByDesc('starts_on'))
                    ->default(fn (): ?int => AcademicYear::query()->current()->value('id')),
                SelectFilter::make('grade_level_id')
                    ->label('Grade')
                    ->relationship('gradeLevel', 'name', fn (Builder $query): Builder => $query->orderBy('sort_order')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
