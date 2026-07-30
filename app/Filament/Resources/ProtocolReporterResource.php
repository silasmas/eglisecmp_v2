<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProtocolReporterResource\Pages;
use App\Models\ProtocolReporter;
use App\Services\PhoneOtpService;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Gestion des numéros autorisés à saisir les rapports de culte.
 */
class ProtocolReporterResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = ProtocolReporter::class;

    protected static ?string $navigationLabel = 'Équipe protocole';

    protected static ?string $modelLabel = 'Rapporteur protocole';

    protected static ?string $pluralModelLabel = 'Équipe protocole';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Rapporteur')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->columnSpan(6),
                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->required()
                            ->helperText('Format local ou international (ex. 0812345678).')
                            ->dehydrateStateUsing(function (?string $state): string {
                                return app(PhoneOtpService::class)->normalizePhone((string) $state);
                            })
                            ->columnSpan(4),
                        Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->columnSpan(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('phone')->label('Téléphone')->searchable(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('updated_at')->label('Mis à jour')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Actif'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProtocolReporters::route('/'),
            'create' => Pages\CreateProtocolReporter::route('/create'),
            'edit' => Pages\EditProtocolReporter::route('/{record}/edit'),
        ];
    }
}
