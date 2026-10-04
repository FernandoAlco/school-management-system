<?php

namespace App\Filament\Admin\Resources\Guardians;

use App\Filament\Admin\Resources\Guardians\Pages\CreateGuardian;
use App\Filament\Admin\Resources\Guardians\Pages\EditGuardian;
use App\Filament\Admin\Resources\Guardians\Pages\ListGuardians;
use App\Filament\Admin\Resources\Guardians\RelationManagers\StudentsRelationManager;
use App\Filament\Admin\Resources\Guardians\Schemas\GuardianForm;
use App\Filament\Admin\Resources\Guardians\Tables\GuardiansTable;
use App\Models\Guardian;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GuardianResource extends Resource
{
    protected static ?string $model = Guardian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'full_name';

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'phone'];
    }

    public static function form(Schema $schema): Schema
    {
        return GuardianForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GuardiansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGuardians::route('/'),
            'create' => CreateGuardian::route('/create'),
            'edit' => EditGuardian::route('/{record}/edit'),
        ];
    }
}
