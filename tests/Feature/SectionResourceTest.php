<?php

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Sections\Pages\CreateSection;
use App\Filament\Resources\Sections\Pages\EditSection;
use App\Filament\Resources\Sections\Pages\ListSections;
use App\Filament\Resources\Sections\RelationManagers\CoursesRelationManager;
use App\Filament\Resources\Sections\RelationManagers\EnrollmentsRelationManager;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\GradeLevel;
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
    $this->actingAs(User::factory()->create()->assignRole(UserRole::Admin));

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
