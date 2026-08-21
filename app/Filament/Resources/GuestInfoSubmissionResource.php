<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\GuestInfoSubmissionResource\Pages;
use App\Filament\Resources\GuestInfoSubmissionResource\RelationManagers\DepartmentNotificationsRelationManager;
use App\Models\GuestInfoFormField;
use App\Models\GuestInfoSubmission;
use App\Models\User;
use App\Support\GuestFormAnswerScope;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Consultation des soumissions de fiches (filtrées par département sauf admin).
 */
class GuestInfoSubmissionResource extends Resource
{
    protected static ?string $model = GuestInfoSubmission::class;

    protected static ?string $navigationLabel = 'Réponses';

    protected static ?string $modelLabel = 'Réponse';

    protected static ?string $pluralModelLabel = 'Réponses';

    protected static ?string $slug = 'guest-info-submissions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected static string|UnitEnum|null $navigationGroup = 'Accueil invités';

    protected static ?int $navigationSort = 30;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['guestPastor', 'form.project']);

        $user = auth()->user();
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if (GuestFormAnswerScope::canViewAll($user)) {
            return $query;
        }

        $deptIds = GuestFormAnswerScope::managedDepartmentIds($user);
        if ($deptIds === []) {
            return $query->whereRaw('1 = 0');
        }

        // Les responsables voient les soumissions des projets où leur département est impliqué.
        return $query->whereHas('form.project.departments', function (Builder $q) use ($deptIds): void {
            $q->whereIn('church_departments.id', $deptIds);
        });
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Soumission')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('guestPastor.full_name')->label('Pasteur')->columnSpan(4),
                        TextEntry::make('form.title')->label('Formulaire')->columnSpan(4),
                        TextEntry::make('submitted_at')->label('Reçu le')->dateTime('d/m/Y H:i')->columnSpan(4),
                        TextEntry::make('filtered_answers')
                            ->label('Réponses')
                            ->columnSpanFull()
                            ->state(function (GuestInfoSubmission $record): string {
                                $user = auth()->user() instanceof User ? auth()->user() : null;
                                $payload = GuestFormAnswerScope::visiblePayloadForUser($user, $record);
                                $labels = GuestInfoFormField::query()
                                    ->whereHas('section', fn ($q) => $q->where('form_id', $record->form_id))
                                    ->pluck('label', 'key');

                                $lines = [];
                                foreach ($payload as $key => $value) {
                                    $label = $labels[$key] ?? $key;
                                    $lines[] = $label.' : '.self::formatAnswerValue($value);
                                }

                                return $lines !== [] ? implode("\n", $lines) : 'Aucune réponse visible pour votre périmètre.';
                            })
                            ->markdown()
                            ->prose(),
                    ]),
            ]);
    }

    /**
     * Formate une valeur de réponse pour l’affichage admin.
     */
    public static function formatAnswerValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }
        if (is_array($value)) {
            return collect($value)
                ->map(function ($item) {
                    if (is_array($item)) {
                        return json_encode($item, JSON_UNESCAPED_UNICODE);
                    }

                    return (string) $item;
                })
                ->filter()
                ->implode(', ');
        }

        return (string) $value;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('submitted_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('guestPastor.full_name')->label('Pasteur')->searchable(),
                TextColumn::make('form.title')->label('Formulaire'),
                TextColumn::make('form.project.title')->label('Projet'),
                TextColumn::make('dept_ack')
                    ->label('Accusés dépts')
                    ->state(function (GuestInfoSubmission $record): string {
                        $rows = $record->departmentNotifications()
                            ->where('status', \App\Models\GuestDepartmentNotification::STATUS_SENT)
                            ->get()
                            ->groupBy('church_department_id');
                        if ($rows->isEmpty()) {
                            return 'Aucun envoi';
                        }
                        $acked = $rows->filter(
                            fn ($group) => $group->contains(fn ($n) => $n->acknowledged_at !== null)
                        )->count();

                        return $acked.'/'.$rows->count().' reçus';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        if (str_starts_with($state, 'Aucun')) {
                            return 'gray';
                        }
                        if (preg_match('/^(\d+)\/(\d+)/', $state, $m) === 1 && $m[1] === $m[2]) {
                            return 'success';
                        }

                        return 'warning';
                    }),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DepartmentNotificationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuestInfoSubmissions::route('/'),
            'view' => Pages\ViewGuestInfoSubmission::route('/{record}'),
        ];
    }
}
