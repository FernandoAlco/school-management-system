# Project Plan: School Management System

A school management system built with **Laravel + Filament**, designed to be self-hosted by a single school (one installation = one school, **no multi-tenancy**).

## Stack

| Component | Package |
|---|---|
| Framework | Laravel 13 |
| Panel / UI | Filament 5 |
| Roles and permissions | `bezhansalleh/filament-shield` (includes `spatie/laravel-permission`, `teams => false`) |
| School settings | `filament/spatie-laravel-settings-plugin` (`spatie/laravel-settings`) |
| PDF (report cards, receipts) | `barryvdh/laravel-dompdf` *(pending, Phase 2)* |
| Tests | Pest |
| Code quality | Laravel Pint, Larastan *(pending)* |

> Decision: **do not** use `saade/filament-fullcalendar`. The timetable is built as a custom Filament page (day × hour grid) and the school calendar as a table grouped by month.

## Architecture

### Panels

| Panel | Path | Users | What they see |
|---|---|---|---|
| Admin | `/admin` | `super_admin`, `admin` | The whole system |
| Teachers | `/teacher` | `teacher` | Only their `courses`: attendance, grades, timetable |
| Portal | `/portal` | `student`, `guardian` | Only their own data (or their linked children's) |

- Access to each panel is controlled in `User::canAccessPanel()`.
- Data isolation is done with roles + `modifyQueryUsing` in Resources (e.g. a teacher only sees `courses` where `teacher_id` is theirs; a guardian only sees students in `guardian_student`).

### Principles

- **Profiles separate from `users`**: `teachers`, `students` and `guardians` have a nullable `user_id`. The login account is created only when needed.
- **Names**: `users` and every profile have `first_name` / `last_name` (no `name` column). When a profile has a user, the profile is the source of truth and `ProfileObserver` syncs its name to the user.
- **`courses` is the core**: subject + section + teacher. Timetables, attendance and grades hang from it.
- **`enrollments` per academic year**: the student's history is kept year after year.
- **PHP enums** (`app/Enums`) with `HasLabel` / `HasColor`; stored as `string` in the database.
- **Money** as `decimal(10,2)`, never `float`.
- **Business logic outside Resources**: in *Action* classes (`EnrollStudent`, `CalculateFinalGrade`, …) so it is testable.
- **School data as settings** (`SchoolSettings`), not as a table.
- **Grades on a 0-100 scale**; the passing grade is configurable in `SchoolSettings` (default 60).

## Database Schema

```
academic_years → terms
grade_levels, subjects, classrooms
teachers / students / guardians   (optional user_id)
     students ←→ guardian_student ←→ guardians
sections ← academic_year + grade_level
  ├── enrollments (student enrolled in a section for an academic year)
  └── courses (subject + section + teacher)
         ├── schedule_slots
         ├── attendance_sessions → attendance_records
         └── grades (per student and term)
announcements, events
fee_concepts → charges → payments
```

### Pending tables (reference)

```text
schedule_slots
  id, course_id FK cascade, classroom_id FK nullable,
  day_of_week tinyint, starts_at time, ends_at time, timestamps
  [index: day_of_week, starts_at]   -- conflicts validated in the app

attendance_sessions
  id, course_id FK, date, taken_by FK→users, timestamps
  [unique: course_id, date]

attendance_records
  id, attendance_session_id FK cascade, student_id FK,
  status (present, absent, late, excused), notes nullable, timestamps
  [unique: attendance_session_id, student_id]

grades
  id, course_id FK, student_id FK, term_id FK,
  score decimal(5,2), comments nullable, timestamps   -- 0-100 scale
  [unique: course_id, student_id, term_id]

announcements
  id, author_id FK→users, title, body text,
  audience (all, teachers, guardians, students, section),
  section_id FK nullable, published_at nullable, timestamps

events
  id, title, description nullable, starts_on, ends_on nullable,
  type (holiday, exam, meeting, activity),
  audience (all, teachers, guardians, students), timestamps

fee_concepts
  id, name, amount decimal(10,2), is_active bool, timestamps

charges
  id, student_id FK, fee_concept_id FK, academic_year_id FK,
  description nullable, amount decimal(10,2), due_on,
  status (pending, partial, paid, cancelled), timestamps

payments
  id, charge_id FK, amount decimal(10,2), paid_on,
  method (cash, card, transfer), reference nullable,
  received_by FK→users, timestamps
```

## Roadmap

### Phase 0 — Project foundation

- [x] Laravel + Filament installed
- [x] Filament Shield
- [x] Spatie Settings plugin
- [x] Pest
- [x] Initialize git repository and first commit
- [ ] Larastan + GitHub Actions (Pint, Larastan, Pest on every push)

### Phase 1 — MVP (academic structure)

- [x] Migrations, models and factories: `academic_years`, `terms`, `grade_levels`, `subjects`, `classrooms`, `teachers`, `students`, `guardians`, `guardian_student`, `sections`, `enrollments`, `courses`
- [x] Enums: `TeacherStatus`, `StudentStatus`, `Gender`, `GuardianRelationship`, `EnrollmentStatus`
- [x] Factory and relationship tests (`tests/Feature/ModelFactoriesTest.php`)
- [x] `first_name` / `last_name` on `users` + name sync from profiles (`ProfileObserver`)
- [x] `SchoolSettings` + settings page in Filament
- [x] Roles (`super_admin`, `admin`, `teacher`, `guardian`, `student`) + `/admin` access via `User::canAccessPanel()`
- [ ] Resource permissions with Shield (`shield:generate` once Resources exist)
- [x] Realistic demo seeders (a complete school): `php artisan migrate:fresh --seed --seeder=DemoSeeder`
- [ ] `/admin` panel: Resources for academic years, terms, grade levels, subjects, classrooms, teachers, students, guardians, sections, enrollments and courses
- [ ] `EnrollStudent` action (validate section capacity and one enrollment per academic year)

### Phase 2 — Daily operations

- [ ] `/teacher` and `/portal` panels with `canAccessPanel()` and per-user scopes
- [ ] Timetables: `schedule_slots` + conflict validation (teacher, classroom, section) + weekly grid page
- [ ] Attendance: `attendance_sessions` / `attendance_records` + quick roll-call page
- [ ] Grades: `grades` + per-term entry + average calculation
- [ ] PDF report cards with dompdf

### Phase 3 — Extras

- [ ] Dashboard with widgets (average attendance, at-risk students, section occupancy)
- [ ] Announcements (`announcements`) with Filament notifications
- [ ] School calendar (`events`)
- [ ] Bulk student import/export (Filament Importers/Exporters)
- [ ] Tuition and payments: `fee_concepts`, `charges`, `payments` + PDF receipts

### Phase 4 — Presentation (portfolio)

- [ ] Main README with screenshots/GIFs, ER diagram and demo users per role
- [ ] 3-command installation (`composer run setup` or similar)
- [ ] Live demo

## Conventions

- Tables, columns, code and UI in **English**.
- Generate files with `php artisan make:*` (`--no-interaction`).
- Models: `#[Fillable]` attribute, `casts()` as a method, typed relationships with generics in PHPDoc.
- Format with `vendor/bin/pint` before every commit.
- Every new module ships with its Pest tests.
