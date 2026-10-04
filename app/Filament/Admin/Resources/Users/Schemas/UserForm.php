<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn (?User $record): bool => $record?->hasProfile() ?? false)
                            ->helperText(fn (?User $record): ?string => $record?->hasProfile() ? 'Managed from the linked teacher, student or guardian profile.' : null),
                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn (?User $record): bool => $record?->hasProfile() ?? false),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Leave blank to keep the current password.' : null),
                    ]),
                Section::make('Access')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => UserRole::tryFrom($record->name)?->getLabel() ?? $record->name)
                            ->multiple()
                            ->preload(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Inactive users cannot sign in to any panel.')
                            ->default(true)
                            ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false),
                    ]),
            ]);
    }
}
