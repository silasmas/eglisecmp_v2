<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ChurchWorkerResource\Pages;
use App\Models\ChurchDepartment;
use App\Models\ChurchWorker;
use App\Models\User;
use App\Services\ChurchWorkerApprovalService;
use App\Support\FilamentImageUrl;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use TinusG\FilamentHoverImageColumn\HoverImageColumn as ImageColumn;
use UnitEnum;

/**
 * Validation des ouvriers et génération de badges.
 */
class ChurchWorkerResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = ChurchWorker::class;

    protected static ?string $navigationLabel = 'Ouvriers';

    protected static ?string $modelLabel = 'Ouvrier';

    protected static ?string $pluralModelLabel = 'Ouvriers';

    protected static ?string $recordTitleAttribute = 'last_name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|UnitEnum|null $navigationGroup = 'Ouvriers';

    protected static ?int $navigationSort = 11;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['department', 'user']);

        $user = auth()->user();
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin') || $user->can('ViewAny:ChurchWorker')) {
            return $query;
        }

        $managedIds = ChurchDepartment::query()
            ->where('manager_user_id', $user->id)
            ->pluck('id');

        return $query->whereIn('department_id', $managedIds);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        if ($user->can('ViewAny:ChurchWorker') || $user->hasRole('super_admin')) {
            return true;
        }

        return ChurchDepartment::query()->where('manager_user_id', $user->id)->exists();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Identité')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('department_id')->label('Département')->relationship('department', 'name')->required()->columnSpan(6),
                        TextInput::make('first_name')->label('Prénom')->required()->columnSpan(3),
                        TextInput::make('last_name')->label('Nom')->required()->columnSpan(3),
                        Select::make('gender')->label('Sexe')->options(ChurchWorker::genderOptions())->required()->columnSpan(3),
                        DatePicker::make('birth_date')->label('Naissance')->required()->native(false)->columnSpan(3),
                        TextInput::make('phone')->label('Téléphone')->required()->columnSpan(3),
                        TextInput::make('email')->label('E-mail')->email()->columnSpan(3),
                        TextInput::make('department_role')->label('Rôle dans le département')->columnSpan(6),
                        Textarea::make('skills')->label('Compétences')->rows(3)->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Ouvrier')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        ImageEntry::make('photo_path')
                            ->label('Photo')
                            ->circular()
                            ->getStateUsing(fn (ChurchWorker $record): ?string => FilamentImageUrl::resolve($record->photo_path))
                            ->columnSpan(3),
                        TextEntry::make('full_name')
                            ->label('Nom')
                            ->state(fn (ChurchWorker $r): string => $r->fullName())
                            ->columnSpan(3),
                        TextEntry::make('department.name')->label('Département')->columnSpan(3),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->formatStateUsing(fn (?string $state): string => ChurchWorker::statusOptions()[$state ?? ''] ?? ($state ?? '—'))
                            ->badge()
                            ->columnSpan(3),
                        TextEntry::make('phone')->label('Téléphone')->columnSpan(4),
                        TextEntry::make('email')->label('E-mail')->columnSpan(4),
                        TextEntry::make('department_role')->label('Rôle')->placeholder('—')->columnSpan(4),
                        TextEntry::make('city')->label('Ville')->columnSpan(3),
                        TextEntry::make('commune')->label('Commune')->columnSpan(3),
                        TextEntry::make('quartier')->label('Quartier')->columnSpan(3),
                        TextEntry::make('avenue')->label('Avenue')->columnSpan(3),
                        TextEntry::make('profession')->label('Profession')->placeholder('—')->columnSpan(4),
                        TextEntry::make('education_level')->label('Niveau')->placeholder('—')->columnSpan(4),
                        TextEntry::make('studies')->label('Études')->placeholder('—')->columnSpan(4),
                        TextEntry::make('skills')->label('Compétences')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('badge_token')
                            ->label('Lien badge')
                            ->formatStateUsing(fn (string $token): string => url('/ouvriers/badge/'.$token))
                            ->copyable()
                            ->columnSpanFull(),
                        TextEntry::make('badge_generated')
                            ->label('Badge')
                            ->formatStateUsing(fn (?bool $state): string => $state ? 'Généré / validé' : 'Non généré')
                            ->columnSpan(4),
                        TextEntry::make('user.email')->label('Compte user')->placeholder('—')->columnSpan(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->circular()
                    ->getStateUsing(fn (ChurchWorker $r): ?string => FilamentImageUrl::resolve($r->photo_path)),
                TextColumn::make('first_name')->label('Prénom')->searchable(),
                TextColumn::make('last_name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('department.name')->label('Département')->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ChurchWorker::statusOptions()[$state ?? ''] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => match ($state) {
                        ChurchWorker::STATUS_APPROVED => 'success',
                        ChurchWorker::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('badge_generated')
                    ->label('Badge')
                    ->formatStateUsing(fn (?bool $state): string => $state ? 'Oui' : 'Non')
                    ->badge()
                    ->color(fn (?bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('created_at')->label('Soumis le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ChurchWorker::statusOptions()),
                SelectFilter::make('department_id')->label('Département')->relationship('department', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ChurchWorker $record): bool => $record->status === ChurchWorker::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->action(function (ChurchWorker $record): void {
                        $user = auth()->user();
                        if (! $user instanceof User) {
                            return;
                        }
                        app(ChurchWorkerApprovalService::class)->approve($record, $user);
                        Notification::make()->title('Ouvrier validé et compte créé')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Refuser')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ChurchWorker $record): bool => $record->status === ChurchWorker::STATUS_PENDING)
                    ->form([
                        Textarea::make('rejection_reason')->label('Motif')->required(),
                    ])
                    ->action(function (ChurchWorker $record, array $data): void {
                        $user = auth()->user();
                        if (! $user instanceof User) {
                            return;
                        }
                        app(ChurchWorkerApprovalService::class)->reject($record, $user, $data['rejection_reason'] ?? null);
                        Notification::make()->title('Inscription refusée')->warning()->send();
                    }),
                Action::make('generateBadge')
                    ->label('Générer badge')
                    ->icon('heroicon-o-qr-code')
                    ->color('primary')
                    ->visible(fn (ChurchWorker $record): bool => $record->status === ChurchWorker::STATUS_APPROVED && ! $record->badge_generated)
                    ->action(function (ChurchWorker $record): void {
                        app(ChurchWorkerApprovalService::class)->generateBadge($record);
                        Notification::make()
                            ->title('Badge généré')
                            ->body(url('/ouvriers/badge/'.$record->badge_token))
                            ->success()
                            ->send();
                    }),
                Action::make('openBadge')
                    ->label('Voir badge')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ChurchWorker $record): string => url('/ouvriers/badge/'.$record->badge_token))
                    ->openUrlInNewTab()
                    ->visible(fn (ChurchWorker $record): bool => $record->badge_generated),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChurchWorkers::route('/'),
            'view' => Pages\ViewChurchWorker::route('/{record}'),
            'edit' => Pages\EditChurchWorker::route('/{record}/edit'),
        ];
    }
}
