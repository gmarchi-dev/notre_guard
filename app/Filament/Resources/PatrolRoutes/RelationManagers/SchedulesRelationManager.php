<?php

namespace App\Filament\Resources\PatrolRoutes\RelationManagers;

use App\Models\PatrolRouteSchedule;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Janelas de execução';

    protected static ?string $modelLabel = 'janela';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('label')
                    ->label('Identificação')
                    ->placeholder('Ronda noturna')
                    ->maxLength(255),
                Toggle::make('active')
                    ->label('Ativa')
                    ->default(true),
                TimePicker::make('window_start')
                    ->label('Início')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('window_end')
                    ->label('Fim')
                    ->seconds(false)
                    ->required(),
                CheckboxList::make('weekdays')
                    ->label('Dias da semana')
                    ->options(PatrolRouteSchedule::WEEKDAYS)
                    ->columns(4)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('label')
                    ->label('Janela')
                    ->placeholder('—'),
                TextColumn::make('window_start')
                    ->label('Início')
                    ->time('H:i'),
                TextColumn::make('window_end')
                    ->label('Fim')
                    ->time('H:i'),
                TextColumn::make('weekdays')
                    ->label('Dias')
                    ->formatStateUsing(fn ($state) => collect((array) $state)
                        ->map(fn ($d) => mb_substr(PatrolRouteSchedule::WEEKDAYS[$d] ?? '', 0, 3))
                        ->join(', ')),
                IconColumn::make('active')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Nova janela'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
