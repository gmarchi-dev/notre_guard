<?php

namespace App\Filament\Resources\PatrolRoutes\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CheckpointsRelationManager extends RelationManager
{
    protected static string $relationship = 'checkpoints';

    protected static ?string $title = 'Pontos do roteiro';

    protected static ?string $modelLabel = 'ponto';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('position')
                ->label('Ordem')
                ->numeric()
                ->minValue(1)
                ->required(),
            Toggle::make('required')
                ->label('Obrigatório')
                ->helperText('Pontos não obrigatórios não contam como falta quando pulados.')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('position')
                    ->label('Ordem')
                    ->state(fn ($record) => $record->pivot->position)
                    ->alignCenter(),
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Ponto')
                    ->searchable(),
                IconColumn::make('required')
                    ->label('Obrigatório')
                    ->state(fn ($record) => (bool) $record->pivot->required)
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Adicionar ponto')
                    ->recordSelectSearchColumns(['code', 'name'])
                    // Só pontos da mesma unidade do roteiro fazem sentido aqui.
                    ->recordSelectOptionsQuery(
                        fn ($query) => $query->where('unit_id', $this->getOwnerRecord()->unit_id)
                    )
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('position')
                            ->label('Ordem')
                            ->numeric()
                            ->minValue(1)
                            ->default(fn () => $this->getOwnerRecord()->checkpoints()->count() + 1)
                            ->required(),
                        Toggle::make('required')->label('Obrigatório')->default(true),
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label('Ordem'),
                DetachAction::make()->label('Remover'),
            ]);
    }
}
