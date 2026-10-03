<?php

namespace App\Filament\Resources\Sections\Schemas;

use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Teacher;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class SectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormSection::make('Section')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('academic_year_id')
                            ->relationship('academicYear', 'name', fn (Builder $query): Builder => $query->orderByDesc('starts_on'))
                            ->default(fn (): ?int => AcademicYear::query()->current()->value('id'))
                            ->required()
                            ->live(),
                        Select::make('grade_level_id')
                            ->relationship('gradeLevel', 'name', fn (Builder $query): Builder => $query->orderBy('sort_order'))
                            ->required()
                            ->live(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('A')
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                ->where('academic_year_id', $get('academic_year_id'))
                                ->where('grade_level_id', $get('grade_level_id')))
                            ->validationMessages([
                                'unique' => 'This grade already has a section with this name in the academic year.',
                            ]),
                        TextInput::make('capacity')
                            ->integer()
                            ->minValue(fn (?Section $record): int => max(1, $record?->enrollments()->count() ?? 1))
                            ->maxValue(65535),
                    ]),
                FormSection::make('Homeroom')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('homeroom_teacher_id')
                            ->label('Homeroom teacher')
                            ->relationship('homeroomTeacher', 'last_name', fn (Builder $query): Builder => $query->orderBy('last_name')->orderBy('first_name'))
                            ->getOptionLabelFromRecordUsing(fn (Teacher $record): string => $record->full_name)
                            ->searchable(['first_name', 'last_name'])
                            ->preload(),
                        Select::make('classroom_id')
                            ->relationship('classroom', 'name', fn (Builder $query): Builder => $query->orderBy('name'))
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }
}
