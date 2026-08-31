<?php

namespace App\Filament\Resources\Chamados;

use App\Filament\Resources\Chamados\Pages\ListChamados;
use App\Filament\Resources\Chamados\Pages\VerChamado;
use App\Filament\Resources\Chamados\Schemas\ChamadoInfolist;
use App\Filament\Resources\Chamados\Tables\ChamadosTable;
use App\Models\Chamado;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Fila de chamados abertos por contestação (RF-ADM-11 a RF-ADM-14).
 *
 * O chamado **não é criado nem editado** pela Operação: nasce da contestação
 * de uma parte (RF-SOL-12) e sai daqui por uma decisão fundamentada. Por
 * isso não há formulário — há uma fila, uma tela de apuração e um ato de
 * decidir.
 *
 * > **Nota terminológica:** *ticket* é o direito de receber um favor. O
 * > registro de atendimento da Operação chama-se **chamado** (02 §8.5).
 */
class ChamadoResource extends Resource
{
    protected static ?string $model = Chamado::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $modelLabel = 'chamado';

    protected static ?string $pluralModelLabel = 'chamados';

    protected static ?string $navigationLabel = 'Chamados';

    protected static ?int $navigationSort = 1;

    protected static string|\UnitEnum|null $navigationGroup = 'Moderação';

    /** O número de abertos na navegação: é fila, e fila se mede. */
    public static function getNavigationBadge(): ?string
    {
        $abertos = static::getModel()::where('estado', Chamado::ABERTO)->count();

        return $abertos > 0 ? (string) $abertos : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChamadoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChamadosTable::configure($table);
    }

    /** RF-SOL-12 — só a contestação abre chamado. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** A decisão é ato próprio, com fundamento; não é edição de campo. */
    public static function canEdit($record): bool
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
            'index' => ListChamados::route('/'),
            'view' => VerChamado::route('/{record}'),
        ];
    }
}
