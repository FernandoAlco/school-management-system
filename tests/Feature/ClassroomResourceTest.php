<?php

use App\Enums\UserRole;
use App\Filament\Resources\Classrooms\Pages\CreateClassroom;
use App\Filament\Resources\Classrooms\Pages\EditClassroom;
use App\Filament\Resources\Classrooms\Pages\ListClassrooms;
use App\Models\Classroom;
use App\Models\Section;
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

it('lists classrooms', function () {
    $classrooms = Classroom::factory()->count(3)->create();

    Livewire::test(ListClassrooms::class)
        ->assertCanSeeTableRecords($classrooms);
});

it('creates a classroom', function () {
    Livewire::test(CreateClassroom::class)
        ->fillForm(['name' => 'Room 101', 'capacity' => 30])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Classroom::query()->where('name', 'Room 101')->sole()->capacity)->toBe(30);
});

it('validates the classroom form', function (array $data, array $errors) {
    Classroom::factory()->create(['name' => 'Room 101']);

    Livewire::test(CreateClassroom::class)
        ->fillForm(['name' => 'Room 102', 'capacity' => 30, ...$data])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'missing name' => [['name' => null], ['name' => 'required']],
    'duplicated name' => [['name' => 'Room 101'], ['name' => 'unique']],
    'capacity below 1' => [['capacity' => 0], ['capacity' => 'min']],
]);

it('unassigns the classroom from its sections when deleted', function () {
    $section = Section::factory()->for(Classroom::factory())->create();

    Livewire::test(EditClassroom::class, ['record' => $section->classroom->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect($section->fresh()->classroom_id)->toBeNull();
});

it('shows the catalog resources in the admin navigation', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSeeInOrder(['Academic', 'Academic Years', 'Grade Levels', 'Subjects', 'Classrooms']);
});
