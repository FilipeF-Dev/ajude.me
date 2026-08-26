<?php

namespace App\Filament\Resources\Proficiencias\Pages;

use App\Filament\Resources\Proficiencias\ProficienciaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProficiencias extends ListRecords
{
    protected static string $resource = ProficienciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
