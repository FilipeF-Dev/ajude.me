<?php

namespace App\Filament\Resources\TermoPopulars\Pages;

use App\Filament\Resources\TermoPopulars\TermoPopularResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTermoPopular extends EditRecord
{
    protected static string $resource = TermoPopularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
