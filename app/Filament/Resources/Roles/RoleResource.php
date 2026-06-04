<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Rôles Shield (surcharge) : en édition, le nom n’est pas revalidé (uniquement les permissions).
 */
class RoleResource extends ShieldRoleResource
{
    #[\Override]
    public static function form(Schema $schema): Schema
    {
        $rolesTable = config('permission.table_names.roles', 'roles');

        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->disabled(fn (?Model $record): bool => $record !== null)
                                    ->dehydrated()
                                    ->required()
                                    ->maxLength(255)
                                    ->rules(fn (?Model $record): array => $record !== null
                                        ? ['required', 'string', 'max:255']
                                        : [
                                            'required',
                                            'string',
                                            'max:255',
                                            Rule::unique($rolesTable, 'name')
                                                ->where('guard_name', Utils::getFilamentAuthGuard()),
                                        ])
                                    ->validationMessages([
                                        'unique' => 'Ce nom de rôle existe déjà pour ce guard.',
                                        'required' => 'Le nom du rôle est obligatoire.',
                                    ]),

                                TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->disabled(fn (?Model $record): bool => $record !== null)
                                    ->dehydrated()
                                    ->nullable()
                                    ->maxLength(255),

                                Select::make(config('permission.column_names.team_foreign_key'))
                                    ->label(__('filament-shield::filament-shield.field.team'))
                                    ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                                    ->default(Filament::getTenant()?->id)
                                    ->options(fn (): array => in_array(Utils::getTenantModel(), [null, '', '0'], true) ? [] : Utils::getTenantModel()::pluck('name', 'id')->toArray())
                                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled())
                                    ->dehydrated(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
                                static::getSelectAllFormComponent(),
                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                static::getShieldFormComponents(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
