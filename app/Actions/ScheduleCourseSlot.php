<?php

namespace App\Actions;

use App\Enums\Weekday;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Period;
use App\Models\ScheduleSlot;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleCourseSlot
{
    /**
     * Create or update a timetable slot for a course, rejecting non-school days and any
     * clash with the course's section, its teacher or the classroom on the same day and period.
     *
     * @param  Classroom|null  $classroom  null means the class takes place in the section's classroom
     *
     * @throws ValidationException
     */
    public function handle(
        Course $course,
        Weekday $dayOfWeek,
        Period $period,
        ?Classroom $classroom = null,
        ?ScheduleSlot $scheduleSlot = null,
    ): ScheduleSlot {
        return DB::transaction(function () use ($course, $dayOfWeek, $period, $classroom, $scheduleSlot): ScheduleSlot {
            $section = Section::query()->lockForUpdate()->findOrFail($course->section_id);

            if (! in_array($dayOfWeek, Weekday::schoolDays(), true)) {
                throw ValidationException::withMessages([
                    'day_of_week' => "{$dayOfWeek->getLabel()} is not a school day.",
                ]);
            }

            $sectionClash = $this->slotsAt($dayOfWeek, $period, $scheduleSlot)
                ->whereHas('course', fn (Builder $query): Builder => $query->where('section_id', $section->getKey()))
                ->first();

            if ($sectionClash !== null) {
                throw ValidationException::withMessages([
                    'period_id' => "The section already has {$sectionClash->course->subject->name} on {$dayOfWeek->getLabel()}, {$period->name}.",
                ]);
            }

            if ($course->teacher_id !== null) {
                $teacherClash = $this->slotsAt($dayOfWeek, $period, $scheduleSlot)
                    ->whereHas('course', fn (Builder $query): Builder => $query
                        ->where('teacher_id', $course->teacher_id)
                        ->whereHas('section', fn (Builder $query): Builder => $query->where('academic_year_id', $section->academic_year_id)))
                    ->first();

                if ($teacherClash !== null) {
                    throw ValidationException::withMessages([
                        'period_id' => "{$course->teacher->full_name} already teaches {$teacherClash->course->section->getLabel()} on {$dayOfWeek->getLabel()}, {$period->name}.",
                    ]);
                }
            }

            $classroomId = $classroom?->getKey() ?? $section->classroom_id;

            if ($classroomId !== null) {
                $classroomClash = $this->slotsAt($dayOfWeek, $period, $scheduleSlot)
                    ->whereHas('course.section', fn (Builder $query): Builder => $query->where('academic_year_id', $section->academic_year_id))
                    ->where(fn (Builder $query): Builder => $query
                        ->where('classroom_id', $classroomId)
                        ->orWhere(fn (Builder $query): Builder => $query
                            ->whereNull('classroom_id')
                            ->whereHas('course.section', fn (Builder $query): Builder => $query->where('classroom_id', $classroomId))))
                    ->first();

                if ($classroomClash !== null) {
                    throw ValidationException::withMessages([
                        'classroom_id' => "{$classroomClash->effectiveClassroom()->name} is already used by {$classroomClash->course->section->getLabel()} on {$dayOfWeek->getLabel()}, {$period->name}.",
                    ]);
                }
            }

            $scheduleSlot ??= new ScheduleSlot;

            $scheduleSlot->fill([
                'course_id' => $course->getKey(),
                'day_of_week' => $dayOfWeek,
                'period_id' => $period->getKey(),
                'classroom_id' => $classroom?->getKey(),
            ])->save();

            return $scheduleSlot;
        });
    }

    /**
     * Slots on the given day and period, other than the one being updated.
     *
     * @return Builder<ScheduleSlot>
     */
    private function slotsAt(Weekday $dayOfWeek, Period $period, ?ScheduleSlot $ignoredSlot): Builder
    {
        return ScheduleSlot::query()
            ->with(['course.subject', 'course.section.gradeLevel', 'course.section.academicYear', 'course.section.classroom', 'classroom'])
            ->where('day_of_week', $dayOfWeek)
            ->where('period_id', $period->getKey())
            ->when($ignoredSlot?->exists, fn (Builder $query): Builder => $query->whereKeyNot($ignoredSlot->getKey()));
    }
}
