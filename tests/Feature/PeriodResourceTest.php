<?php

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Periods\Pages\CreatePeriod;
use App\Filament\Admin\Resources\Periods\Pages\EditPeriod;
use App\Filament\Admin\Resources\Periods\Pages\ListPeriods;
use App\Models\Period;
use App\Models\ScheduleSlot;
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

it('lists periods ordered by start time', function () {
    $second = Period::factory()->create(['name' => '2nd period', 'starts_at' => '08:45:00', 'ends_at' => '09:30:00']);
    $first = Period::factory()->create(['name' => '1st period', 'starts_at' => '08:00:00', 'ends_at' => '08:45:00']);

    Livewire::test(ListPeriods::class)
        ->assertCanSeeTableRecords([$first, $second], inOrder: true);
});

it('creates a period', function () {
    Livewire::test(CreatePeriod::class)
        ->fillForm(['name' => '1st period', 'starts_at' => '08:00', 'ends_at' => '08:45'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Period::query()->where('name', '1st period')->sole())
        ->starts_at->toBe('08:00:00')
        ->ends_at->toBe('08:45:00');
});

it('validates the period form', function (array $data, array $errors) {
    Period::factory()->create(['name' => '1st period', 'starts_at' => '08:00:00', 'ends_at' => '08:45:00']);

    Livewire::test(CreatePeriod::class)
        ->fillForm(['name' => '2nd period', 'starts_at' => '09:00', 'ends_at' => '09:45', ...$data])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'missing name' => [['name' => null], ['name' => 'required']],
    'duplicated name' => [['name' => '1st period'], ['name' => 'unique']],
    'ends before it starts' => [['starts_at' => '10:00', 'ends_at' => '09:15'], ['ends_at' => 'after']],
    'overlaps another period' => [['starts_at' => '08:30', 'ends_at' => '09:15'], ['ends_at']],
    'contains another period' => [['starts_at' => '07:30', 'ends_at' => '09:15'], ['ends_at']],
]);

it('allows periods that touch without overlapping', function () {
    Period::factory()->create(['starts_at' => '08:00:00', 'ends_at' => '08:45:00']);

    Livewire::test(CreatePeriod::class)
        ->fillForm(['name' => '2nd period', 'starts_at' => '08:45', 'ends_at' => '09:30'])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('does not treat a period as overlapping itself when edited', function () {
    $period = Period::factory()->create(['starts_at' => '08:00:00', 'ends_at' => '08:45:00']);

    Livewire::test(EditPeriod::class, ['record' => $period->getRouteKey()])
        ->fillForm(['ends_at' => '08:50'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($period->fresh()->ends_at)->toBe('08:50:00');
});

it('removes the period from timetables when deleted', function () {
    $scheduleSlot = ScheduleSlot::factory()->create();

    Livewire::test(EditPeriod::class, ['record' => $scheduleSlot->period->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect($scheduleSlot->fresh())->toBeNull();
});
