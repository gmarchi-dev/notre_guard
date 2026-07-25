<?php

namespace App\Filament\Resources\Checkpoints\Pages;

use App\Filament\Resources\Checkpoints\CheckpointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCheckpoints extends ListRecords
{
    protected static string $resource = CheckpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
