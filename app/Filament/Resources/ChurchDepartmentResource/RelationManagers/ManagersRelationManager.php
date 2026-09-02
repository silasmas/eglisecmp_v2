<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchDepartmentResource\RelationManagers;

use App\Models\ChurchDepartment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Gestion des responsables d’un département.
 */
class ManagersRelationManager extends RelationManager
{
    protected static string $relationship = 'managers';

    protected static ?string $title = 'Responsables';

    protected static ?string $recordTitleAttribute = 'full_name';

    /**
     * Autorise la relation si l’utilisateur peut modifier le département.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('update', $ownerRecord) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                TextInput::make('full_name')->label('Nom complet')->required()->columnSpan(6),
                TextInput::make('phone')->label('Téléphone')->tel()->maxLength(60)->columnSpan(3),
                TextInput::make('email')->label('E-mail')->email()->maxLength(160)->columnSpan(3),
                Toggle::make('is_primary')->label('Responsable principal')->inline(false)->columnSpan(3),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->columnSpan(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('full_name')->label('Nom')->searchable(),
                TextColumn::make('phone')->label('Tél.')->placeholder('—'),
                TextColumn::make('email')->label('E-mail')->placeholder('—'),
                IconColumn::make('is_primary')->label('Principal')->boolean(),
                TextColumn::make('user.name')->label('Compte')->placeholder('—'),
                TextColumn::make('sort_order')->label('Ordre'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter un responsable'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    protected function canCreate(): bool
    {
        return $this->canModifyOwner();
    }

    protected function canEdit(Model $record): bool
    {
        return $this->canModifyOwner();
    }

    protected function canDelete(Model $record): bool
    {
        return $this->canModifyOwner();
    }

    protected function canDeleteAny(): bool
    {
        return $this->canModifyOwner();
    }

    /**
     * Vérifie le droit de mise à jour sur le département parent.
     */
    private function canModifyOwner(): bool
    {
        $owner = $this->getOwnerRecord();

        return $owner instanceof ChurchDepartment
            && (auth()->user()?->can('update', $owner) ?? false);
    }
}
