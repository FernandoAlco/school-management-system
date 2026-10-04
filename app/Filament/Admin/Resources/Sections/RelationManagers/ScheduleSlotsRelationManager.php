<?php

namespace App\Filament\Admin\Resources\Sections\RelationManagers;

use App\Actions\ScheduleCourseSlot;
use App\Enums\Weekday;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Period;
use App\Models\ScheduleSlot;
use App\Models\Section;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ScheduleSlotsRelationManager extends RelationManager
{
    protected static string $relationship = 'scheduleSlots';

    protected static ?string $title = 'Timetable';

    public function form(Schema $schema): Schema
    {
        /** @var Section $section */
        $section = $this->getOwnerRecord();

        return $schema
            ->components([
                Select::make('course_id')
                    ->label('Subject')
                    ->options(fn (): array => $section->courses()
                        ->with(['subject', 'teacher'])
                        ->get()
                        ->sortBy('subject.name')
                        ->mapWithKeys(fn (Course $course): array => [
                            $course->id => $course->teacher === null
                                ? $course->subject->name
                                : "{$course->subject->name} ({$course->teacher->full_name})",
                        ])
                        ->all())
                    ->required(),
                Select::make('day_of_week')
                    ->label('Day')
                    ->options(collect(Weekday::schoolDays())->mapWithKeys(fn (Weekday $day): array => [$day->value => $day->getLabel()]))
                    ->required(),
                Select::make('period_id')
                    ->label('Period')
                    ->options(fn (): array => Period::query()
                        ->orderBy('starts_at')
                        ->get()
                        ->mapWithKeys(fn (Period $period): array => [$period->id => $period->getLabel()])
                        ->all())
                    ->required(),
                Select::make('classroom_id')
                    ->label('Classroom')
                    ->options(fn (): array => Classroom::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->placeholder($section->classroom === null ? 'No classroom' : "Section classroom ({$section->classroom->name})")
                    ->helperText('Only needed when the class takes place outside the section classroom.')
                    ->searchable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (ScheduleSlot $record): string => "{$record->course->subject->name} on {$record->day_of_week->getLabel()}, {$record->period->name}")
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['course.subject', 'course.teacher', 'course.section.classroom', 'period', 'classroom']))
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('Day'),
                TextColumn::make('period.name')
                    ->label('Period')
                    ->description(fn (ScheduleSlot $record): string => $record->period->time_range),
                TextColumn::make('course.subject.name')
                    ->label('Subject'),
                TextColumn::make('course.teacher.full_name')
                    ->label('Teacher')
                    ->placeholder('Unassigned'),
                TextColumn::make('classroom')
                    ->label('Classroom')
                    ->state(fn (ScheduleSlot $record): ?string => $record->effectiveClassroom()?->name)
                    ->placeholder('No classroom'),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy('day_of_week')
                ->orderBy(Period::query()->select('starts_at')->whereColumn('periods.id', 'schedule_slots.period_id')))
            ->filters([
                SelectFilter::make('day_of_week')
                    ->label('Day')
                    ->options(collect(Weekday::schoolDays())->mapWithKeys(fn (Weekday $day): array => [$day->value => $day->getLabel()])),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add class')
                    ->modalHeading('Add class')
                    ->using(fn (array $data, CreateAction $action): ScheduleSlot => $this->scheduleSlot($data, $action)),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(fn (ScheduleSlot $record, array $data, EditAction $action): ScheduleSlot => $this->scheduleSlot($data, $action, $record)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @param  array{course_id: int|string, day_of_week: int|string, period_id: int|string, classroom_id: int|string|null}  $data
     */
    private function scheduleSlot(array $data, Action $action, ?ScheduleSlot $scheduleSlot = null): ScheduleSlot
    {
        /** @var Section $section */
        $section = $this->getOwnerRecord();

        try {
            return app(ScheduleCourseSlot::class)->handle(
                $section->courses()->findOrFail($data['course_id']),
                Weekday::from((int) $data['day_of_week']),
                Period::findOrFail($data['period_id']),
                filled($data['classroom_id']) ? Classroom::findOrFail($data['classroom_id']) : null,
                $scheduleSlot,
            );
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('The class could not be scheduled')
                ->body($exception->getMessage())
                ->send();

            $action->halt();
        }
    }
}
