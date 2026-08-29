<?php

namespace App\Filament\Resources\TermoCiencias\Tables;

use App\Models\TermoCiencia;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TermoCienciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('versao', 'desc')
            ->columns([
                TextColumn::make('versao')->label('Versão')->sortable(),

                TextColumn::make('vigencia')
                    ->label('Situação')
                    ->badge()
                    ->state(fn (TermoCiencia $r) => $r->encerrado_em === null ? 'Vigente' : 'Encerrado')
                    ->color(fn (TermoCiencia $r) => $r->encerrado_em === null ? 'success' : 'gray'),

                TextColumn::make('texto')->label('Texto')->wrap()->limit(180),

                TextColumn::make('publicado_em')->label('Publicado em')->dateTime('d/m/Y H:i'),

                TextColumn::make('publicadoPor.nome')->label('Por')->placeholder('—'),

                // RF-SEG-04 — quantos aceitaram esta versão. É o que sustenta
                // a finalidade probatória do termo.
                TextColumn::make('aceites_count')
                    ->label('Aceites')
                    ->counts('aceites'),
            ]);
    }
}
