<?php

declare(strict_types=1);

namespace App\Filament\Resources\TestimonyResource\Pages;

use App\Filament\Resources\TestimonyResource;
use App\Models\Testimony;
use App\Models\TestimonyWallSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Liste des témoignages avec onglets par statut de modération.
 */
class ListTestimonies extends ListRecords
{
    protected static string $resource = TestimonyResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            TestimonyResource::LIST_TAB_ALL => Tab::make('Tous')
                ->icon('heroicon-o-queue-list')
                ->badge(fn (): int => Testimony::query()->count()),
            TestimonyResource::LIST_TAB_PENDING => Tab::make('En attente')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', Testimony::STATUS_PENDING),
                )
                ->badge(
                    fn (): int => Testimony::query()->where('status', Testimony::STATUS_PENDING)->count(),
                ),
            TestimonyResource::LIST_TAB_APPROVED => Tab::make('Approuvés')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', Testimony::STATUS_APPROVED),
                )
                ->badge(
                    fn (): int => Testimony::query()->where('status', Testimony::STATUS_APPROVED)->count(),
                ),
            TestimonyResource::LIST_TAB_REJECTED => Tab::make('Refusés')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', Testimony::STATUS_REJECTED),
                )
                ->badge(
                    fn (): int => Testimony::query()->where('status', Testimony::STATUS_REJECTED)->count(),
                ),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return TestimonyResource::LIST_TAB_ALL;
    }

    protected function getHeaderActions(): array
    {
        $settings = TestimonyWallSetting::current();

        return [
            Action::make('wallSettings')
                ->label('Réglages du mur')
                ->icon('heroicon-o-cog-6-tooth')
                ->form([
                    Toggle::make('allow_photo_upload')
                        ->label('Autoriser les photos à la soumission')
                        ->default(fn (): bool => $settings->allow_photo_upload),
                    TextInput::make('max_photos_per_testimony')
                        ->label('Nombre max. de photos par témoignage')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(fn (): int => (int) $settings->max_photos_per_testimony)
                        ->visible(fn ($get): bool => (bool) $get('allow_photo_upload')),
                    Toggle::make('allow_youtube_link')
                        ->label('Autoriser un lien vidéo YouTube')
                        ->default(fn (): bool => $settings->allow_youtube_link),
                    Toggle::make('allow_video_upload')
                        ->label('Autoriser l’upload d’un fichier vidéo')
                        ->default(fn (): bool => $settings->allow_video_upload),
                    TextInput::make('max_video_upload_mb')
                        ->label('Taille max. du fichier vidéo (Mo)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->default(fn (): int => (int) $settings->max_video_upload_mb)
                        ->suffix('Mo')
                        ->visible(fn ($get): bool => (bool) $get('allow_video_upload')),
                    Toggle::make('allow_anonymous')
                        ->label('Autoriser la publication en anonyme')
                        ->default(fn (): bool => $settings->allow_anonymous),
                    Toggle::make('require_first_name')
                        ->label('Prénom obligatoire (hors anonyme)')
                        ->default(fn (): bool => $settings->require_first_name),
                    Toggle::make('require_last_name')
                        ->label('Nom obligatoire (hors anonyme)')
                        ->default(fn (): bool => $settings->require_last_name),
                ])
                ->action(function (array $data): void {
                    $current = TestimonyWallSetting::current();
                    $current->update([
                        'allow_photo_upload' => (bool) ($data['allow_photo_upload'] ?? false),
                        'max_photos_per_testimony' => max(1, min(20, (int) ($data['max_photos_per_testimony'] ?? 5))),
                        'allow_youtube_link' => (bool) ($data['allow_youtube_link'] ?? false),
                        'allow_video_upload' => (bool) ($data['allow_video_upload'] ?? false),
                        'max_video_upload_mb' => max(1, min(100, (int) ($data['max_video_upload_mb'] ?? 5))),
                        'allow_anonymous' => (bool) ($data['allow_anonymous'] ?? false),
                        'require_first_name' => (bool) ($data['require_first_name'] ?? false),
                        'require_last_name' => (bool) ($data['require_last_name'] ?? false),
                    ]);
                    Notification::make()->title('Réglages enregistrés')->success()->send();
                }),
        ];
    }
}
