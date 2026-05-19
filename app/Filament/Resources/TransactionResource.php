<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

class TransactionResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Paiements';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('reference')->required()->maxLength(255),
            Select::make('offrande_id')
                ->label('Offrande')
                ->relationship('offrande', 'nom')
                ->required()
                ->searchable()
                ->preload(),
            TextInput::make('montant')->numeric(),
            TextInput::make('currency')->maxLength(10),
            TextInput::make('phone')->maxLength(50),
            TextInput::make('fullname')->maxLength(250),
            TextInput::make('etat')->maxLength(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Reference')->searchable(),
                TextColumn::make('offrande.nom')
                    ->label('Offrande')
                    ->searchable()
                    ->formatStateUsing(fn ($state): string => static::getTranslatedState($state)),
                TextColumn::make('montant')->label('Montant')->numeric(2),
                TextColumn::make('currency')->label('Devise'),
                TextColumn::make('phone')->label('Telephone'),
                TextColumn::make('etat')->label('Etat')->badge(),
                TextColumn::make('created_at')->label('Date')->dateTime()->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    protected static function getTranslatedState(mixed $state): string
    {
        if (! is_string($state) || blank($state)) {
            return (string) $state;
        }

        $decoded = json_decode($state, true);

        if (! is_array($decoded)) {
            return $state;
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return (string) ($decoded[$locale]
            ?? $decoded[$fallback]
            ?? collect($decoded)->first(fn ($item): bool => filled($item))
            ?? '');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
