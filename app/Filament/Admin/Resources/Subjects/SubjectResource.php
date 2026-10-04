<?php

namespace App\Filament\Admin\Resources\Subjects;

use App\Filament\Admin\Resources\Subjects\Pages\CreateSubject;
use App\Filament\Admin\Resources\Subjects\Pages\EditSubject;
use App\Filament\Admin\Resources\Subjects\Pages\ListSubjects;
use App\Filament\Admin\Resources\Subjects\Schemas\SubjectForm;
use App\Filament\Admin\Resources\Subjects\Tables\SubjectsTable;
use App\Models\Subject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SubjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubjectsTable::configure($table);
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
            'index' => ListSubjects::route('/'),
            'create' => CreateSubject::route('/create'),
            'edit' => EditSubject::route('/{record}/edit'),
        ];
    }
}
