<?php

namespace App\Filament\Resources\ChecklistTemplates;

use App\Filament\Concerns\ScopedToUnit;
use App\Filament\Resources\ChecklistTemplates\Pages\CreateChecklistTemplate;
use App\Filament\Resources\ChecklistTemplates\Pages\EditChecklistTemplate;
use App\Filament\Resources\ChecklistTemplates\Pages\ListChecklistTemplates;
use App\Filament\Resources\ChecklistTemplates\Schemas\ChecklistTemplateForm;
use App\Filament\Resources\ChecklistTemplates\Tables\ChecklistTemplatesTable;
use App\Models\ChecklistTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ChecklistTemplateResource extends Resource
{
    use ScopedToUnit;

    protected static ?string $model = ChecklistTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Configuração';

    protected static ?string $modelLabel = 'checklist';

    protected static ?string $pluralModelLabel = 'checklists';

    protected static ?int $navigationSort = 1;

    /**
     * Checklist com unit_id nulo é modelo global, válido para todas as unidades -
     * o gestor precisa enxergá-lo junto com os da própria unidade.
     */
    protected static function applyUnitScope(Builder $query, int $unitId): Builder
    {
        return $query->where(
            fn (Builder $q) => $q->where('unit_id', $unitId)->orWhereNull('unit_id'),
        );
    }

    public static function form(Schema $schema): Schema
    {
        return ChecklistTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChecklistTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChecklistTemplates::route('/'),
            'create' => CreateChecklistTemplate::route('/create'),
            'edit' => EditChecklistTemplate::route('/{record}/edit'),
        ];
    }
}
