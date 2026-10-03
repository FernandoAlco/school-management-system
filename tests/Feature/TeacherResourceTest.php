<?php

use App\Enums\TeacherStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole(UserRole::Admin));
});

it('lists teachers sorted by last name', function () {
    $teachers = collect(['Young', 'Adams', 'Miller'])->map(fn (string $lastName): Teacher => Teacher::factory()->create(['last_name' => $lastName]));

    Livewire::test(ListTeachers::class)
        ->assertCanSeeTableRecords($teachers->sortBy('last_name'), inOrder: true);
});

it('searches teachers by name', function () {
    $match = Teacher::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $other = Teacher::factory()->create(['first_name' => 'Alan', 'last_name' => 'Turing']);

    Livewire::test(ListTeachers::class)
        ->searchTable('Lovelace')
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$other]);

    expect(TeacherResource::getGlobalSearchResults('Lovelace'))->toHaveCount(1);
});

it('filters teachers by status', function () {
    $active = Teacher::factory()->create();
    $inactive = Teacher::factory()->inactive()->create();

    Livewire::test(ListTeachers::class)
        ->filterTable('status', TeacherStatus::Inactive->value)
        ->assertCanSeeTableRecords([$inactive])
        ->assertCanNotSeeTableRecords([$active]);
});

it('creates a teacher', function () {
    Livewire::test(CreateTeacher::class)
        ->fillForm([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@school.test',
            'employee_number' => 'EMP-00001',
            'hire_date' => '2020-08-15',
            'status' => TeacherStatus::Active,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Teacher::query()->where('employee_number', 'EMP-00001')->sole())
        ->full_name->toBe('Ada Lovelace')
        ->status->toBe(TeacherStatus::Active);
});

it('validates the teacher form', function (array $data, array $errors) {
    Teacher::factory()->create(['employee_number' => 'EMP-00001']);

    Livewire::test(CreateTeacher::class)
        ->fillForm([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'employee_number' => 'EMP-00002',
            'status' => TeacherStatus::Active,
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'missing first name' => [['first_name' => null], ['first_name' => 'required']],
    'duplicated employee number' => [['employee_number' => 'EMP-00001'], ['employee_number' => 'unique']],
    'invalid email' => [['email' => 'not-an-email'], ['email' => 'email']],
]);

it('soft deletes a teacher', function () {
    $teacher = Teacher::factory()->create();

    Livewire::test(EditTeacher::class, ['record' => $teacher->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect($teacher->fresh()->trashed())->toBeTrue();
});
