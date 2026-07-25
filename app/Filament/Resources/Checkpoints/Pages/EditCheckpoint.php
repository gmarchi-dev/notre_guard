<?php

namespace App\Filament\Resources\Checkpoints\Pages;

use App\Filament\Resources\Checkpoints\CheckpointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCheckpoint extends EditRecord
{
    protected static string $resource = CheckpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
