<?php

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Enums\Weekday;
use App\Filament\Admin\Resources\Sections\Pages\CreateSection;
use App\Filament\Admin\Resources\Sections\Pages\EditSection;
use App\Filament\Admin\Resources\Sections\Pages\ListSections;
use App\Filament\Admin\Resources\Sections\RelationManagers\CoursesRelationManager;
use App\Filament\Admin\Resources\Sections\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Admin\Resources\Sections\RelationManagers\ScheduleSlotsRelationManager;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Period;
use App\Models\ScheduleSlot;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole(UserRole::SuperAdmin));

    $this->academicYear = AcademicYear::factory()->current()->create(['name' => '2026-2027']);
});

it('lists sections of the current academic year by default', function () {
    $current = Section::factory()->for($this->academicYear)->create();
    $previous = Section::factory()->for(AcademicYear::factory()->create(['name' => '2025-2026']))->create();

    Livewire::test(ListSections::class)
        ->assertCanSeeTableRecords([$current])
        ->assertCanNotSeeTableRecords([$previous]);
});

it('shows how many seats are taken', function () {
    $section = Section::factory()->for($this->academicYear)->create(['capacity' => 25]);
    Enrollment::factory()->count(2)->for($section)->create();
    Enrollment::factory()->for($section)->create(['status' => EnrollmentStatus::Withdrawn]);

    Livewire::test(ListSections::class)
        ->assertTableColumnFormattedStateSet('active_enrollments_count', '2 / 25', $section);
});

it('creates a section', function () {
    $gradeLevel = GradeLevel::factory()->create();
    $teacher = Teacher::factory()->create();

    Livewire::test(CreateSection::class)
        ->assertSchemaStateSet(['academic_year_id' => $this->academicYear->id])
        ->fillForm([
            'grade_level_id' => $gradeLevel->id,
            'name' => 'A',
            'capacity' => 25,
            'homeroom_teacher_id' => $teacher->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Section::query()->sole())
        ->academic_year_id->toBe($this->academicYear->id)
        ->homeroom_teacher_id->toBe($teacher->id);
});

it('rejects duplicated sections for the same grade and academic year', function () {
    $existing = Section::factory()->for($this->academicYear)->create(['name' => 'A']);

    Livewire::test(CreateSection::class)
        ->fillForm([
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $existing->grade_level_id,
            'name' => 'A',
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});

it('does not allow a capacity below the enrolled students', function () {
    $section = Section::factory()->for($this->academicYear)->create(['capacity' => 25]);
    Enrollment::factory()->count(3)->for($section)->create();

    Livewire::test(EditSection::class, ['record' => $section->getRouteKey()])
        ->fillForm(['capacity' => 2])
        ->call('save')
        ->assertHasFormErrors(['capacity' => 'min']);
});

it('only allows deleting sections without enrollments', function () {
    $section = Section::factory()->for($this->academicYear)->create();

    Livewire::test(EditSection::class, ['record' => $section->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect($section->exists())->toBeFalse();

    $enrolled = Enrollment::factory()->create()->section;

    Livewire::test(EditSection::class, ['record' => $enrolled->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});

it('enrolls a student from the section', function () {
    $section = Section::factory()->for($this->academicYear)->create();
    $student = Student::factory()->create();

    Livewire::test(EnrollmentsRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditSection::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'student_id' => $student->id,
            'enrolled_on' => '2026-08-20',
        ])
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($section->enrollments()->sole())
        ->student_id->toBe($student->id)
        ->academic_year_id->toBe($this->academicYear->id);
});

it('notifies when the section is full', function () {
    $section = Section::factory()->for($this->academicYear)->create(['capacity' => 1]);
    Enrollment::factory()->for($section)->create();

    Livewire::test(EnrollmentsRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditSection::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'student_id' => Student::factory()->create()->id,
            'enrolled_on' => '2026-08-20',
        ])
        ->assertNotified('The student could not be enrolled');

    expect($section->enrollments()->count())->toBe(1);
});

it('changes the status of an enrollment', function () {
    $section = Section::factory()->for($this->academicYear)->create();
    $enrollment = Enrollment::factory()->for($section)->create();

    Livewire::test(EnrollmentsRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditSection::class])
        ->callAction(TestAction::make(EditAction::class)->table($enrollment), data: [
            'status' => EnrollmentStatus::Withdrawn,
        ])
        ->assertHasNoFormErrors();

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Withdrawn);
});

it('assigns a subject and teacher to the section', function () {
    $section = Section::factory()->for($this->academicYear)->create();
    $subject = Subject::factory()->create();
    $teacher = Teacher::factory()->create();

    Livewire::test(CoursesRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditSection::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ])
        ->assertHasNoFormErrors();

    expect($section->courses()->sole())
        ->subject_id->toBe($subject->id)
        ->teacher_id->toBe($teacher->id);
});

it('does not assign the same subject twice to a section', function () {
    $course = Course::factory()->for(Section::factory()->for($this->academicYear))->create();

    Livewire::test(CoursesRelationManager::class, ['ownerRecord' => $course->section, 'pageClass' => EditSection::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'subject_id' => $course->subject_id,
        ])
        ->assertHasFormErrors(['subject_id' => 'unique']);
});

it('lists the section timetable', function () {
    $course = Course::factory()->for(Section::factory()->for($this->academicYear))->create();
    $scheduleSlot = ScheduleSlot::factory()->for($course)->create();
    $otherSectionSlot = ScheduleSlot::factory()->create();

    Livewire::test(ScheduleSlotsRelationManager::class, ['ownerRecord' => $course->section, 'pageClass' => EditSection::class])
        ->assertCanSeeTableRecords([$scheduleSlot])
        ->assertCanNotSeeTableRecords([$otherSectionSlot]);
});

it('adds a class to the section timetable', function () {
    $course = Course::factory()->for(Section::factory()->for($this->academicYear))->create();
    $period = Period::factory()->create();

    Livewire::test(ScheduleSlotsRelationManager::class, ['ownerRecord' => $course->section, 'pageClass' => EditSection::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'course_id' => $course->id,
            'day_of_week' => Weekday::Monday->value,
            'period_id' => $period->id,
        ])
        ->assertHasNoFormErrors();

    expect($course->scheduleSlots()->sole())
        ->day_of_week->toBe(Weekday::Monday)
        ->period_id->toBe($period->id)
        ->classroom_id->toBeNull();
});

it('notifies when a class clashes with the timetable', function () {
    $scheduleSlot = ScheduleSlot::factory()
        ->for(Course::factory()->for(Section::factory()->for($this->academicYear)))
        ->create(['day_of_week' => Weekday::Monday]);
    $course = Course::factory()->for($scheduleSlot->course->section)->create();

    Livewire::test(ScheduleSlotsRelationManager::class, ['ownerRecord' => $course->section, 'pageClass' => EditSection::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'course_id' => $course->id,
            'day_of_week' => Weekday::Monday->value,
            'period_id' => $scheduleSlot->period_id,
        ])
        ->assertNotified('The class could not be scheduled');

    expect($course->scheduleSlots()->count())->toBe(0);
});

it('moves a class to another period', function () {
    $scheduleSlot = ScheduleSlot::factory()
        ->for(Course::factory()->for(Section::factory()->for($this->academicYear)))
        ->create(['day_of_week' => Weekday::Monday]);
    $period = Period::factory()->create();

    Livewire::test(ScheduleSlotsRelationManager::class, ['ownerRecord' => $scheduleSlot->course->section, 'pageClass' => EditSection::class])
        ->callAction(TestAction::make(EditAction::class)->table($scheduleSlot), data: [
            'day_of_week' => Weekday::Friday->value,
            'period_id' => $period->id,
        ])
        ->assertHasNoFormErrors();

    expect($scheduleSlot->fresh())
        ->day_of_week->toBe(Weekday::Friday)
        ->period_id->toBe($period->id);
});

it('only offers school days and the section courses', function () {
    $course = Course::factory()->for(Section::factory()->for($this->academicYear))->create();
    $otherSectionCourse = Course::factory()->create();

    Livewire::test(ScheduleSlotsRelationManager::class, ['ownerRecord' => $course->section, 'pageClass' => EditSection::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'course_id' => $otherSectionCourse->id,
            'day_of_week' => Weekday::Saturday->value,
            'period_id' => Period::factory()->create()->id,
        ])
        ->assertHasFormErrors(['course_id' => 'in', 'day_of_week' => 'in']);
});
