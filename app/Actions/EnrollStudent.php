<?php

namespace App\Actions;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollStudent
{
    /**
     * Enroll an active student in a section, enforcing the section capacity
     * and a single enrollment per student and academic year.
     *
     * @throws ValidationException
     */
    public function handle(Student $student, Section $section, ?CarbonInterface $enrolledOn = null): Enrollment
    {
        return DB::transaction(function () use ($student, $section, $enrolledOn): Enrollment {
            $section = Section::query()->lockForUpdate()->findOrFail($section->getKey());

            if ($student->status !== StudentStatus::Active) {
                throw ValidationException::withMessages([
                    'student_id' => 'Only active students can be enrolled.',
                ]);
            }

            $isAlreadyEnrolled = Enrollment::query()
                ->where('student_id', $student->getKey())
                ->where('academic_year_id', $section->academic_year_id)
                ->exists();

            if ($isAlreadyEnrolled) {
                throw ValidationException::withMessages([
                    'student_id' => 'The student is already enrolled in this academic year.',
                ]);
            }

            if ($section->isFull()) {
                throw ValidationException::withMessages([
                    'section_id' => 'The section has reached its capacity.',
                ]);
            }

            return $section->enrollments()->create([
                'student_id' => $student->getKey(),
                'academic_year_id' => $section->academic_year_id,
                'enrolled_on' => $enrolledOn ?? today(),
                'status' => EnrollmentStatus::Active,
            ]);
        });
    }
}
