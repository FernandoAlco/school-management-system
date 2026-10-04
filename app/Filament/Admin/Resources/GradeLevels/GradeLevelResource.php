<?php

namespace App\Filament\Admin\Resources\GradeLevels;

use App\Filament\Admin\Resources\GradeLevels\Pages\CreateGradeLevel;
use App\Filament\Admin\Resources\GradeLevels\Pages\EditGradeLevel;
use App\Filament\Admin\Resources\GradeLevels\Pages\ListGradeLevels;
use App\Filament\Admin\Resources\GradeLevels\Schemas\GradeLevelForm;
use App\Filament\Admin\Resources\GradeLevels\Tables\GradeLevelsTable;
use App\Models\GradeLevel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GradeLevelResource extends Resource
{
    protected static ?string $model = GradeLevel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return GradeLevelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GradeLevelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGradeLevels::route('/'),
            'create' => CreateGradeLevel::route('/create'),
            'edit' => EditGradeLevel::route('/{record}/edit'),
        ];
    }
}
