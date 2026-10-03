<?php

namespace App\Filament\Resources\Sections\RelationManagers;

use App\Enums\TeacherStatus;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class CoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'courses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name', fn (Builder $query): Builder => $query->orderBy('name'))
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('section_id', $this->getOwnerRecord()->getKey()))
                    ->validationMessages([
                        'unique' => 'This subject is already assigned to the section.',
                    ]),
                Select::make('teacher_id')
                    ->label('Teacher')
                    ->relationship('teacher', 'last_name', fn (Builder $query): Builder => $query
                        ->where('status', TeacherStatus::Active)
                        ->orderBy('last_name')
                        ->orderBy('first_name'))
                    ->getOptionLabelFromRecordUsing(fn (Teacher $record): string => $record->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (Course $record): string => $record->subject->name)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['subject', 'teacher']))
            ->columns([
                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable(),
                TextColumn::make('subject.code')
                    ->label('Code'),
                TextColumn::make('teacher.full_name')
                    ->label('Teacher')
                    ->placeholder('Unassigned')
                    ->searchable(['first_name', 'last_name']),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy(Subject::query()->select('name')->whereColumn('subjects.id', 'courses.subject_id')))
            ->headerActions([
                CreateAction::make()
                    ->label('Add subject'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
