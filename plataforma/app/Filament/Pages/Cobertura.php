<?php

namespace App\Filament\Pages;

use App\Dominio\Metricas\ApurarCobertura;
use BackedEnum;
use Filament\Pages\Page;

/**
 * Vocabulário e cobertura (RF-ADM-16, RF-ADM-17, RF-ADM-18).
 *
 * Uma página, duas listas que nunca devem ser somadas: falta de palavra e
 * falta de gente pedem ações opostas. Ver ApurarCobertura.
 */
class Cobertura extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Vocabulário e cobertura';

    protected static ?string $title = 'O que falta na rede';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.cobertura';

    /** @return array<string, mixed> */
    public function getViewData(): array
    {
        $apurador = new ApurarCobertura();

        return [
            'vocabulario' => $apurador->vocabularioFaltante(),
            'cobertura' => $apurador->coberturaFaltante(),
        ];
    }
}
