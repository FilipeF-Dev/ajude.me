<?php

namespace App\Filament\Resources\Denuncias;

use App\Filament\Resources\Denuncias\Pages\ListDenuncias;
use App\Filament\Resources\Denuncias\Tables\DenunciasTable;
use App\Models\Denuncia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Fila de denúncias (RF-ADM-04, RF-BLO-08).
 *
 * Diferente do bloqueio, que é decisão individual e não pede nada à
 * plataforma (RF-BLO-05): denunciar aciona a Operação, e por isso a denúncia
 * tem estado e desfecho.
 */
class DenunciaResource extends Resource
{
    protected static ?string $model = Denuncia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $modelLabel = 'denúncia';

    protected static ?string $pluralModelLabel = 'denúncias';

    protected static ?string $navigationLabel = 'Denúncias';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = 'Moderação';

    public static function getNavigationBadge(): ?string
    {
        $abertas = static::getModel()::where('estado', Denuncia::ABERTA)->count();

        return $abertas > 0 ? (string) $abertas : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return DenunciasTable::configure($table);
    }

    /** A denúncia é registrada pelo usuário, nunca pela Operação. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** P3 — o histórico não é apagado. */
    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDenuncias::route('/'),
        ];
    }
}
