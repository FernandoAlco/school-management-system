<?php

namespace App\Filament\Admin\Resources\Sections\RelationManagers;

use App\Actions\EnrollStudent;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Student')
                    ->relationship('student', 'last_name', fn (Builder $query): Builder => $query
                        ->where('status', StudentStatus::Active)
                        ->whereDoesntHave('enrollments', fn (Builder $query): Builder => $query->where('academic_year_id', $this->getOwnerRecord()->academic_year_id))
                        ->orderBy('last_name')
                        ->orderBy('first_name'))
                    ->getOptionLabelFromRecordUsing(fn (Student $record): string => "{$record->full_name} ({$record->student_number})")
                    ->searchable(['first_name', 'last_name', 'student_number'])
                    ->required()
                    ->visibleOn('create'),
                DatePicker::make('enrolled_on')
                    ->default(today())
                    ->required(),
                Select::make('status')
                    ->options(EnrollmentStatus::class)
                    ->required()
                    ->hiddenOn('create'),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var Section $section */
        $section = $this->getOwnerRecord();

        return $table
            ->recordTitle(fn (Enrollment $record): string => $record->student->full_name)
            ->description(fn (): string => $section->capacity === null
                ? "{$section->enrollments()->where('status', EnrollmentStatus::Active)->count()} active students"
                : "{$section->enrollments()->where('status', EnrollmentStatus::Active)->count()} / {$section->capacity} seats taken")
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('student'))
            ->columns([
                TextColumn::make('student.student_number')
                    ->label('Student number')
                    ->searchable(),
                TextColumn::make('student.full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('enrolled_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy(Student::query()->select('last_name')->whereColumn('students.id', 'enrollments.student_id'))
                ->orderBy(Student::query()->select('first_name')->whereColumn('students.id', 'enrollments.student_id')))
            ->filters([
                SelectFilter::make('status')
                    ->options(EnrollmentStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Enroll student')
                    ->modalHeading('Enroll student')
                    ->using(function (array $data, CreateAction $action) use ($section): Enrollment {
                        try {
                            return app(EnrollStudent::class)->handle(
                                Student::findOrFail($data['student_id']),
                                $section,
                                Carbon::parse($data['enrolled_on']),
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('The student could not be enrolled')
                                ->body($exception->getMessage())
                                ->send();

                            $action->halt();
                        }
                    }),
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
