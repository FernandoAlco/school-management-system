<?php

use App\Enums\Gender;
use App\Enums\GuardianRelationship;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\RelationManagers\GuardiansRelationManager;
use App\Filament\Resources\Students\StudentResource;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole(UserRole::SuperAdmin));
});

it('lists students sorted by last name', function () {
    $students = collect(['Young', 'Adams', 'Miller'])->map(fn (string $lastName): Student => Student::factory()->create(['last_name' => $lastName]));

    Livewire::test(ListStudents::class)
        ->assertCanSeeTableRecords($students->sortBy('last_name'), inOrder: true);
});

it('searches students by name and student number', function () {
    $match = Student::factory()->create(['last_name' => 'Lovelace', 'student_number' => 'A00000001']);
    $other = Student::factory()->create(['last_name' => 'Turing']);

    Livewire::test(ListStudents::class)
        ->searchTable('Lovelace')
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$other])
        ->searchTable('A00000001')
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$other]);

    expect(StudentResource::getGlobalSearchResults('Lovelace'))->toHaveCount(1);
});

it('filters students by their grade in the current academic year', function () {
    $currentYear = AcademicYear::factory()->current()->create(['name' => '2026-2027']);
    $previousYear = AcademicYear::factory()->create(['name' => '2025-2026']);
    $gradeOne = GradeLevel::factory()->create(['name' => 'Grade 1', 'sort_order' => 1]);
    $gradeTwo = GradeLevel::factory()->create(['name' => 'Grade 2', 'sort_order' => 2]);

    $inGradeTwo = Enrollment::factory()->for(Section::factory()->for($currentYear)->for($gradeTwo))->create()->student;
    $inGradeOne = Enrollment::factory()->for(Section::factory()->for($currentYear)->for($gradeOne))->create()->student;
    $wasInGradeTwo = Enrollment::factory()->for(Section::factory()->for($previousYear)->for($gradeTwo))->create()->student;

    Livewire::test(ListStudents::class)
        ->filterTable('current_grade_level', $gradeTwo->id)
        ->assertCanSeeTableRecords([$inGradeTwo])
        ->assertCanNotSeeTableRecords([$inGradeOne, $wasInGradeTwo]);
});

it('creates a student', function () {
    Livewire::test(CreateStudent::class)
        ->fillForm([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'birth_date' => '2018-12-10',
            'gender' => Gender::Female,
            'student_number' => 'A00000001',
            'status' => StudentStatus::Active,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Student::query()->where('student_number', 'A00000001')->sole())
        ->full_name->toBe('Ada Lovelace')
        ->gender->toBe(Gender::Female);
});

it('validates the student form', function (array $data, array $errors) {
    Student::factory()->create(['student_number' => 'A00000001', 'national_id' => 'NID-1']);

    Livewire::test(CreateStudent::class)
        ->fillForm([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'birth_date' => '2018-12-10',
            'student_number' => 'A00000002',
            'status' => StudentStatus::Active,
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'missing birth date' => [['birth_date' => null], ['birth_date' => 'required']],
    'birth date in the future' => [['birth_date' => now()->addDay()->toDateString()], ['birth_date' => 'before_or_equal']],
    'duplicated student number' => [['student_number' => 'A00000001'], ['student_number' => 'unique']],
    'duplicated national id' => [['national_id' => 'NID-1'], ['national_id' => 'unique']],
]);

it('only allows force deleting students without enrollments', function () {
    $student = Student::factory()->create();
    $student->delete();

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->callAction(ForceDeleteAction::class);

    expect(Student::withTrashed()->find($student->id))->toBeNull();

    $enrolled = Enrollment::factory()->create()->student;
    $enrolled->delete();

    Livewire::test(EditStudent::class, ['record' => $enrolled->getRouteKey()])
        ->assertActionHidden(ForceDeleteAction::class);
});

it('attaches an existing guardian with the relationship', function () {
    $student = Student::factory()->create();
    $guardian = Guardian::factory()->create();

    Livewire::test(GuardiansRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
        ->callAction(TestAction::make(AttachAction::class)->table(), data: [
            'recordId' => $guardian->id,
            'relationship' => GuardianRelationship::Father,
            'is_primary' => false,
        ])
        ->assertHasNoFormErrors();

    $pivot = $student->guardians()->sole()->pivot;

    expect($pivot->relationship)->toBe(GuardianRelationship::Father)
        ->and($pivot->is_primary)->toBeFalse();
});

it('creates a new guardian for the student', function () {
    $student = Student::factory()->create();

    Livewire::test(GuardiansRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'first_name' => 'Laura',
            'last_name' => 'Bennett',
            'email' => 'laura@example.test',
            'relationship' => GuardianRelationship::Mother,
            'is_primary' => true,
        ])
        ->assertHasNoFormErrors();

    $guardian = $student->guardians()->sole();

    expect($guardian->full_name)->toBe('Laura Bennett')
        ->and($guardian->pivot->relationship)->toBe(GuardianRelationship::Mother)
        ->and($guardian->pivot->is_primary)->toBeTrue();
});

it('keeps a single primary guardian per student', function () {
    $student = Student::factory()->create();
    $mother = Guardian::factory()->create();
    $father = Guardian::factory()->create();
    $student->guardians()->attach($mother, ['relationship' => GuardianRelationship::Mother, 'is_primary' => true]);
    $student->guardians()->attach($father, ['relationship' => GuardianRelationship::Father, 'is_primary' => false]);

    Livewire::test(GuardiansRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
        ->callAction(TestAction::make(EditAction::class)->table($father), data: [
            'is_primary' => true,
        ])
        ->assertHasNoFormErrors();

    expect($student->guardians()->wherePivot('is_primary', true)->sole()->is($father))->toBeTrue();
});
