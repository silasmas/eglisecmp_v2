<?php

declare(strict_types=1);

namespace App\Filament\Resources\PastoralReceptionResource\Pages;

use App\Filament\Resources\MinisterResource;
use App\Filament\Resources\PastoralReceptionResource;
use App\Models\SiteInquiry;
use App\Support\AppointmentReasons;
use App\Support\PastoralAccess;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Historique des RDV : temps respecté, clôtures, suivis — scopé au pasteur sauf titulaire/super_admin.
 */
class PastoralReceptionHistory extends ListRecords
{
    protected static string $resource = PastoralReceptionResource::class;

    protected static ?string $title = 'Historique des rendez-vous';

    protected static ?string $navigationLabel = 'Historique RDV';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * Actions d’en-tête.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToList')
                ->label('Réception du jour')
                ->url(PastoralReceptionResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }

    /**
     * Table historique (dossiers terminés / suivis / clos).
     */
    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where(function (Builder $q): void {
                    $q->whereIn('dossier_status', [
                        SiteInquiry::DOSSIER_CLOSED,
                        SiteInquiry::DOSSIER_FOLLOW_UP,
                        SiteInquiry::DOSSIER_SUSPENDED,
                    ])
                        ->orWhereNotNull('completed_at')
                        ->orWhereNotNull('time_respected');
                }))
            ->defaultSort('preferred_at', 'desc')
            ->columns([
                TextColumn::make('preferred_at')->label('Créneau')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('name')->label('Fidèle')->searchable(),
                TextColumn::make('minister.fullname')
                    ->label('Pasteur')
                    ->formatStateUsing(fn ($state): string => MinisterResource::normalizeLegacyValue($state) ?? '—')
                    ->visible(fn (): bool => PastoralAccess::canViewAllAppointments(auth()->user())),
                TextColumn::make('appointment_reason')
                    ->label('Motif')
                    ->formatStateUsing(fn (?string $state): string => AppointmentReasons::label($state)),
                TextColumn::make('dossier_status')
                    ->label('Dossier')
                    ->formatStateUsing(fn (?string $state): string => SiteInquiry::dossierStatusOptions()[$state ?? ''] ?? '—')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        SiteInquiry::DOSSIER_CLOSED => 'gray',
                        SiteInquiry::DOSSIER_SUSPENDED => 'danger',
                        SiteInquiry::DOSSIER_FOLLOW_UP => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('session_duration_minutes')
                    ->label('Durée prévue')
                    ->suffix(' min')
                    ->placeholder('—'),
                TextColumn::make('time_respected')
                    ->label('Temps respecté')
                    ->formatStateUsing(fn (?bool $state): string => match ($state) {
                        true => 'Oui',
                        false => 'Non — à améliorer',
                        default => '—',
                    })
                    ->badge()
                    ->color(fn (?bool $state): string => match ($state) {
                        true => 'success',
                        false => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('next_appointment_at')
                    ->label('Prochain RDV')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('closed_at')
                    ->label('Clôturé le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('time_respected')
                    ->label('Temps')
                    ->options([
                        '1' => 'Respecté',
                        '0' => 'Dépassé (à améliorer)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if ($value === '1') {
                            return $query->where('time_respected', true);
                        }
                        if ($value === '0') {
                            return $query->where('time_respected', false);
                        }

                        return $query;
                    }),
                SelectFilter::make('dossier_status')
                    ->label('Dossier')
                    ->options(SiteInquiry::dossierStatusOptions()),
            ])
            ->recordUrl(function (SiteInquiry $record): ?string {
                if (! PastoralReceptionResource::canView($record)) {
                    return null;
                }

                return PastoralReceptionResource::getUrl('view', ['record' => $record]);
            });
    }
}
