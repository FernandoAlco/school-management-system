<?php

namespace App\Models;

use Database\Factories\PeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['name', 'starts_at', 'ends_at'])]
class Period extends Model
{
    /** @use HasFactory<PeriodFactory> */
    use HasFactory;

    /**
     * @return HasMany<ScheduleSlot, $this>
     */
    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class);
    }

    /**
     * Label used across the panels, e.g. "1st period (08:00–08:45)".
     */
    public function getLabel(): string
    {
        return "{$this->name} ({$this->time_range})";
    }

    /**
     * @param  Builder<Period>  $query
     */
    #[Scope]
    protected function overlapping(Builder $query, string $startsAt, string $endsAt): void
    {
        $query
            ->where('starts_at', '<', self::normalizeTime($endsAt))
            ->where('ends_at', '>', self::normalizeTime($startsAt));
    }

    /**
     * Times are stored as "H:i:s" so that they compare correctly as strings.
     */
    public static function normalizeTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }

    /**
     * @return Attribute<string, string>
     */
    protected function startsAt(): Attribute
    {
        return Attribute::set(fn (string $value): string => self::normalizeTime($value));
    }

    /**
     * @return Attribute<string, string>
     */
    protected function endsAt(): Attribute
    {
        return Attribute::set(fn (string $value): string => self::normalizeTime($value));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function timeRange(): Attribute
    {
        return Attribute::get(fn (): string => substr($this->starts_at, 0, 5).'–'.substr($this->ends_at, 0, 5));
    }
}
