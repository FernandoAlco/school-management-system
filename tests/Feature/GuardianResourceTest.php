<?php

use App\Enums\GuardianRelationship;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Guardians\GuardianResource;
use App\Filament\Admin\Resources\Guardians\Pages\CreateGuardian;
use App\Filament\Admin\Resources\Guardians\Pages\EditGuardian;
use App\Filament\Admin\Resources\Guardians\Pages\ListGuardians;
use App\Filament\Admin\Resources\Guardians\RelationManagers\StudentsRelationManager;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\AttachAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole(UserRole::SuperAdmin));
});

it('lists guardians sorted by last name', function () {
    $guardians = collect(['Young', 'Adams', 'Miller'])->map(fn (string $lastName): Guardian => Guardian::factory()->create(['last_name' => $lastName]));

    Livewire::test(ListGuardians::class)
        ->assertCanSeeTableRecords($guardians->sortBy('last_name'), inOrder: true);

    expect(GuardianResource::getGlobalSearchResults('Adams'))->toHaveCount(1);
});

it('creates a guardian', function () {
    Livewire::test(CreateGuardian::class)
        ->fillForm([
            'first_name' => 'Laura',
            'last_name' => 'Bennett',
            'email' => 'laura@example.test',
            'phone' => '555-0100',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Guardian::query()->where('email', 'laura@example.test')->sole()->full_name)->toBe('Laura Bennett');
});

it('validates the guardian form', function (array $data, array $errors) {
    Livewire::test(CreateGuardian::class)
        ->fillForm(['first_name' => 'Laura', 'last_name' => 'Bennett', ...$data])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'missing last name' => [['last_name' => null], ['last_name' => 'required']],
    'invalid email' => [['email' => 'not-an-email'], ['email' => 'email']],
]);

it('attaches a student as primary guardian', function () {
    $guardian = Guardian::factory()->create();
    $student = Student::factory()->create();
    $student->guardians()->attach(Guardian::factory()->create(), ['relationship' => GuardianRelationship::Father, 'is_primary' => true]);

    Livewire::test(StudentsRelationManager::class, ['ownerRecord' => $guardian, 'pageClass' => EditGuardian::class])
        ->callAction(TestAction::make(AttachAction::class)->table(), data: [
            'recordId' => $student->id,
            'relationship' => GuardianRelationship::Mother,
            'is_primary' => true,
        ])
        ->assertHasNoFormErrors();

    expect($student->guardians()->wherePivot('is_primary', true)->sole()->is($guardian))->toBeTrue();
});

it('deletes a guardian and unlinks their students', function () {
    $guardian = Guardian::factory()->create();
    $student = Student::factory()->create();
    $student->guardians()->attach($guardian, ['relationship' => GuardianRelationship::Mother, 'is_primary' => true]);

    Livewire::test(EditGuardian::class, ['record' => $guardian->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect($guardian->exists())->toBeFalse()
        ->and($student->guardians()->count())->toBe(0);
});
