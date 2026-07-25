<?php

namespace App\Filament\Resources\Units\RelationManagers;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    protected static ?string $title = 'Postos de serviço';

    protected static ?string $modelLabel = 'posto';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nome do posto')
                    ->required()
                    ->maxLength(255),
                Select::make('kind')
                    ->label('Tipo')
                    ->options(Post::KINDS)
                    ->default('fixed')
                    ->required(),
                TextInput::make('latitude')
                    ->label('Latitude')
                    ->numeric()
                    ->step(0.0000001),
                TextInput::make('longitude')
                    ->label('Longitude')
                    ->numeric()
                    ->step(0.0000001),
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
                TextColumn::make('name')
                    ->label('Posto')
                    ->searchable(),
                TextColumn::make('kind')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state) => Post::KINDS[$state] ?? $state)
                    ->badge(),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Novo posto'),
            ])
            ->recordActions([
                Action::make('qrcode')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn (Post $record) => route('qr.post', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }
}
