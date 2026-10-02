<?php

use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('syncs the profile name to its user when the profile is created', function (string $profileClass) {
    $user = User::factory()->create(['first_name' => 'Old', 'last_name' => 'Name']);

    $profileClass::factory()->for($user)->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

    expect($user->fresh())
        ->first_name->toBe('Jane')
        ->last_name->toBe('Smith')
        ->full_name->toBe('Jane Smith');
})->with([Teacher::class, Student::class, Guardian::class]);

it('syncs the profile name to its user when the name changes', function (string $profileClass) {
    $profile = $profileClass::factory()->withUser()->create();

    $profile->update(['last_name' => 'Johnson']);

    expect($profile->user->fresh()->last_name)->toBe('Johnson');
})->with([Teacher::class, Student::class, Guardian::class]);

it('syncs the profile name when a user is linked later', function () {
    $student = Student::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $user = User::factory()->create();

    $student->user()->associate($user)->save();

    expect($user->fresh()->full_name)->toBe('John Doe');
});

it('does not touch any user when the profile has no account', function () {
    $user = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'User']);

    Teacher::factory()->create();

    expect($user->fresh()->full_name)->toBe('Admin User');
});
