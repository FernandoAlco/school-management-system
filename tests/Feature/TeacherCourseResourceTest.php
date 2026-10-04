<?php

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Filament\Teacher\Resources\Courses\Pages\ListCourses;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->teacher = Teacher::factory()->withUser()->create();
    $this->teacher->user->assignRole(UserRole::Teacher);
    $this->actingAs($this->teacher->user);

    Filament::setCurrentPanel('teacher');

    $this->section = Section::factory()->for(AcademicYear::factory()->current())->create();
});

it('lists only the courses taught by the signed-in teacher', function () {
    $own = Course::factory()->for($this->section)->for($this->teacher)->create();
    $otherTeachers = Course::factory()->for($this->section)->create();
    $unassigned = Course::factory()->for($this->section)->create(['teacher_id' => null]);

    Livewire::test(ListCourses::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$otherTeachers, $unassigned]);
});

it('lists courses of the current academic year by default', function () {
    $current = Course::factory()->for($this->section)->for($this->teacher)->create();
    $previous = Course::factory()
        ->for(Section::factory()->for(AcademicYear::factory()))
        ->for($this->teacher)
        ->create();

    Livewire::test(ListCourses::class)
        ->assertCanSeeTableRecords([$current])
        ->assertCanNotSeeTableRecords([$previous])
        ->filterTable('academic_year_id', $previous->section->academic_year_id)
        ->assertCanSeeTableRecords([$previous])
        ->assertCanNotSeeTableRecords([$current]);
});

it('shows how many active students are enrolled in the section', function () {
    $course = Course::factory()->for($this->section)->for($this->teacher)->create();
    Enrollment::factory()->count(2)->for($this->section)->create();
    Enrollment::factory()->for($this->section)->create(['status' => EnrollmentStatus::Withdrawn]);

    Livewire::test(ListCourses::class)
        ->assertTableColumnStateSet('section.active_enrollments_count', 2, $course);
});

it('is read-only for teachers', function () {
    $this->get('/teacher/courses')->assertOk();

    Livewire::test(ListCourses::class)
        ->assertActionDoesNotExist('create');
});
