<?php

use App\Enums\UserRole;
use App\Filament\Resources\GradeLevels\Pages\CreateGradeLevel;
use App\Filament\Resources\GradeLevels\Pages\EditGradeLevel;
use App\Filament\Resources\GradeLevels\Pages\ListGradeLevels;
use App\Models\GradeLevel;
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

it('lists grade levels in order', function () {
    $gradeLevels = collect([3, 1, 2])->map(fn (int $order): GradeLevel => GradeLevel::factory()->create(['name' => "Grade {$order}", 'sort_order' => $order]));

    Livewire::test(ListGradeLevels::class)
        ->assertCanSeeTableRecords($gradeLevels->sortBy('sort_order'), inOrder: true);
});

it('creates a grade level', function () {
    Livewire::test(CreateGradeLevel::class)
        ->fillForm(['name' => 'Grade 1', 'sort_order' => 1])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(GradeLevel::query()->where('name', 'Grade 1')->sole()->sort_order)->toBe(1);
});

it('validates the grade level form', function (array $data, array $errors) {
    GradeLevel::factory()->create(['name' => 'Grade 1', 'sort_order' => 1]);

    Livewire::test(CreateGradeLevel::class)
        ->fillForm(['name' => 'Grade 2', 'sort_order' => 2, ...$data])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'missing name' => [['name' => null], ['name' => 'required']],
    'duplicated name' => [['name' => 'Grade 1'], ['name' => 'unique']],
    'order below 1' => [['sort_order' => 0], ['sort_order' => 'min']],
]);

it('only allows deleting grade levels without sections', function () {
    $gradeLevel = GradeLevel::factory()->create();

    Livewire::test(EditGradeLevel::class, ['record' => $gradeLevel->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect($gradeLevel->exists())->toBeFalse();

    $inUse = Section::factory()->create()->gradeLevel;

    Livewire::test(EditGradeLevel::class, ['record' => $inUse->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});
