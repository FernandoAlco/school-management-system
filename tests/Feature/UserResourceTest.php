<?php

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->superAdmin = User::factory()->create()->assignRole(UserRole::SuperAdmin);
    $this->actingAs($this->superAdmin);
});

it('lists users sorted by last name', function () {
    $users = collect(['Young', 'Adams', 'Miller'])->map(fn (string $lastName): User => User::factory()->create(['last_name' => $lastName]));

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords($users->push($this->superAdmin)->sortBy('last_name'), inOrder: true);

    expect(UserResource::getGlobalSearchResults('Adams'))->toHaveCount(1);
});

it('filters users by role', function () {
    $admin = User::factory()->create()->assignRole(UserRole::Admin);
    $teacher = User::factory()->create()->assignRole(UserRole::Teacher);

    Livewire::test(ListUsers::class)
        ->filterTable('roles', [Role::findByName(UserRole::Admin->value)->id])
        ->assertCanSeeTableRecords([$admin])
        ->assertCanNotSeeTableRecords([$teacher]);
});

it('creates a user with a hashed password and roles', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@school.test',
            'password' => 'secret-password',
            'roles' => [Role::findByName(UserRole::Admin->value)->id],
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'grace@school.test')->sole();

    expect(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->hasRole(UserRole::Admin))->toBeTrue()
        ->and($user->canAccessPanel(filament()->getPanel('admin')))->toBeTrue();
});

it('validates the user form', function (array $data, array $errors) {
    User::factory()->create(['email' => 'taken@school.test']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@school.test',
            'password' => 'secret-password',
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'missing password' => [['password' => null], ['password' => 'required']],
    'short password' => [['password' => 'short'], ['password']],
    'duplicated email' => [['email' => 'taken@school.test'], ['email' => 'unique']],
]);

it('keeps the current password when left blank', function () {
    $user = User::factory()->create();
    $originalPassword = $user->password;

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['email' => 'new-email@school.test', 'password' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh())
        ->email->toBe('new-email@school.test')
        ->password->toBe($originalPassword);
});

it('deactivates a user so they can no longer access the panel', function () {
    $admin = User::factory()->create()->assignRole(UserRole::Admin);

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($admin->fresh()->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();
});

it('does not let users delete or deactivate themselves', function () {
    Livewire::test(EditUser::class, ['record' => $this->superAdmin->getRouteKey()])
        ->assertActionHidden(DeleteAction::class)
        ->assertFormFieldDisabled('is_active');
});

it('does not edit the name of users linked to a profile', function () {
    $teacher = Teacher::factory()->withUser()->create();

    Livewire::test(EditUser::class, ['record' => $teacher->user->getRouteKey()])
        ->assertFormFieldDisabled('first_name')
        ->assertFormFieldDisabled('last_name');
});
