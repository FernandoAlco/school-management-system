<?php

use App\Enums\UserRole;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows teachers with a teacher profile to access the teacher panel', function () {
    $teacher = Teacher::factory()->withUser()->create();
    $teacher->user->assignRole(UserRole::Teacher);

    $this->actingAs($teacher->user)->get('/teacher')->assertOk();
});

it('forbids teachers without a teacher profile from accessing the teacher panel', function () {
    $user = User::factory()->create()->assignRole(UserRole::Teacher);

    $this->actingAs($user)->get('/teacher')->assertForbidden();
});

it('forbids non-teacher roles from accessing the teacher panel', function (UserRole $role) {
    $user = User::factory()->create()->assignRole($role);

    $this->actingAs($user)->get('/teacher')->assertForbidden();
})->with([UserRole::SuperAdmin, UserRole::Admin, UserRole::Guardian, UserRole::Student]);

it('forbids inactive teachers from accessing the teacher panel', function () {
    $teacher = Teacher::factory()->withUser()->create();
    $teacher->user->update(['is_active' => false]);
    $teacher->user->assignRole(UserRole::Teacher);

    $this->actingAs($teacher->user)->get('/teacher')->assertForbidden();
});

it('redirects guests to the teacher login page', function () {
    $this->get('/teacher')->assertRedirect('/teacher/login');
});
