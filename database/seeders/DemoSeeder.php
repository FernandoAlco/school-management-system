<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\GuardianRelationship;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Enums\Weekday;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Period;
use App\Models\ScheduleSlot;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Settings\SchoolSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds a complete demo elementary school: two academic years, six grade levels,
 * two sections per grade, teachers, students with their guardians, and one demo account per role.
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public const STUDENTS_PER_SECTION = 20;

    public const SECTION_CAPACITY = 25;

    /**
     * @var list<string>
     */
    public const SECTION_NAMES = ['A', 'B'];

    /**
     * Subjects taught by each section's homeroom teacher.
     *
     * @var array<string, string>
     */
    public const CORE_SUBJECTS = [
        'MATH' => 'Mathematics',
        'ENG' => 'English',
        'SCI' => 'Science',
        'SOC' => 'Social Studies',
    ];

    /**
     * Subjects taught by a specialist teacher across every section.
     *
     * @var array<string, string>
     */
    public const SPECIALIST_SUBJECTS = [
        'SPA' => 'Spanish',
        'ART' => 'Art',
        'MUS' => 'Music',
        'PE' => 'Physical Education',
    ];

    /**
     * Class periods of the school day; breaks are the gaps between them.
     *
     * @var list<array{name: string, starts_at: string, ends_at: string}>
     */
    public const PERIODS = [
        ['name' => '1st period', 'starts_at' => '08:00', 'ends_at' => '08:45'],
        ['name' => '2nd period', 'starts_at' => '08:45', 'ends_at' => '09:30'],
        ['name' => '3rd period', 'starts_at' => '09:45', 'ends_at' => '10:30'],
        ['name' => '4th period', 'starts_at' => '10:30', 'ends_at' => '11:15'],
        ['name' => '5th period', 'starts_at' => '11:30', 'ends_at' => '12:15'],
        ['name' => '6th period', 'starts_at' => '12:15', 'ends_at' => '13:00'],
    ];

    /**
     * Weekly periods per subject in every section; they add up to every period of the week.
     *
     * @var array<string, int>
     */
    public const WEEKLY_PERIODS = [
        'MATH' => 6,
        'ENG' => 6,
        'SCI' => 5,
        'SOC' => 5,
        'SPA' => 2,
        'ART' => 2,
        'MUS' => 2,
        'PE' => 2,
    ];

    /**
     * Families created so far, so that some students can be seeded as siblings.
     *
     * @var list<array{last_name: string, guardians: list<array{guardian: Guardian, relationship: GuardianRelationship}>}>
     */
    private array $families = [];

    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $this->seedSchoolSettings();

        [$previousYear, $currentYear] = $this->seedAcademicYears();
        $gradeLevels = $this->seedGradeLevels();
        $subjects = $this->seedSubjects();
        $classrooms = $this->seedClassrooms();
        [$homeroomTeachers, $specialistTeachers] = $this->seedTeachers();

        $previousSections = $this->seedSections($previousYear, $gradeLevels, $classrooms, $homeroomTeachers, $subjects, $specialistTeachers);
        $currentSections = $this->seedSections($currentYear, $gradeLevels, $classrooms, $homeroomTeachers, $subjects, $specialistTeachers);

        $this->seedTimetables($currentSections, $this->seedPeriods());

        $this->seedDemoFamily($currentYear, $previousSections, $currentSections);
        $this->seedStudents($currentYear, $previousSections, $currentSections);
        $this->seedGraduates($previousYear, $previousSections);

        User::factory()
            ->create(['first_name' => 'Demo', 'last_name' => 'Admin', 'email' => 'admin@example.com'])
            ->assignRole(UserRole::Admin);
    }

    private function seedSchoolSettings(): void
    {
        app(SchoolSettings::class)->fill([
            'name' => 'Maple Grove Elementary School',
            'principal_name' => 'Margaret Collins',
            'email' => 'office@maplegrove.test',
            'phone' => '(555) 010-2030',
            'address' => '1200 Maple Grove Ave, Springfield',
        ])->save();
    }

    /**
     * @return array{AcademicYear, AcademicYear}
     */
    private function seedAcademicYears(): array
    {
        $currentStartYear = now()->month >= 8 ? now()->year : now()->year - 1;

        return [
            $this->createAcademicYear($currentStartYear - 1, isCurrent: false),
            $this->createAcademicYear($currentStartYear, isCurrent: true),
        ];
    }

    private function createAcademicYear(int $startYear, bool $isCurrent): AcademicYear
    {
        $academicYear = AcademicYear::factory()->create([
            'name' => $startYear.'-'.($startYear + 1),
            'starts_on' => CarbonImmutable::create($startYear, 8, 20),
            'ends_on' => CarbonImmutable::create($startYear + 1, 7, 15),
            'is_current' => $isCurrent,
        ]);

        $academicYear->terms()->createMany([
            [
                'name' => 'Semester 1',
                'sort_order' => 1,
                'starts_on' => CarbonImmutable::create($startYear, 8, 20),
                'ends_on' => CarbonImmutable::create($startYear + 1, 1, 15),
            ],
            [
                'name' => 'Semester 2',
                'sort_order' => 2,
                'starts_on' => CarbonImmutable::create($startYear + 1, 1, 16),
                'ends_on' => CarbonImmutable::create($startYear + 1, 7, 15),
            ],
        ]);

        return $academicYear;
    }

    /**
     * @return Collection<int, GradeLevel> keyed by grade number
     */
    private function seedGradeLevels(): Collection
    {
        return collect(range(1, 6))->mapWithKeys(fn (int $grade): array => [
            $grade => GradeLevel::factory()->create(['name' => "Grade {$grade}", 'sort_order' => $grade]),
        ]);
    }

    /**
     * @return Collection<string, Subject> keyed by subject code
     */
    private function seedSubjects(): Collection
    {
        return collect([...self::CORE_SUBJECTS, ...self::SPECIALIST_SUBJECTS])
            ->map(fn (string $name, string $code): Subject => Subject::factory()->create(['name' => $name, 'code' => $code]));
    }

    /**
     * @return Collection<int, Classroom> one room per section, keyed by "{grade}{section}"
     */
    private function seedClassrooms(): Collection
    {
        return $this->sectionKeys()->mapWithKeys(fn (array $section, int $index): array => [
            $section['key'] => Classroom::factory()->create([
                'name' => 'Room '.(100 * $section['grade'] + $index % count(self::SECTION_NAMES) + 1),
                'capacity' => 30,
            ]),
        ]);
    }

    /**
     * @return array{Collection<string, Teacher>, Collection<string, Teacher>} homeroom teachers keyed by "{grade}{section}" and specialists keyed by subject code
     */
    private function seedTeachers(): array
    {
        $homeroomTeachers = $this->sectionKeys()->mapWithKeys(fn (array $section, int $index): array => [
            $section['key'] => $this->createTeacher($index === 0 ? ['email' => 'teacher@example.com'] : []),
        ]);

        $specialistTeachers = collect(self::SPECIALIST_SUBJECTS)
            ->map(fn (): Teacher => $this->createTeacher());

        return [$homeroomTeachers, $specialistTeachers];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTeacher(array $attributes = []): Teacher
    {
        $teacher = Teacher::factory()->state($attributes)->withUser()->create();

        $teacher->user->assignRole(UserRole::Teacher);

        return $teacher;
    }

    /**
     * @param  Collection<int, GradeLevel>  $gradeLevels
     * @param  Collection<string, Classroom>  $classrooms
     * @param  Collection<string, Teacher>  $homeroomTeachers
     * @param  Collection<string, Subject>  $subjects
     * @param  Collection<string, Teacher>  $specialistTeachers
     * @return Collection<string, Section> keyed by "{grade}{section}"
     */
    private function seedSections(
        AcademicYear $academicYear,
        Collection $gradeLevels,
        Collection $classrooms,
        Collection $homeroomTeachers,
        Collection $subjects,
        Collection $specialistTeachers,
    ): Collection {
        return $this->sectionKeys()->mapWithKeys(function (array $sectionKey) use ($academicYear, $gradeLevels, $classrooms, $homeroomTeachers, $subjects, $specialistTeachers): array {
            $homeroomTeacher = $homeroomTeachers[$sectionKey['key']];

            $section = Section::factory()->create([
                'academic_year_id' => $academicYear->id,
                'grade_level_id' => $gradeLevels[$sectionKey['grade']]->id,
                'name' => $sectionKey['name'],
                'homeroom_teacher_id' => $homeroomTeacher->id,
                'classroom_id' => $classrooms[$sectionKey['key']]->id,
                'capacity' => self::SECTION_CAPACITY,
            ]);

            $subjects->each(fn (Subject $subject, string $code): Course => Course::factory()->create([
                'section_id' => $section->id,
                'subject_id' => $subject->id,
                'teacher_id' => $specialistTeachers->get($code, $homeroomTeacher)->id,
            ]));

            return [$sectionKey['key'] => $section];
        });
    }

    /**
     * @return Collection<int, Period> ordered by start time
     */
    private function seedPeriods(): Collection
    {
        return collect(self::PERIODS)->map(fn (array $period): Period => Period::factory()->create([
            'name' => $period['name'],
            'starts_at' => "{$period['starts_at']}:00",
            'ends_at' => "{$period['ends_at']}:00",
        ]));
    }

    /**
     * Fill every period of the week in every section without clashes. The week is numbered as
     * cells 0-29 (day × period); specialist subjects rotate through cells so that each specialist,
     * who teaches every section, never has two classes in the same cell.
     *
     * @param  Collection<string, Section>  $sections
     * @param  Collection<int, Period>  $periods
     */
    private function seedTimetables(Collection $sections, Collection $periods): void
    {
        $schoolDays = Weekday::schoolDays();
        $cellsPerWeek = count($schoolDays) * $periods->count();
        $specialistCodes = array_keys(self::SPECIALIST_SUBJECTS);
        $now = now();

        $slots = $sections->values()->flatMap(function (Section $section, int $sectionIndex) use ($schoolDays, $periods, $cellsPerWeek, $specialistCodes, $now): array {
            $courses = $section->courses()->with('subject')->get()->keyBy('subject.code');
            $courseByCell = [];

            foreach ($specialistCodes as $specialistIndex => $code) {
                foreach (range(0, self::WEEKLY_PERIODS[$code] - 1) as $weeklyIndex) {
                    $courseByCell[(2 * $sectionIndex + $weeklyIndex + 7 * $specialistIndex) % $cellsPerWeek] = $courses[$code];
                }
            }

            $freeCells = array_values(array_diff(range(0, $cellsPerWeek - 1), array_keys($courseByCell)));

            foreach (array_keys(self::CORE_SUBJECTS) as $code) {
                foreach (array_splice($freeCells, 0, self::WEEKLY_PERIODS[$code]) as $cell) {
                    $courseByCell[$cell] = $courses[$code];
                }
            }

            return collect($courseByCell)->map(fn (Course $course, int $cell): array => [
                'course_id' => $course->id,
                'day_of_week' => $schoolDays[intdiv($cell, $periods->count())]->value,
                'period_id' => $periods[$cell % $periods->count()]->id,
                'classroom_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all();
        });

        ScheduleSlot::query()->insert($slots->all());
    }

    /**
     * Seed a family with known accounts: a guardian with two children, one of whom can log in.
     *
     * @param  Collection<string, Section>  $previousSections
     * @param  Collection<string, Section>  $currentSections
     */
    private function seedDemoFamily(AcademicYear $currentYear, Collection $previousSections, Collection $currentSections): void
    {
        $guardian = Guardian::factory()
            ->state(['first_name' => 'Laura', 'last_name' => 'Bennett', 'email' => 'guardian@example.com'])
            ->withUser()
            ->create();
        $guardian->user->assignRole(UserRole::Guardian);

        $this->families[] = [
            'last_name' => 'Bennett',
            'guardians' => [['guardian' => $guardian, 'relationship' => GuardianRelationship::Mother]],
        ];
        $family = array_key_last($this->families);

        $olderChild = $this->createStudent($currentYear, $previousSections, $currentSections, grade: 5, sectionName: 'A', family: $family);
        $this->createStudent($currentYear, $previousSections, $currentSections, grade: 2, sectionName: 'A', family: $family);

        // Keep the demo family out of the sibling pool so the demo guardian always has exactly two children.
        array_pop($this->families);

        $studentUser = User::factory()->create([
            'first_name' => $olderChild->first_name,
            'last_name' => $olderChild->last_name,
            'email' => 'student@example.com',
        ]);
        $studentUser->assignRole(UserRole::Student);
        $olderChild->user()->associate($studentUser)->save();
    }

    /**
     * Fill every current section, enrolling students in grade 2 and above in the previous year too.
     *
     * @param  Collection<string, Section>  $previousSections
     * @param  Collection<string, Section>  $currentSections
     */
    private function seedStudents(AcademicYear $currentYear, Collection $previousSections, Collection $currentSections): void
    {
        foreach ($this->sectionKeys() as $sectionKey) {
            $section = $currentSections[$sectionKey['key']];

            while ($section->enrollments()->count() < self::STUDENTS_PER_SECTION) {
                $this->createStudent($currentYear, $previousSections, $currentSections, $sectionKey['grade'], $sectionKey['name']);
            }
        }
    }

    /**
     * @param  Collection<string, Section>  $previousSections
     * @param  Collection<string, Section>  $currentSections
     */
    private function createStudent(
        AcademicYear $currentYear,
        Collection $previousSections,
        Collection $currentSections,
        int $grade,
        string $sectionName,
        ?int $family = null,
    ): Student {
        $student = $this->createStudentInFamily($family ?? $this->pickFamily(), $currentYear->starts_on->year, $grade);

        $this->enroll($student, $currentSections["{$grade}{$sectionName}"]);

        if ($grade > 1) {
            $this->enroll($student, $previousSections[($grade - 1).$sectionName]);
        }

        return $student;
    }

    /**
     * Seed last year's sixth graders, who have since graduated.
     *
     * @param  Collection<string, Section>  $previousSections
     */
    private function seedGraduates(AcademicYear $previousYear, Collection $previousSections): void
    {
        foreach (self::SECTION_NAMES as $sectionName) {
            for ($i = 0; $i < self::STUDENTS_PER_SECTION; $i++) {
                $student = $this->createStudentInFamily($this->pickFamily(), $previousYear->starts_on->year, grade: 6);
                $student->update(['status' => StudentStatus::Graduated]);

                $this->enroll($student, $previousSections["6{$sectionName}"]);
            }
        }
    }

    private function createStudentInFamily(int $family, int $academicYearStartYear, int $grade): Student
    {
        $birthYear = $academicYearStartYear - $grade - 6;
        $gender = fake()->randomElement([Gender::Male, Gender::Female]);

        $student = Student::factory()->create([
            'first_name' => fake()->firstName($gender === Gender::Male ? 'male' : 'female'),
            'last_name' => $this->families[$family]['last_name'],
            'gender' => $gender,
            'birth_date' => fake()->dateTimeBetween("{$birthYear}-09-01", ($birthYear + 1).'-08-31'),
        ]);

        foreach ($this->families[$family]['guardians'] as $index => $member) {
            $student->guardians()->attach($member['guardian'], [
                'relationship' => $member['relationship'],
                'is_primary' => $index === 0,
            ]);
        }

        return $student;
    }

    private function enroll(Student $student, Section $section): void
    {
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'academic_year_id' => $section->academic_year_id,
            'enrolled_on' => $section->academicYear->starts_on,
        ]);
    }

    /**
     * Return an existing family (so the student has siblings) or create a new one.
     */
    private function pickFamily(): int
    {
        if ($this->families !== [] && fake()->boolean(15)) {
            return array_rand($this->families);
        }

        $lastName = fake()->lastName();
        $guardians = [['guardian' => $this->createGuardian('female', $lastName), 'relationship' => GuardianRelationship::Mother]];

        if (fake()->boolean(70)) {
            $guardians[] = ['guardian' => $this->createGuardian('male', $lastName), 'relationship' => GuardianRelationship::Father];
        } elseif (fake()->boolean(20)) {
            $guardians[] = ['guardian' => $this->createGuardian(fake()->randomElement(['male', 'female']), fake()->lastName()), 'relationship' => GuardianRelationship::LegalGuardian];
        }

        $this->families[] = ['last_name' => $lastName, 'guardians' => $guardians];

        return array_key_last($this->families);
    }

    private function createGuardian(string $gender, string $lastName): Guardian
    {
        $factory = Guardian::factory()->state([
            'first_name' => fake()->firstName($gender),
            'last_name' => $lastName,
        ]);

        $guardian = (fake()->boolean(25) ? $factory->withUser() : $factory)->create();

        $guardian->user?->assignRole(UserRole::Guardian);

        return $guardian;
    }

    /**
     * @return Collection<int, array{key: string, grade: int, name: string}>
     */
    private function sectionKeys(): Collection
    {
        return collect(range(1, 6))
            ->crossJoin(self::SECTION_NAMES)
            ->map(fn (array $pair): array => ['key' => $pair[0].$pair[1], 'grade' => $pair[0], 'name' => $pair[1]]);
    }
}
