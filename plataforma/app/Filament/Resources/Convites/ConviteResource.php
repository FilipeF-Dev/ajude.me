<?php

namespace App\Filament\Resources\Convites;

use App\Filament\Resources\Convites\Pages\ListConvites;
use App\Filament\Resources\Convites\Tables\ConvitesTable;
use App\Models\Convite;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Convites de acesso da base restrita (RF-ADM-08).
 *
 * Pré-requisito do piloto fechado (06 §5.2). Emitir é ato próprio, em lote,
 * na ação do cabeçalho: é assim que o piloto funciona — a Operação prepara um
 * punhado antes de ir a campo, e não um por vez enquanto conversa com alguém.
 *
 * Não se edita um convite: o código é o que foi entregue, e mudá-lo depois
 * invalidaria o papel na mão de quem o recebeu.
 */
class ConviteResource extends Resource
{
    protected static ?string $model = Convite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $modelLabel = 'convite';

    protected static ?string $pluralModelLabel = 'convites';

    protected static ?string $navigationLabel = 'Convites';

    protected static ?int $navigationSort = 1;

    protected static string|\UnitEnum|null $navigationGroup = 'Piloto';

    /** Quantos ainda podem ser entregues. */
    public static function getNavigationBadge(): ?string
    {
        $disponiveis = static::getModel()::query()
            ->whereNull('usado_em')
            ->whereNull('revogado_em')
            ->where(fn ($consulta) => $consulta
                ->whereNull('expira_em')
                ->orWhere('expira_em', '>', now()))
            ->count();

        return $disponiveis > 0 ? (string) $disponiveis : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function table(Table $table): Table
    {
        return ConvitesTable::configure($table);
    }

    /** Emitir é ação de lote, no cabeçalho da lista. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** O código é o que foi entregue; editá-lo invalidaria o que está na mão. */
    public static function canEdit($record): bool
    {
        return false;
    }

    /** P3 — quem entrou por qual convite não se apaga. */
    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListConvites::route('/')];
    }
}
