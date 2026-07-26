<?php

namespace App\Filament\Portaria\Resources\KeyLoans\Pages;

use App\Filament\Portaria\Resources\KeyLoans\KeyLoanResource;
use Filament\Resources\Pages\ListRecords;

class ListKeyLoans extends ListRecords
{
    protected static string $resource = KeyLoanResource::class;

    protected static ?string $title = 'Livro de retiradas';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
