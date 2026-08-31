<?php

namespace App\Filament\Resources\Chamados\Tables;

use App\Models\Chamado;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChamadosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Aberto primeiro, e dentro dele o mais antigo antes: fila é
            // ordem de chegada, não de conveniência.
            ->defaultSort('aberto_em', 'asc')
            // O parâmetro precisa chamar-se `$query`: é assim que o Filament
            // o injeta.
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->orderByRaw('CASE WHEN estado = ? THEN 0 ELSE 1 END', [Chamado::ABERTO]))
            ->columns([
                TextColumn::make('id')
                    ->label('Nº')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Chamado::ABERTO => 'Aberto',
                        Chamado::DECIDIDO => 'Decidido',
                    })
                    ->color(fn (string $state) => $state === Chamado::ABERTO ? 'warning' : 'gray'),

                TextColumn::make('solicitacao.ajudante.nome')
                    ->label('Ajudante')
                    ->searchable(),

                TextColumn::make('solicitacao.solicitante.nome')
                    ->label('Solicitante')
                    ->searchable(),

                TextColumn::make('abertoPor.nome')
                    ->label('Contestou')
                    ->searchable(),

                TextColumn::make('aberto_em')
                    ->label('Aberto em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('manifestacao_ate')
                    ->label('Prazo das partes')
                    ->dateTime('d/m/Y H:i')
                    // RF-SOL-15 — o prazo esgotado não trava a análise; só
                    // deixa de haver o que esperar.
                    ->description(fn (Chamado $registro) => $registro->prazoDeManifestacaoEncerrou()
                        ? 'encerrado'
                        : 'em curso')
                    ->color(fn (Chamado $registro) => $registro->prazoDeManifestacaoEncerrou()
                        ? 'gray'
                        : 'warning')
                    ->sortable(),

                TextColumn::make('decisao')
                    ->label('Decisão')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        Chamado::REALIZADO => 'Realizado',
                        Chamado::NAO_REALIZADO => 'Não realizado',
                        Chamado::INCONCLUSIVO => 'Inconclusivo',
                        default => '—',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        Chamado::REALIZADO => 'success',
                        Chamado::NAO_REALIZADO => 'danger',
                        Chamado::INCONCLUSIVO => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Situação')
                    ->options([
                        Chamado::ABERTO => 'Aberto',
                        Chamado::DECIDIDO => 'Decidido',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Apurar'),
            ]);
    }
}
