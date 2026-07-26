<?php

namespace App\Filament\Portaria\Resources\KeyHolders;

use App\Filament\Portaria\Resources\KeyHolders\Pages\ListKeyHolders;
use App\Models\KeyHolder;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class KeyHolderResource extends Resource
{
    protected static ?string $model = KeyHolder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'solicitante';

    protected static ?string $pluralModelLabel = 'solicitantes';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Select::make('kind')
                ->label('Vínculo')
                ->options(KeyHolder::KINDS)
                ->default('staff')
                ->required(),
            TextInput::make('department')
                ->label('Setor')
                ->maxLength(255),
            TextInput::make('document')
                ->label('Documento')
                ->maxLength(30),
            TextInput::make('phone')
                ->label('Telefone')
                ->tel()
                ->maxLength(20),
            Textarea::make('notes')
                ->label('Observações')
                ->rows(2)
                ->columnSpanFull(),
            Toggle::make('active')
                ->label('Ativo')
                ->default(true)
                ->helperText('Inativo não aparece na lista de retirada.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kind')
                    ->label('Vínculo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => KeyHolder::KINDS[$state] ?? $state),
                TextColumn::make('department')
                    ->label('Setor')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('open_loans_count')
                    ->label('Chaves em mãos')
                    ->counts('openLoans')
                    ->alignCenter()
                    ->color(fn (int $state) => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('phone')
                    ->label('Telefone')
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('kind')->label('Vínculo')->options(KeyHolder::KINDS),
                TernaryFilter::make('active')->label('Ativo'),
            ])
            ->headerActions([
                CreateAction::make()->label('Novo solicitante'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKeyHolders::route('/'),
        ];
    }
}
