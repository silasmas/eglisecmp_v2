<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestPastoralProjectResource\RelationManagers;

use App\Models\GuestInvitationLetter;
use App\Models\GuestPastoralProject;
use App\Models\User;
use App\Services\GuestInvitationLetterService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Modèle de lettre d’invitation PDF (projet) et overrides pasteurs.
 */
class InvitationLettersRelationManager extends RelationManager
{
    protected static string $relationship = 'invitationLetters';

    protected static ?string $title = 'Lettres d’invitation PDF';

    protected static ?string $recordTitleAttribute = 'recipient_title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Select::make('guest_pastor_id')
                    ->label('Pasteur (vide = modèle projet)')
                    ->options(fn (): array => $this->getOwnerRecord() instanceof GuestPastoralProject
                        ? $this->getOwnerRecord()->guestPastors()->orderBy('full_name')->pluck('full_name', 'id')->all()
                        : [])
                    ->searchable()
                    ->nullable()
                    ->helperText('Laissez vide pour le modèle commun à tous les pasteurs.')
                    ->columnSpan(6),
                TextInput::make('recipient_title')
                    ->label('Titre destinataire')
                    ->helperText('Ex. : À l’apôtre Yvan CASTANOU — placeholders : {titre_nom}')
                    ->columnSpan(6),
                RichEditor::make('body_html')
                    ->label('Corps de la lettre')
                    ->columnSpanFull()
                    ->helperText('Placeholders : {titre_nom} · {projet} · {theme} · {dates} · {signature}'),
                RichEditor::make('signature_html')
                    ->label('Signature')
                    ->columnSpanFull(),
                FileUpload::make('header_image_path')
                    ->label('Image d’en-tête (optionnel)')
                    ->image()
                    ->disk('public')
                    ->directory('guest-letters/headers')
                    ->visibility('public')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scope')
                    ->label('Portée')
                    ->state(fn (GuestInvitationLetter $record): string => $record->guest_pastor_id === null
                        ? 'Modèle projet'
                        : ('Override — '.$record->guestPastor?->full_name)),
                TextColumn::make('recipient_title')->label('Destinataire')->placeholder('—')->limit(40),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('generated_at')->label('PDF généré')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter modèle / override'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('generatePdf')
                    ->label('Générer PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (GuestInvitationLetter $record): void {
                        $user = auth()->user();
                        $path = app(GuestInvitationLetterService::class)->generatePdf(
                            $record,
                            $user instanceof User ? $user : null,
                        );
                        Notification::make()
                            ->title('PDF généré')
                            ->body($path)
                            ->success()
                            ->send();
                    }),
                Action::make('downloadPdf')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (GuestInvitationLetter $record): ?string => $record->pdfPublicUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (GuestInvitationLetter $record): bool => filled($record->pdf_path)),
                DeleteAction::make(),
            ]);
    }
}
