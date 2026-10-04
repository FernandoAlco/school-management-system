<?php

namespace App\Filament\Admin\Resources\Sections;

use App\Filament\Admin\Resources\Sections\Pages\CreateSection;
use App\Filament\Admin\Resources\Sections\Pages\EditSection;
use App\Filament\Admin\Resources\Sections\Pages\ListSections;
use App\Filament\Admin\Resources\Sections\RelationManagers\CoursesRelationManager;
use App\Filament\Admin\Resources\Sections\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Admin\Resources\Sections\RelationManagers\ScheduleSlotsRelationManager;
use App\Filament\Admin\Resources\Sections\Schemas\SectionForm;
use App\Filament\Admin\Resources\Sections\Tables\SectionsTable;
use App\Models\Section;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SectionResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static ?int $navigationSort = 5;

    /**
     * @param  Section|null  $record
     */
    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record?->getLabel() ?? static::getModelLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return SectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EnrollmentsRelationManager::class,
            CoursesRelationManager::class,
            ScheduleSlotsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSections::route('/'),
            'create' => CreateSection::route('/create'),
            'edit' => EditSection::route('/{record}/edit'),
        ];
    }
}
