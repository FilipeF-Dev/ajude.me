<?php

namespace App\Filament\Resources\RegistroAuditorias\Tables;

use App\Models\RegistroAuditoria;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RegistroAuditoriasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('ocorrido_em', 'desc')
            ->columns([
                TextColumn::make('ocorrido_em')
                    ->label('Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('operador.nome')
                    ->label('Quem')
                    ->placeholder('— (operador removido)')
                    ->searchable(),

                TextColumn::make('acao')
                    ->label('Ação')
                    ->badge()
                    ->searchable(),

                TextColumn::make('alvo')
                    ->label('Alvo')
                    ->state(fn (RegistroAuditoria $r) => class_basename($r->alvo_tipo)." nº {$r->alvo_id}"),

                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->wrap()
                    ->limit(160)
                    ->placeholder('—'),
            ]);
    }
}
