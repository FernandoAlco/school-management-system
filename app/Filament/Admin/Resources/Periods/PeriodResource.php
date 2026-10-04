<?php

namespace App\Filament\Admin\Resources\Periods;

use App\Filament\Admin\Resources\Periods\Pages\CreatePeriod;
use App\Filament\Admin\Resources\Periods\Pages\EditPeriod;
use App\Filament\Admin\Resources\Periods\Pages\ListPeriods;
use App\Filament\Admin\Resources\Periods\Schemas\PeriodForm;
use App\Filament\Admin\Resources\Periods\Tables\PeriodsTable;
use App\Models\Period;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PeriodResource extends Resource
{
    protected static ?string $model = Period::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Class Periods';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PeriodsTable::configure($table);
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
            'index' => ListPeriods::route('/'),
            'create' => CreatePeriod::route('/create'),
            'edit' => EditPeriod::route('/{record}/edit'),
        ];
    }
}
