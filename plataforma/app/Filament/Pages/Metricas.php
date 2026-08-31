<?php

namespace App\Filament\Pages;

use App\Dominio\Metricas\ApurarMetricas;
use BackedEnum;
use Filament\Pages\Page;

/**
 * Painel de métricas da Operação (03 §7, 06 §10).
 *
 * Fecha a última condição do portão da Fase 1 (06 §5.4): "as métricas são
 * apuráveis a partir dos eventos". Sem isto, o piloto da Fase 2 começaria
 * cego justamente nas três coisas que ele existe para medir.
 *
 * Só lê. Nenhuma decisão da Operação acontece aqui.
 */
class Metricas extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Métricas';

    protected static ?string $title = 'Métricas do piloto';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.metricas';

    public int $meses = 6;

    /** @return array<string, mixed> */
    public function getViewData(): array
    {
        $apurador = new ApurarMetricas();

        return [
            'panorama' => $apurador->panorama(),
            'coortes' => $apurador->coortes($this->meses),
        ];
    }
}
