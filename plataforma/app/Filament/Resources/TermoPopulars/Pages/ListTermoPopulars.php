<?php

namespace App\Filament\Resources\TermoPopulars\Pages;

use App\Filament\Resources\TermoPopulars\TermoPopularResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTermoPopulars extends ListRecords
{
    protected static string $resource = TermoPopularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
