<?php

use App\Enums\UserRole;
use App\Filament\Pages\ManageSchoolSettings;
use App\Models\User;
use App\Settings\SchoolSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('shows the school settings page to admins', function () {
    $admin = User::factory()->create()->assignRole(UserRole::Admin);

    $this->actingAs($admin)->get(ManageSchoolSettings::getUrl())->assertOk();
});

it('forbids non-admins from the school settings page', function () {
    $teacher = User::factory()->create()->assignRole(UserRole::Teacher);

    $this->actingAs($teacher)->get(ManageSchoolSettings::getUrl())->assertForbidden();
});

it('saves the school settings', function () {
    $this->actingAs(User::factory()->create()->assignRole(UserRole::Admin));

    Livewire::test(ManageSchoolSettings::class)
        ->fillForm([
            'name' => 'Springfield Elementary',
            'principal_name' => 'Seymour Skinner',
            'email' => 'office@springfield.test',
            'phone' => '555-0100',
            'address' => '19 Plympton St',
            'passing_grade' => 70,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(SchoolSettings::class);

    expect($settings->name)->toBe('Springfield Elementary')
        ->and($settings->principal_name)->toBe('Seymour Skinner')
        ->and($settings->passing_grade)->toBe(70);
});

it('validates the school settings', function (array $data, array $errors) {
    $this->actingAs(User::factory()->create()->assignRole(UserRole::Admin));

    Livewire::test(ManageSchoolSettings::class)
        ->fillForm($data)
        ->call('save')
        ->assertHasFormErrors($errors);
})->with([
    'missing name' => [['name' => ''], ['name' => 'required']],
    'invalid email' => [['email' => 'not-an-email'], ['email' => 'email']],
    'passing grade below 0' => [['passing_grade' => -1], ['passing_grade' => 'min']],
    'passing grade above 100' => [['passing_grade' => 101], ['passing_grade' => 'max']],
]);

it('uses the school name as the admin panel brand', function () {
    app(SchoolSettings::class)->fill(['name' => 'Springfield Elementary'])->save();

    $this->actingAs(User::factory()->create()->assignRole(UserRole::Admin))
        ->get('/admin')
        ->assertSee('Springfield Elementary');
});
