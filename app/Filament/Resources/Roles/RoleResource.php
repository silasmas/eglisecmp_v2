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
use Illuminate\Validation\Rules\Unique;

/**
 * Rôles Shield (surcharge) : nom non modifiable à l’édition, unicité nom + guard, messages FR.
 */
class RoleResource extends ShieldRoleResource
{
    #[\Override]
    public static function form(Schema $schema): Schema
    {
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
                                    ->unique(
                                        ignoreRecord: true,
                                        modifyRuleUsing: function (Unique $rule, callable $get, ?Model $record): Unique {
                                            $guard = $get('guard_name')
                                                ?? $record?->guard_name
                                                ?? Utils::getFilamentAuthGuard();

                                            $scoped = $rule->where('guard_name', $guard);

                                            return Utils::isTenancyEnabled()
                                                ? $scoped->where(Utils::getTenantModelForeignKey(), Filament::getTenant()?->id)
                                                : $scoped;
                                        }
                                    )
                                    ->validationMessages([
                                        'unique' => 'Ce nom de rôle est déjà utilisé pour ce guard. Si l’erreur persiste, exécutez : php artisan migrate',
                                        'required' => 'Le nom du rôle est obligatoire.',
                                    ])
                                    ->required()
                                    ->maxLength(255),

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
