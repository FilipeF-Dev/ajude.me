<?php

namespace App\Filament\Resources\RegistroAuditorias\Pages;

use App\Filament\Resources\RegistroAuditorias\RegistroAuditoriaResource;
use Filament\Resources\Pages\ListRecords;

class ListRegistroAuditorias extends ListRecords
{
    protected static string $resource = RegistroAuditoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
