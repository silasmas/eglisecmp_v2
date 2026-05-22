<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Administration des comptes utilisateurs du panneau Filament.
 */
class UserResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $modelLabel = 'Utilisateur';

    protected static ?string $pluralModelLabel = 'Utilisateurs';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    /**
     * Libellé affiché pour un rôle (évite les labels null dans les Select Filament).
     */
    public static function roleOptionLabel(Role $role): string
    {
        $label = filled($role->display_name)
            ? $role->display_name
            : (filled($role->name) ? $role->name : null);

        return $label ?? "Rôle #{$role->id}";
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Identité')
                    ->description('Coordonnées et rôle applicatif de l\'utilisateur.')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(6),
                        Select::make('role_id')
                            ->label('Rôle')
                            ->relationship(
                                'roleModel',
                                'display_name',
                                fn (Builder $query): Builder => $query->orderBy('display_name')->orderBy('name'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Role $record): string => static::roleOptionLabel($record),
                            )
                            ->searchable(['display_name', 'name'])
                            ->preload()
                            ->required()
                            ->native(false)
                            ->helperText('Définit les droits d\'accès au panneau d\'administration.')
                            ->columnSpan(6),
                        Toggle::make('notifiable')
                            ->label('Utilisateur notifiable')
                            ->helperText('Reçoit les notifications système (e-mail, alertes internes).')
                            ->default(false)
                            ->columnSpan(6),
                    ]),
                Section::make('Sécurité')
                    ->description('Laisse vide à la modification pour conserver le mot de passe actuel.')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->same('passwordConfirmation')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->columnSpan(6),
                        TextInput::make('passwordConfirmation')
                            ->label('Confirmation du mot de passe')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->columnSpan(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('roleModel.display_name')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->roleModel
                        ? static::roleOptionLabel($record->roleModel)
                        : '—'),
                IconColumn::make('notifiable')->label('Notifiable')->boolean(),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
