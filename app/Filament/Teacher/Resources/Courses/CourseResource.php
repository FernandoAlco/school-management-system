<?php

namespace App\Filament\Teacher\Resources\Courses;

use App\Filament\Teacher\Resources\Courses\Pages\ListCourses;
use App\Filament\Teacher\Resources\Courses\Tables\CoursesTable;
use App\Models\Course;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'My courses';

    protected static ?string $modelLabel = 'course';

    /**
     * Access is granted by the teacher panel itself and records are scoped to the signed-in
     * teacher, so this read-only resource does not depend on Shield permissions.
     */
    public static function canViewAny(): bool
    {
        return true;
    }

    /**
     * @return Builder<Course>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('teacher', fn (Builder $query): Builder => $query->where('user_id', auth()->id()));
    }

    public static function table(Table $table): Table
    {
        return CoursesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
        ];
    }
}
