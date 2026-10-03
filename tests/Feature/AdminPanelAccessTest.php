<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows admins to access the admin panel', function (UserRole $role) {
    $user = User::factory()->create()->assignRole($role);

    $this->actingAs($user)->get('/admin')->assertOk();
})->with([UserRole::SuperAdmin, UserRole::Admin]);

it('forbids non-admin roles from accessing the admin panel', function (UserRole $role) {
    $user = User::factory()->create()->assignRole($role);

    $this->actingAs($user)->get('/admin')->assertForbidden();
})->with([UserRole::Teacher, UserRole::Guardian, UserRole::Student]);

it('forbids users without a role from accessing the admin panel', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('forbids inactive admins from accessing the admin panel', function () {
    $user = User::factory()->inactive()->create()->assignRole(UserRole::Admin);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('redirects guests to the admin login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('seeds every role and a super admin account', function () {
    $this->seed();

    expect(Role::query()->pluck('name')->sort()->values()->all())
        ->toBe(collect(UserRole::cases())->pluck('value')->sort()->values()->all())
        ->and(User::query()->where('email', 'superadmin@example.com')->sole()->hasRole(UserRole::SuperAdmin))->toBeTrue();
});
