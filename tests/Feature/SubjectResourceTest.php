<?php

use App\Enums\UserRole;
use App\Filament\Resources\Subjects\Pages\CreateSubject;
use App\Filament\Resources\Subjects\Pages\EditSubject;
use App\Filament\Resources\Subjects\Pages\ListSubjects;
use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole(UserRole::SuperAdmin));
});

it('lists subjects', function () {
    $subjects = Subject::factory()->count(3)->create();

    Livewire::test(ListSubjects::class)
        ->assertCanSeeTableRecords($subjects);
});

it('creates a subject', function () {
    Livewire::test(CreateSubject::class)
        ->fillForm(['name' => 'Mathematics', 'code' => 'MATH'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Subject::query()->where('code', 'MATH')->sole()->name)->toBe('Mathematics');
});

it('updates a subject', function () {
    $subject = Subject::factory()->create();

    Livewire::test(EditSubject::class, ['record' => $subject->getRouteKey()])
        ->fillForm(['name' => 'Science', 'code' => 'SCI'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($subject->fresh())
        ->name->toBe('Science')
        ->code->toBe('SCI');
});

it('validates the subject form', function (array $data, array $errors) {
    Subject::factory()->create(['code' => 'MATH']);

    Livewire::test(CreateSubject::class)
        ->fillForm(['name' => 'Science', 'code' => 'SCI', ...$data])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'missing name' => [['name' => null], ['name' => 'required']],
    'duplicated code' => [['code' => 'MATH'], ['code' => 'unique']],
]);

it('only allows deleting subjects without courses', function () {
    $subject = Subject::factory()->create();

    Livewire::test(EditSubject::class, ['record' => $subject->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect($subject->exists())->toBeFalse();

    $inUse = Course::factory()->create()->subject;

    Livewire::test(EditSubject::class, ['record' => $inUse->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});
