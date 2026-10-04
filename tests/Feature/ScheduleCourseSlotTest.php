<?php

use App\Actions\ScheduleCourseSlot;
use App\Enums\Weekday;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Period;
use App\Models\ScheduleSlot;
use App\Models\Section;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->academicYear = AcademicYear::factory()->current()->create();
    $this->period = Period::factory()->create(['name' => '1st period']);
    $this->teacher = Teacher::factory()->create(['first_name' => 'Ana', 'last_name' => 'García']);
    $this->section = Section::factory()->for($this->academicYear)->for(Classroom::factory()->state(['name' => 'Room 101']))->create();
    $this->course = Course::factory()->for($this->section)->for($this->teacher)->create();
});

/**
 * A course in another section of the current academic year, with its own classroom and teacher.
 */
function otherCourse(array $attributes = [], ?AcademicYear $academicYear = null): Course
{
    return Course::factory()
        ->for(Section::factory()->for($academicYear ?? test()->academicYear)->for(Classroom::factory()))
        ->create($attributes);
}

it('schedules a course in the section classroom', function () {
    $scheduleSlot = app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Monday, $this->period);

    expect($scheduleSlot)
        ->course_id->toBe($this->course->id)
        ->day_of_week->toBe(Weekday::Monday)
        ->period_id->toBe($this->period->id)
        ->classroom_id->toBeNull()
        ->and($scheduleSlot->effectiveClassroom()->is($this->section->classroom))->toBeTrue();
});

it('schedules a course in another classroom', function () {
    $lab = Classroom::factory()->create();

    $scheduleSlot = app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Monday, $this->period, $lab);

    expect($scheduleSlot->effectiveClassroom()->is($lab))->toBeTrue();
});

it('rejects days without classes', function (Weekday $day) {
    app(ScheduleCourseSlot::class)->handle($this->course, $day, $this->period);
})->with([Weekday::Saturday, Weekday::Sunday])->throws(ValidationException::class, 'is not a school day.');

it('rejects a second class of the section in the same day and period', function () {
    ScheduleSlot::factory()
        ->for(Course::factory()->for($this->section)->for(Teacher::factory()))
        ->for($this->period)
        ->create(['day_of_week' => Weekday::Monday]);

    app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Monday, $this->period);
})->throws(ValidationException::class, 'The section already has');

it('rejects a teacher teaching two sections in the same day and period', function () {
    ScheduleSlot::factory()
        ->for(otherCourse(['teacher_id' => $this->teacher->id]))
        ->for($this->period)
        ->create(['day_of_week' => Weekday::Monday]);

    app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Monday, $this->period);
})->throws(ValidationException::class, 'Ana García already teaches');

it('rejects a classroom used twice in the same day and period', function () {
    ScheduleSlot::factory()
        ->for(otherCourse())
        ->for($this->period)
        ->for($this->section->classroom)
        ->create(['day_of_week' => Weekday::Monday]);

    app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Monday, $this->period);
})->throws(ValidationException::class, 'Room 101 is already used by');

it('rejects a classroom that another section uses as its own classroom', function () {
    $otherCourse = otherCourse();
    ScheduleSlot::factory()->for($otherCourse)->for($this->period)->create(['day_of_week' => Weekday::Monday]);

    app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Monday, $this->period, $otherCourse->section->classroom);
})->throws(ValidationException::class, 'is already used by');

it('allows the same section, teacher and classroom on another day or period', function () {
    ScheduleSlot::factory()
        ->for(otherCourse(['teacher_id' => $this->teacher->id]))
        ->for($this->period)
        ->for($this->section->classroom)
        ->create(['day_of_week' => Weekday::Monday]);

    app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Tuesday, $this->period);
    app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Monday, Period::factory()->create());

    expect($this->course->scheduleSlots()->count())->toBe(2);
});

it('ignores clashes with other academic years', function () {
    ScheduleSlot::factory()
        ->for(otherCourse(['teacher_id' => $this->teacher->id], AcademicYear::factory()->create()))
        ->for($this->period)
        ->for($this->section->classroom)
        ->create(['day_of_week' => Weekday::Monday]);

    $scheduleSlot = app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Monday, $this->period);

    expect($scheduleSlot->exists)->toBeTrue();
});

it('allows two unassigned courses at the same time in different sections', function () {
    $this->course->update(['teacher_id' => null]);
    ScheduleSlot::factory()
        ->for(otherCourse(['teacher_id' => null]))
        ->for($this->period)
        ->create(['day_of_week' => Weekday::Monday]);

    $scheduleSlot = app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Monday, $this->period);

    expect($scheduleSlot->exists)->toBeTrue();
});

it('updates a slot without clashing with itself', function () {
    $scheduleSlot = app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Monday, $this->period);
    $lab = Classroom::factory()->create();

    app(ScheduleCourseSlot::class)->handle($this->course, Weekday::Monday, $this->period, $lab, $scheduleSlot);

    expect($scheduleSlot->fresh()->classroom_id)->toBe($lab->id)
        ->and(ScheduleSlot::query()->count())->toBe(1);
});
