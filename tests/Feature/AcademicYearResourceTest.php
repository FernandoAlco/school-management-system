<?php

use App\Enums\UserRole;
use App\Filament\Admin\Resources\AcademicYears\Pages\CreateAcademicYear;
use App\Filament\Admin\Resources\AcademicYears\Pages\EditAcademicYear;
use App\Filament\Admin\Resources\AcademicYears\Pages\ListAcademicYears;
use App\Filament\Admin\Resources\AcademicYears\RelationManagers\TermsRelationManager;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole(UserRole::SuperAdmin));
});

it('lists academic years', function () {
    $academicYears = AcademicYear::factory()->count(3)->sequence(
        ['name' => '2024-2025'],
        ['name' => '2025-2026'],
        ['name' => '2026-2027'],
    )->create();

    Livewire::test(ListAcademicYears::class)
        ->assertOk()
        ->assertCanSeeTableRecords($academicYears);
});

it('creates an academic year', function () {
    Livewire::test(CreateAcademicYear::class)
        ->fillForm([
            'name' => '2027-2028',
            'starts_on' => '2027-08-20',
            'ends_on' => '2028-07-15',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(AcademicYear::query()->where('name', '2027-2028')->sole())
        ->starts_on->toDateString()->toBe('2027-08-20')
        ->is_current->toBeFalse();
});

it('validates the academic year form', function (array $data, array $errors) {
    AcademicYear::factory()->create(['name' => '2026-2027']);

    Livewire::test(CreateAcademicYear::class)
        ->fillForm([
            'name' => '2027-2028',
            'starts_on' => '2027-08-20',
            'ends_on' => '2028-07-15',
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'missing name' => [['name' => null], ['name' => 'required']],
    'duplicated name' => [['name' => '2026-2027'], ['name' => 'unique']],
    'ends before it starts' => [['ends_on' => '2027-08-01'], ['ends_on' => 'after']],
]);

it('sets an academic year as the only current one', function () {
    $previous = AcademicYear::factory()->current()->create(['name' => '2025-2026']);
    $next = AcademicYear::factory()->create(['name' => '2026-2027']);

    Livewire::test(ListAcademicYears::class)
        ->assertActionHidden(TestAction::make('markAsCurrent')->table($previous))
        ->callAction(TestAction::make('markAsCurrent')->table($next));

    expect($next->fresh()->is_current)->toBeTrue()
        ->and($previous->fresh()->is_current)->toBeFalse();
});

it('only allows deleting academic years without sections', function () {
    $academicYear = AcademicYear::factory()->create();

    Livewire::test(EditAcademicYear::class, ['record' => $academicYear->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect($academicYear->exists())->toBeFalse();

    $inUse = Section::factory()->create()->academicYear;

    Livewire::test(EditAcademicYear::class, ['record' => $inUse->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});

it('creates terms within the academic year', function () {
    $academicYear = AcademicYear::factory()->create([
        'starts_on' => '2026-08-20',
        'ends_on' => '2027-07-15',
    ]);

    Livewire::test(TermsRelationManager::class, ['ownerRecord' => $academicYear, 'pageClass' => EditAcademicYear::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'name' => 'Semester 1',
            'sort_order' => 1,
            'starts_on' => '2026-08-20',
            'ends_on' => '2027-01-15',
        ])
        ->assertHasNoFormErrors();

    expect($academicYear->terms()->sole()->name)->toBe('Semester 1');
});

it('validates terms against the academic year', function (array $data, array $errors) {
    $academicYear = AcademicYear::factory()->create([
        'starts_on' => '2026-08-20',
        'ends_on' => '2027-07-15',
    ]);
    $academicYear->terms()->create([
        'name' => 'Semester 1',
        'sort_order' => 1,
        'starts_on' => '2026-08-20',
        'ends_on' => '2027-01-15',
    ]);

    Livewire::test(TermsRelationManager::class, ['ownerRecord' => $academicYear, 'pageClass' => EditAcademicYear::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'name' => 'Semester 2',
            'sort_order' => 2,
            'starts_on' => '2027-01-16',
            'ends_on' => '2027-07-15',
            ...$data,
        ])
        ->assertHasFormErrors($errors);
})->with([
    'duplicated order' => [['sort_order' => 1], ['sort_order' => 'unique']],
    'starts before the academic year' => [['starts_on' => '2026-08-01'], ['starts_on' => 'after_or_equal']],
    'ends after the academic year' => [['ends_on' => '2027-08-01'], ['ends_on' => 'before_or_equal']],
    'ends before it starts' => [['ends_on' => '2027-01-10'], ['ends_on' => 'after']],
]);
