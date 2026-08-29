<?php

namespace App\Filament\Resources\Ocorrencias;

use App\Filament\Resources\Ocorrencias\Pages\ListOcorrencias;
use App\Filament\Resources\Ocorrencias\Tables\OcorrenciasTable;
use App\Models\Ocorrencia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Ocorrências no histórico dos usuários (RF-ADM-03, RF-CAN-11).
 *
 * Só leitura. A ocorrência é consequência de um ato — cancelamento, decisão
 * de chamado, relato de uma parte — e não algo que a Operação lança à mão:
 * criar ocorrência por fora contornaria a regra que a produz.
 *
 * Nunca é removida (P3): é o que permite reconhecer comportamento recorrente
 * meses depois.
 */
class OcorrenciaResource extends Resource
{
    protected static ?string $model = Ocorrencia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $modelLabel = 'ocorrência';

    protected static ?string $pluralModelLabel = 'ocorrências';

    protected static ?string $navigationLabel = 'Ocorrências';

    protected static ?int $navigationSort = 3;

    protected static string|\UnitEnum|null $navigationGroup = 'Moderação';

    public static function table(Table $table): Table
    {
        return OcorrenciasTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListOcorrencias::route('/')];
    }
}
