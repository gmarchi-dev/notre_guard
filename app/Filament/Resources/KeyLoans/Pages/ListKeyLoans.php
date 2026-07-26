<?php

namespace App\Filament\Resources\KeyLoans\Pages;

use App\Filament\Resources\KeyLoans\KeyLoanResource;
use Filament\Resources\Pages\ListRecords;

class ListKeyLoans extends ListRecords
{
    protected static string $resource = KeyLoanResource::class;

    protected static ?string $title = 'Retiradas de chave';
}
