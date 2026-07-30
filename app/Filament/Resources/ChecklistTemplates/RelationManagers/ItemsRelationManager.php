<?php

namespace App\Filament\Resources\ChecklistTemplates\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Itens de verificação';

    protected static ?string $modelLabel = 'item';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->label('Item')
                ->helperText('Redação curta e afirmativa - o vigilante lê isso no escuro.')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('position')
                ->label('Ordem')
                ->numeric()
                ->minValue(1)
                ->default(fn () => $this->getOwnerRecord()->items()->count() + 1)
                ->required(),
            Toggle::make('photo_required_when_nonconforming')
                ->label('Exigir foto quando não conforme')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('position')
                    ->label('#')
                    ->alignCenter(),
                TextColumn::make('label')
                    ->label('Item')
                    ->searchable()
                    ->wrap(),
                IconColumn::make('photo_required_when_nonconforming')
                    ->label('Foto se NC')
                    ->boolean(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->headerActions([
                CreateAction::make()->label('Novo item'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
