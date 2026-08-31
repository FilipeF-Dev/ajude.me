<?php

namespace App\Filament\Resources\Chamados\Pages;

use App\Filament\Resources\Chamados\ChamadoResource;
use Filament\Resources\Pages\ListRecords;

/**
 * A fila. Sem ação de criar: chamado nasce de contestação (RF-SOL-12).
 */
class ListChamados extends ListRecords
{
    protected static string $resource = ChamadoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
