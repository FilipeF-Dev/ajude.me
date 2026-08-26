<?php

namespace App\Filament\Resources\Proficiencias\Pages;

use App\Filament\Resources\Proficiencias\ProficienciaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProficiencia extends EditRecord
{
    protected static string $resource = ProficienciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
