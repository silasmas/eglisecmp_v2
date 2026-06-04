<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonyResource\Pages;
use App\Models\Testimony;
use App\Services\TestimonyNotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Modération et consultation des témoignages du mur public.
 */
class TestimonyResource extends Resource
{
    use HasTabbedActions;

    public const LIST_TAB_ALL = 'all';

    public const LIST_TAB_PENDING = 'pending';

    public const LIST_TAB_APPROVED = 'approved';

    public const LIST_TAB_REJECTED = 'rejected';

    protected static ?string $model = Testimony::class;

    protected static ?string $navigationLabel = 'Mur de témoignages';

    protected static ?string $modelLabel = 'Témoignage';

    protected static ?string $pluralModelLabel = 'Témoignages';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Contenu')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        Select::make('kind')
                            ->label('Type')
                            ->options([
                                Testimony::KIND_TEXT => 'Texte',
                                Testimony::KIND_VIDEO => 'Vidéo',
                                Testimony::KIND_MIX => 'Mixte',
                            ])
                            ->required()
                            ->columnSpan(4),
                        Select::make('status')
                            ->label('Statut')
                            ->options([
                                Testimony::STATUS_PENDING => 'En attente',
                                Testimony::STATUS_APPROVED => 'Approuvé',
                                Testimony::STATUS_REJECTED => 'Refusé',
                            ])
                            ->required()
                            ->columnSpan(4),
                        TextInput::make('category')->label('Catégorie')->columnSpan(4),
                        TextInput::make('first_name')->label('Prénom')->required()->columnSpan(4),
                        TextInput::make('last_name')->label('Nom')->columnSpan(4),
                        TextInput::make('title')->label('Titre')->required()->columnSpanFull(),
                        Textarea::make('text')->label('Texte')->rows(6)->columnSpanFull(),
                        TextInput::make('video')->label('URL vidéo')->url()->columnSpanFull(),
                        TextInput::make('postit_color')->label('Couleur post-it')->columnSpan(6),
                        TextInput::make('font_family')->label('Police')->columnSpan(6),
                    ]),
                Section::make('Contact (interne)')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextInput::make('email')->email()->columnSpan(6),
                        TextInput::make('phone')->columnSpan(6),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Témoignage')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextEntry::make('kind')->label('Type')->columnSpan(3),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                Testimony::STATUS_APPROVED => 'Approuvé',
                                Testimony::STATUS_REJECTED => 'Refusé',
                                default => 'En attente',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                Testimony::STATUS_APPROVED => 'success',
                                Testimony::STATUS_REJECTED => 'danger',
                                default => 'warning',
                            })
                            ->columnSpan(3),
                        TextEntry::make('category')->label('Catégorie')->placeholder('—')->columnSpan(3),
                        TextEntry::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i')->columnSpan(3),
                        TextEntry::make('is_anonymous')
                            ->label('Anonyme')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Oui' : 'Non')
                            ->columnSpan(3),
                        TextEntry::make('rejection_reason')
                            ->label('Motif de refus')
                            ->visible(fn (?Testimony $record): bool => $record !== null && $record->status === Testimony::STATUS_REJECTED)
                            ->columnSpanFull(),
                        TextEntry::make('first_name')->label('Prénom')->columnSpan(4),
                        TextEntry::make('last_name')->label('Nom')->columnSpan(4),
                        TextEntry::make('title')->label('Titre')->columnSpanFull(),
                        TextEntry::make('text')->label('Texte')->columnSpanFull(),
                        TextEntry::make('video')->label('Vidéo')->url(fn (?string $state): bool => filled($state))->columnSpanFull(),
                        TextEntry::make('postit_color')->label('Couleur')->columnSpan(6),
                        TextEntry::make('font_family')->label('Police')->columnSpan(6),
                        ViewEntry::make('media_preview')
                            ->label('Aperçu médias')
                            ->view('filament.infolists.testimony-media')
                            ->columnSpanFull(),
                        RepeatableEntry::make('images')
                            ->label('Images (liste)')
                            ->schema([
                                ImageEntry::make('image')
                                    ->label('')
                                    ->disk('public')
                                    ->visibility('public'),
                            ])
                            ->columnSpanFull()
                            ->visible(fn (Testimony $record): bool => $record->images()->exists()),
                    ]),
                Section::make('Contact')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextEntry::make('email')->columnSpan(6),
                        TextEntry::make('phone')->columnSpan(6),
                        TextEntry::make('verification_type')->label('Vérification')->columnSpan(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Testimony::STATUS_APPROVED => 'Approuvé',
                        Testimony::STATUS_REJECTED => 'Refusé',
                        default => 'En attente',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Testimony::STATUS_APPROVED => 'success',
                        Testimony::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('kind')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Testimony::KIND_VIDEO => 'Vidéo',
                        Testimony::KIND_MIX => 'Mixte',
                        default => 'Texte',
                    }),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('first_name')
                    ->label('Auteur')
                    ->formatStateUsing(fn (Testimony $record): string => $record->authorFullName())
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('category')->toggleable(),
                TextColumn::make('email')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Reçu')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('kind')
                    ->label('Type')
                    ->options([
                        Testimony::KIND_TEXT => 'Texte',
                        Testimony::KIND_VIDEO => 'Vidéo',
                        Testimony::KIND_MIX => 'Mixte',
                    ]),
                SelectFilter::make('category')->label('Catégorie'),
            ])
            ->actions([
                ViewAction::make(),
                self::makeApproveAction(),
                self::makeRejectAction(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    self::makeBulkApproveAction(),
                    self::makeBulkRejectAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function makeApproveAction(): Action
    {
        return Action::make('approveTestimony')
            ->label('Approuver')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (Testimony $record): bool => $record->canBeApproved())
            ->requiresConfirmation()
            ->modalHeading('Approuver ce témoignage')
            ->modalDescription('Il sera visible sur le mur public du site.')
            ->action(function (Testimony $record, TestimonyNotificationService $notifications): void {
                $record->update([
                    'status' => Testimony::STATUS_APPROVED,
                    'approved_at' => now(),
                    'rejection_reason' => null,
                ]);
                $notifications->notifyApproved($record->fresh() ?? $record);
                Notification::make()->title('Témoignage approuvé — fidèle notifié par e-mail')->success()->send();
            });
    }

    public static function makeRejectAction(): Action
    {
        return Action::make('rejectTestimony')
            ->label('Refuser')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Testimony $record): bool => $record->canBeRejected())
            ->form([
                Textarea::make('rejection_reason')
                    ->label('Motif du refus (envoyé au fidèle)')
                    ->required()
                    ->rows(4)
                    ->maxLength(2000),
            ])
            ->modalHeading('Refuser ce témoignage')
            ->modalDescription('Le motif sera inclus dans le courriel envoyé au fidèle.')
            ->action(function (Testimony $record, array $data, TestimonyNotificationService $notifications): void {
                $record->update([
                    'status' => Testimony::STATUS_REJECTED,
                    'rejection_reason' => $data['rejection_reason'],
                ]);
                $fresh = $record->fresh() ?? $record;
                $notifications->notifyRejected($fresh);
                Notification::make()->title('Témoignage refusé — fidèle notifié par e-mail')->warning()->send();
            });
    }

    public static function makeBulkApproveAction(): BulkAction
    {
        return BulkAction::make('bulkApproveTestimonies')
            ->label('Approuver')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, TestimonyNotificationService $notifications): void {
                $count = 0;
                foreach ($records as $record) {
                    if ($record->canBeApproved()) {
                        $record->update([
                            'status' => Testimony::STATUS_APPROVED,
                            'approved_at' => now(),
                            'rejection_reason' => null,
                        ]);
                        $notifications->notifyApproved($record->fresh() ?? $record);
                        $count++;
                    }
                }
                Notification::make()
                    ->title("{$count} témoignage(s) approuvé(s) et notifiés")
                    ->success()
                    ->send();
            });
    }

    public static function makeBulkRejectAction(): BulkAction
    {
        return BulkAction::make('bulkRejectTestimonies')
            ->label('Refuser')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $count = 0;
                foreach ($records as $record) {
                    if ($record->canBeRejected()) {
                        $record->update(['status' => Testimony::STATUS_REJECTED]);
                        $count++;
                    }
                }
                Notification::make()
                    ->title("{$count} témoignage(s) refusé(s)")
                    ->warning()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestimonies::route('/'),
            'view' => Pages\ViewTestimony::route('/{record}'),
        ];
    }
}
