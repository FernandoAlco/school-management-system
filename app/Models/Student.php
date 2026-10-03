<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Observers\ProfileObserver;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

#[Fillable(['user_id', 'student_number', 'first_name', 'last_name', 'birth_date', 'gender', 'national_id', 'address', 'photo_path', 'status'])]
#[ObservedBy(ProfileObserver::class)]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Guardian, $this, GuardianStudent>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)
            ->using(GuardianStudent::class)
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Make the given guardian the student's only primary guardian.
     */
    public function makePrimaryGuardian(Guardian $guardian): void
    {
        DB::transaction(function () use ($guardian): void {
            GuardianStudent::query()
                ->where('student_id', $this->getKey())
                ->update(['is_primary' => false]);

            $this->guardians()->updateExistingPivot($guardian->getKey(), ['is_primary' => true]);
        });
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => "{$this->first_name} {$this->last_name}");
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'status' => StudentStatus::class,
        ];
    }
}
