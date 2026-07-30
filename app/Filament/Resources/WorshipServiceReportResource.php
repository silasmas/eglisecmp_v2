<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WorshipServiceReportResource\Pages;
use App\Filament\Widgets\WorshipAttendanceStatsOverviewWidget;
use App\Models\WorshipServiceReport;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Consultation et édition des rapports de présence aux cultes.
 */
class WorshipServiceReportResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = WorshipServiceReport::class;

    protected static ?string $navigationLabel = 'Stats cultes';

    protected static ?string $modelLabel = 'Rapport de culte';

    protected static ?string $pluralModelLabel = 'Rapports de culte';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Rapport')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('service_date')->label('Date du culte')->required()->native(false)->columnSpan(4),
                        Select::make('service_type')
                            ->label('Type de culte')
                            ->options(WorshipServiceReport::typeOptions())
                            ->required()
                            ->columnSpan(4),
                        TextInput::make('attendees_count')
                            ->label('Nombre de participants')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->columnSpan(4),
                        TextInput::make('submitted_by')->label('Saisi par')->columnSpan(6),
                        TextInput::make('phone')->label('Téléphone')->tel()->columnSpan(6),
                        Textarea::make('report_text')->label('Rapport écrit')->rows(6)->required()->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Détail')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('service_date')->label('Date')->date('d/m/Y')->columnSpan(3),
                        TextEntry::make('service_type')
                            ->label('Type')
                            ->formatStateUsing(fn (WorshipServiceReport $record): string => $record->serviceTypeLabel())
                            ->columnSpan(3),
                        TextEntry::make('attendees_count')->label('Participants')->columnSpan(3),
                        TextEntry::make('submitted_by')->label('Saisi par')->placeholder('—')->columnSpan(3),
                        TextEntry::make('phone')->label('Téléphone')->placeholder('—')->columnSpan(4),
                        TextEntry::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i')->columnSpan(4),
                        TextEntry::make('report_text')->label('Rapport')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('service_date', 'desc')
            ->columns([
                TextColumn::make('service_date')->label('Date')->date('d/m/Y')->sortable(),
                TextColumn::make('service_type')
                    ->label('Culte')
                    ->formatStateUsing(fn (WorshipServiceReport $record): string => $record->serviceTypeLabel())
                    ->badge(),
                TextColumn::make('attendees_count')->label('Participants')->sortable(),
                TextColumn::make('submitted_by')->label('Saisi par')->toggleable(),
                TextColumn::make('report_text')->label('Rapport')->limit(50)->wrap(),
                TextColumn::make('created_at')->label('Reçu')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('service_type')
                    ->label('Type')
                    ->options(WorshipServiceReport::typeOptions()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            WorshipAttendanceStatsOverviewWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorshipServiceReports::route('/'),
            'create' => Pages\CreateWorshipServiceReport::route('/create'),
            'view' => Pages\ViewWorshipServiceReport::route('/{record}'),
            'edit' => Pages\EditWorshipServiceReport::route('/{record}/edit'),
        ];
    }
}
