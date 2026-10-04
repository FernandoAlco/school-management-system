<?php

namespace App\Filament\Teacher\Resources\Courses\Tables;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'subject',
                'section' => fn (BelongsTo $query): BelongsTo => $query
                    ->with(['gradeLevel', 'academicYear'])
                    ->withCount(['enrollments as active_enrollments_count' => fn (Builder $query): Builder => $query->where('status', EnrollmentStatus::Active)]),
            ]))
            ->columns([
                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable(),
                TextColumn::make('section.gradeLevel.name')
                    ->label('Grade'),
                TextColumn::make('section.name')
                    ->label('Section'),
                TextColumn::make('section.active_enrollments_count')
                    ->label('Students'),
                TextColumn::make('section.academicYear.name')
                    ->label('Academic year'),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy(GradeLevel::query()
                    ->select('grade_levels.sort_order')
                    ->join('sections', 'sections.grade_level_id', '=', 'grade_levels.id')
                    ->whereColumn('sections.id', 'courses.section_id'))
                ->orderBy(Section::query()->select('name')->whereColumn('sections.id', 'courses.section_id'))
                ->orderBy(Subject::query()->select('name')->whereColumn('subjects.id', 'courses.subject_id')))
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label('Academic year')
                    ->options(fn (): array => AcademicYear::query()->orderByDesc('starts_on')->pluck('name', 'id')->all())
                    ->default(fn (): ?int => AcademicYear::query()->current()->value('id'))
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'],
                        fn (Builder $query, int|string $academicYearId): Builder => $query->whereHas('section', fn (Builder $query): Builder => $query->where('academic_year_id', $academicYearId)),
                    )),
            ]);
    }
}
