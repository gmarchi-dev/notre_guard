<?php

namespace App\Filament\Resources\DailyReports;

use App\Filament\Concerns\ScopedToUnit;
use App\Filament\Resources\DailyReports\Pages\EditDailyReport;
use App\Filament\Resources\DailyReports\Pages\ListDailyReports;
use App\Filament\Resources\DailyReports\Schemas\DailyReportForm;
use App\Filament\Resources\DailyReports\Tables\DailyReportsTable;
use App\Models\DailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DailyReportResource extends Resource
{
    use ScopedToUnit;

    protected static ?string $model = DailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Operação';

    protected static ?string $modelLabel = 'RDO';

    protected static ?string $pluralModelLabel = 'RDOs';

    protected static ?int $navigationSort = 0;

    /**
     * O RDO não é criado por formulário: ele é gerado a partir do que já foi
     * registrado em campo, pela ação "Gerar RDO" na listagem.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return DailyReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DailyReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDailyReports::route('/'),
            'edit' => EditDailyReport::route('/{record}/edit'),
        ];
    }
}
