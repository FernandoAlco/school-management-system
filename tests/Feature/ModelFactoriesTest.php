<?php

use App\Enums\GuardianRelationship;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a course with its section, subject and teacher', function () {
    $course = Course::factory()->create();

    expect($course->section->academicYear)->toBeInstanceOf(AcademicYear::class)
        ->and($course->subject->name)->not->toBeEmpty()
        ->and($course->teacher->courses)->toHaveCount(1);
});

it('derives the enrollment academic year from its section', function () {
    $section = Section::factory()->create();

    $enrollment = Enrollment::factory()->for($section)->create();

    expect($enrollment->academic_year_id)->toBe($section->academic_year_id)
        ->and($section->students->first()->is($enrollment->student))->toBeTrue();
});

it('prevents enrolling a student twice in the same academic year', function () {
    $academicYear = AcademicYear::factory()->create();
    $student = Student::factory()->create();

    Enrollment::factory()
        ->for($student)
        ->for(Section::factory()->for($academicYear))
        ->create();

    Enrollment::factory()
        ->for($student)
        ->for(Section::factory()->for($academicYear))
        ->create();
})->throws(QueryException::class);

it('links guardians and students with a casted pivot', function () {
    $student = Student::factory()->create();
    $guardian = Guardian::factory()->withUser()->create();

    $student->guardians()->attach($guardian, [
        'relationship' => GuardianRelationship::Mother,
        'is_primary' => true,
    ]);

    $pivot = $student->guardians()->first()->pivot;

    expect($pivot->relationship)->toBe(GuardianRelationship::Mother)
        ->and($pivot->is_primary)->toBeTrue()
        ->and($guardian->user->email)->toBe($guardian->email);
});

it('creates profiles with user accounts and enum casts', function () {
    $teacher = Teacher::factory()->withUser()->create();
    $student = Student::factory()->withdrawn()->create();

    expect($teacher->user->teacher->is($teacher))->toBeTrue()
        ->and($teacher->full_name)->toBe("{$teacher->first_name} {$teacher->last_name}")
        ->and($student->status)->toBe(StudentStatus::Withdrawn);
});

it('orders terms within an academic year', function () {
    $academicYear = AcademicYear::factory()->current()->create();

    Term::factory()->for($academicYear)->create(['name' => 'Term 2', 'sort_order' => 2]);
    Term::factory()->for($academicYear)->create(['name' => 'Term 1', 'sort_order' => 1]);

    expect(AcademicYear::current()->sole()->terms->pluck('name')->all())
        ->toBe(['Term 1', 'Term 2']);
});
