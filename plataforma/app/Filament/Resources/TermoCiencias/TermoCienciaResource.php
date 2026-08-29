<?php

namespace App\Filament\Resources\TermoCiencias;

use App\Filament\Resources\TermoCiencias\Pages\ListTermoCiencias;
use App\Filament\Resources\TermoCiencias\Tables\TermoCienciasTable;
use App\Models\TermoCiencia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Termo de ciência de riscos (RF-ADM-07).
 *
 * > **Termo de ciência é documento, e documento não socorre ninguém.**
 *
 * Ele cobre o risco jurídico da plataforma. O risco de o evento acontecer é
 * coberto pela curadoria de categorias e pelo registro de comparecimento, e
 * a existência do termo não substitui nenhum dos dois (02 §8.3).
 *
 * O texto de uma versão publicada **nunca muda**: publicar uma redação nova
 * encerra a anterior e cria outra versão. Quem aceitou a versão 2 aceitou
 * aquele texto, e é isso que RF-SEG-04 exige demonstrar depois.
 */
class TermoCienciaResource extends Resource
{
    protected static ?string $model = TermoCiencia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $modelLabel = 'termo de ciência';

    protected static ?string $pluralModelLabel = 'termos de ciência';

    protected static ?string $navigationLabel = 'Termo de ciência';

    protected static ?int $navigationSort = 4;

    protected static string|\UnitEnum|null $navigationGroup = 'Moderação';

    public static function table(Table $table): Table
    {
        return TermoCienciasTable::configure($table);
    }

    /** Publicar é ato próprio, na ação do cabeçalho da lista. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** Versão publicada é imutável — publicar de novo é criar outra. */
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
        return ['index' => ListTermoCiencias::route('/')];
    }
}
