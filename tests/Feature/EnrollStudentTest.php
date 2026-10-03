<?php

use App\Actions\EnrollStudent;
use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('enrolls an active student in the section academic year', function () {
    $section = Section::factory()->create();
    $student = Student::factory()->create();

    $enrollment = app(EnrollStudent::class)->handle($student, $section, Carbon::parse('2026-08-20'));

    expect($enrollment)
        ->student_id->toBe($student->id)
        ->section_id->toBe($section->id)
        ->academic_year_id->toBe($section->academic_year_id)
        ->status->toBe(EnrollmentStatus::Active)
        ->and($enrollment->enrolled_on->toDateString())->toBe('2026-08-20');
});

it('rejects students that are not active', function () {
    app(EnrollStudent::class)->handle(Student::factory()->withdrawn()->create(), Section::factory()->create());
})->throws(ValidationException::class, 'Only active students can be enrolled.');

it('rejects a second enrollment in the same academic year', function () {
    $academicYear = AcademicYear::factory()->create();
    $student = Student::factory()->create();
    Enrollment::factory()->for($student)->for(Section::factory()->for($academicYear))->create();

    app(EnrollStudent::class)->handle($student, Section::factory()->for($academicYear)->create());
})->throws(ValidationException::class, 'The student is already enrolled in this academic year.');

it('allows enrolling the same student in a different academic year', function () {
    $student = Student::factory()->create();
    Enrollment::factory()->for($student)->create();

    $enrollment = app(EnrollStudent::class)->handle($student, Section::factory()->create());

    expect($student->enrollments()->count())->toBe(2)
        ->and($enrollment->exists)->toBeTrue();
});

it('rejects enrollments when the section is full', function () {
    $section = Section::factory()->create(['capacity' => 1]);
    Enrollment::factory()->for($section)->create();

    app(EnrollStudent::class)->handle(Student::factory()->create(), $section);
})->throws(ValidationException::class, 'The section has reached its capacity.');

it('does not count withdrawn students against the capacity', function () {
    $section = Section::factory()->create(['capacity' => 1]);
    Enrollment::factory()->for($section)->create(['status' => EnrollmentStatus::Withdrawn]);

    $enrollment = app(EnrollStudent::class)->handle(Student::factory()->create(), $section);

    expect($enrollment->exists)->toBeTrue();
});
