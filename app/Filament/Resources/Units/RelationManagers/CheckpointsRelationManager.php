<?php

namespace App\Filament\Resources\Units\RelationManagers;

use App\Models\Checkpoint;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

    protected static ?string $title = 'Pontos de controle';

    protected static ?string $modelLabel = 'ponto de controle';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')
                    ->label('Código')
                    ->helperText('Curto e legível, ex.: PC-01. Impresso junto ao QR Code.')
                    ->required()
                    ->maxLength(30),
                TextInput::make('name')
                    ->label('Nome / local')
                    ->required()
                    ->maxLength(255),
                TextInput::make('latitude')
                    ->label('Latitude')
                    ->numeric()
                    ->step(0.0000001),
                TextInput::make('longitude')
                    ->label('Longitude')
                    ->numeric()
                    ->step(0.0000001),
                TextInput::make('radius_m')
                    ->label('Raio de tolerância (m)')
                    ->helperText('Leitura fora do raio não é recusada - é registrada como desvio.')
                    ->numeric()
                    ->minValue(5)
                    ->default(50)
                    ->required(),
                Select::make('checklist_template_id')
                    ->label('Checklist na passagem')
                    ->relationship('checklistTemplate', 'name')
                    ->searchable()
                    ->preload(),
                Textarea::make('instruction')
                    ->label('Instrução ao vigilante')
                    ->helperText('Exibida na tela no momento da leitura.')
                    ->rows(2)
                    ->columnSpanFull(),
                TextInput::make('nfc_uid')
                    ->label('UID da tag NFC')
                    ->helperText('Opcional. Só funciona em Android/Chrome.')
                    ->maxLength(64),
                Toggle::make('active')
                    ->label('Ativo')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Ponto')
                    ->searchable(),
                TextColumn::make('radius_m')
                    ->label('Raio')
                    ->suffix(' m')
                    ->alignCenter(),
                TextColumn::make('checklistTemplate.name')
                    ->label('Checklist')
                    ->placeholder('—'),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->defaultSort('code')
            ->headerActions([
                CreateAction::make()->label('Novo ponto'),
            ])
            ->recordActions([
                Action::make('qrcode')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn (Checkpoint $record) => route('qr.checkpoint', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }
}
