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
use Filament\Infolists\Components\ViewEntry;
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
                    ]),
                Section::make('Réponses du pasteur')
                    ->description('Chaque question avec son libellé et la valeur saisie (filtrée selon votre périmètre).')
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('filtered_answers_view')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->view('filament.guest-forms.submission-answers'),
                    ]),
            ]);
    }

    /**
     * Prépare les réponses (libellé + valeur) pour l’affichage admin.
     *
     * @return list<array{label: string, value: string}>
     */
    public static function answersForDisplay(GuestInfoSubmission $record): array
    {
        $user = auth()->user() instanceof User ? auth()->user() : null;
        $payload = GuestFormAnswerScope::visiblePayloadForUser($user, $record);
        $fields = GuestInfoFormField::query()
            ->whereHas('section', fn ($q) => $q->where('form_id', $record->form_id))
            ->orderBy('sort_order')
            ->get(['key', 'label', 'type', 'options', 'sort_order']);

        $ordered = [];
        foreach ($fields as $field) {
            if (! array_key_exists($field->key, $payload)) {
                continue;
            }
            $ordered[] = [
                'label' => (string) $field->label,
                'value' => self::formatAnswerValue($payload[$field->key], $field->type, is_array($field->options) ? $field->options : null),
            ];
            unset($payload[$field->key]);
        }

        foreach ($payload as $key => $value) {
            $ordered[] = [
                'label' => (string) $key,
                'value' => self::formatAnswerValue($value),
            ];
        }

        return $ordered;
    }

    /**
     * Formate une valeur de réponse pour l’affichage admin.
     *
     * @param  array<string, mixed>|null  $options
     */
    public static function formatAnswerValue(mixed $value, ?string $type = null, ?array $options = null): string
    {
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        if (is_array($value)) {
            $choices = is_array($options['choices'] ?? null) ? $options['choices'] : [];

            return collect($value)
                ->map(function ($item) use ($choices) {
                    if (is_array($item)) {
                        if (isset($item['category'], $item['item'])) {
                            return (string) $item['category'].' : '.(string) $item['item'];
                        }
                        if (count($item) === 2 && array_is_list($item)) {
                            return (string) $item[0].' : '.(string) $item[1];
                        }

                        return (string) json_encode($item, JSON_UNESCAPED_UNICODE);
                    }

                    $key = (string) $item;
                    if ($choices !== [] && isset($choices[$key]) && is_scalar($choices[$key])) {
                        return (string) $choices[$key];
                    }

                    return $key;
                })
                ->filter(fn ($line): bool => $line !== '')
                ->implode("\n");
        }

        $scalar = (string) $value;
        if (is_array($options['choices'] ?? null) && isset($options['choices'][$scalar]) && is_scalar($options['choices'][$scalar])) {
            return (string) $options['choices'][$scalar];
        }

        return $scalar;
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
