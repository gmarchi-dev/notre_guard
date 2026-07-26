<?php

namespace App\Filament\Resources\KeyItems;

use App\Filament\Concerns\ScopedToUnit;
use App\Filament\Resources\KeyItems\Pages\ListKeyItems;
use App\Models\KeyItem;
use App\Models\User;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Cadastro do quadro de chaves. A operação (entregar, receber) fica no painel
 * da portaria, onde a chave está.
 */
class KeyItemResource extends Resource
{
    use ScopedToUnit;

    protected static ?string $model = KeyItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'chave';

    protected static ?string $pluralModelLabel = 'chaves';

    protected static ?int $navigationSort = 6;

    /** Mesma permissão da portaria: quem não opera chaves não as cadastra. */
    public static function canAccess(): bool
    {
        return auth()->user()?->can(User::PERMISSION_KEYS) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('unit_id')
                ->label('Unidade')
                ->relationship('unit', 'name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('code')
                ->label('Gancho / código')
                ->helperText('Uma linha por cópia física: se há duas cópias, cadastre 12A e 12B.')
                ->required()
                ->maxLength(30),
            TextInput::make('name')
                ->label('O que abre')
                ->placeholder('Sala 203, Portão dos fundos, Almoxarifado')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('storage_location')
                ->label('Onde fica guardada')
                ->placeholder('Quadro da portaria, armário 2')
                ->maxLength(255),
            Toggle::make('active')
                ->label('Ativa')
                ->default(true)
                ->helperText('Inativa não aparece no quadro da portaria.'),
            Textarea::make('notes')
                ->label('Observações')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('currentLoan.holder'))
            ->columns([
                TextColumn::make('code')->label('Gancho')->searchable()->sortable()->weight('bold'),
                TextColumn::make('name')->label('Abre')->searchable()->wrap(),
                TextColumn::make('unit.name')->label('Unidade')->sortable(),
                TextColumn::make('storage_location')->label('Guarda')->placeholder('—')->toggleable(),
                TextColumn::make('situacao')
                    ->label('Situação')
                    ->badge()
                    ->state(fn (KeyItem $record) => $record->currentLoan
                        ? 'Com '.$record->currentLoan->holder->name
                        : 'No quadro')
                    ->color(fn (KeyItem $record) => match (true) {
                        ! $record->currentLoan => 'success',
                        $record->currentLoan->isOverdue() => 'danger',
                        default => 'warning',
                    }),
                IconColumn::make('active')->label('Ativa')->boolean(),
            ])
            ->defaultSort('code')
            ->filters([
                SelectFilter::make('unit')->label('Unidade')->relationship('unit', 'name'),
                Filter::make('out')
                    ->label('Fora do quadro')
                    ->query(fn (Builder $query) => $query->out()),
            ])
            ->headerActions([
                CreateAction::make()->label('Nova chave'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKeyItems::route('/'),
        ];
    }
}
