<?php

namespace App\Filament\Resources\RegistroAuditorias;

use App\Filament\Resources\RegistroAuditorias\Pages\ListRegistroAuditorias;
use App\Filament\Resources\RegistroAuditorias\Tables\RegistroAuditoriasTable;
use App\Models\RegistroAuditoria;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Trilha de auditoria das ações da Operação (RF-ADM-10).
 *
 * É a contrapartida do poder que a Operação tem sobre o domínio: ela decide
 * chamados, suspende contas e publica termos — e cada ato fica com autor,
 * momento e motivo.
 *
 * Só inserção e leitura, inclusive aqui: uma trilha que a própria Operação
 * pudesse editar não auditaria coisa alguma.
 */
class RegistroAuditoriaResource extends Resource
{
    protected static ?string $model = RegistroAuditoria::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'trilha de auditoria';

    protected static ?string $navigationLabel = 'Trilha de auditoria';

    protected static ?int $navigationSort = 9;

    protected static string|\UnitEnum|null $navigationGroup = 'Moderação';

    public static function table(Table $table): Table
    {
        return RegistroAuditoriasTable::configure($table);
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
        return ['index' => ListRegistroAuditorias::route('/')];
    }
}
