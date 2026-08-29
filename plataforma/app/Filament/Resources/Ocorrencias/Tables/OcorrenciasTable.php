<?php

namespace App\Filament\Resources\Ocorrencias\Tables;

use App\Models\Ocorrencia;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OcorrenciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('registrada_em', 'desc')
            ->columns([
                TextColumn::make('registrada_em')
                    ->label('Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('contraUsuario.nome')
                    ->label('Contra')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Ocorrencia::CANCELAMENTO_DO_AJUDANTE => 'Cancelou (ajudante)',
                        Ocorrencia::CANCELAMENTO_EM_CIMA_DA_HORA => 'Cancelou em cima da hora',
                        Ocorrencia::CONTESTACAO_SEM_FUNDAMENTO => 'Contestação sem fundamento',
                        Ocorrencia::FAVOR_NAO_REALIZADO => 'Favor não realizado',
                        Ocorrencia::RELATO_DA_PARTE => 'Relato da parte',
                        default => $state,
                    })
                    ->color(fn (string $state) => $state === Ocorrencia::RELATO_DA_PARTE ? 'gray' : 'danger'),

                // A distinção importa na leitura: relato é acusação de uma
                // parte, não fato apurado.
                TextColumn::make('origem')
                    ->label('Origem')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === Ocorrencia::ORIGEM_SISTEMA
                        ? 'Sistema'
                        : 'Relato')
                    ->color(fn (string $state) => $state === Ocorrencia::ORIGEM_SISTEMA ? 'info' : 'warning'),

                TextColumn::make('autor.nome')->label('Autor do relato')->placeholder('—'),

                TextColumn::make('descricao')->label('Descrição')->wrap()->limit(140),

                TextColumn::make('solicitacao_id')->label('Atendimento nº')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('origem')
                    ->label('Origem')
                    ->options([
                        Ocorrencia::ORIGEM_SISTEMA => 'Sistema',
                        Ocorrencia::ORIGEM_USUARIO => 'Relato de uma parte',
                    ]),
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        Ocorrencia::CANCELAMENTO_DO_AJUDANTE => 'Cancelou (ajudante)',
                        Ocorrencia::CANCELAMENTO_EM_CIMA_DA_HORA => 'Cancelou em cima da hora',
                        Ocorrencia::CONTESTACAO_SEM_FUNDAMENTO => 'Contestação sem fundamento',
                        Ocorrencia::FAVOR_NAO_REALIZADO => 'Favor não realizado',
                        Ocorrencia::RELATO_DA_PARTE => 'Relato da parte',
                    ]),
            ]);
    }
}
