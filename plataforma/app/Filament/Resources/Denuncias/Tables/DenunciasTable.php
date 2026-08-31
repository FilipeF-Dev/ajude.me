<?php

namespace App\Filament\Resources\Denuncias\Tables;

use App\Models\Denuncia;
use App\Models\RegistroAuditoria;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DenunciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('registrada_em', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->orderByRaw('CASE WHEN estado = ? THEN 0 ELSE 1 END', [Denuncia::ABERTA]))
            ->columns([
                TextColumn::make('estado')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Denuncia::ABERTA => 'Aberta',
                        Denuncia::PROCEDENTE => 'Procedente',
                        Denuncia::IMPROCEDENTE => 'Improcedente',
                    })
                    ->color(fn (string $state) => match ($state) {
                        Denuncia::ABERTA => 'warning',
                        Denuncia::PROCEDENTE => 'danger',
                        Denuncia::IMPROCEDENTE => 'gray',
                    }),

                TextColumn::make('autor.nome')->label('Quem denunciou')->searchable(),

                TextColumn::make('alvo')
                    ->label('Alvo')
                    ->state(fn (Denuncia $r) => $r->alvo_usuario_id
                        ? $r->alvoUsuario->nome
                        : 'Favor: '.$r->alvoFavor->titulo)
                    ->description(fn (Denuncia $r) => $r->alvo_usuario_id ? 'usuário' : 'favor'),

                TextColumn::make('motivo')->label('Motivo')->wrap()->limit(120),

                TextColumn::make('registrada_em')
                    ->label('Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('analisadaPor.nome')->label('Analisada por')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Situação')
                    ->options([
                        Denuncia::ABERTA => 'Aberta',
                        Denuncia::PROCEDENTE => 'Procedente',
                        Denuncia::IMPROCEDENTE => 'Improcedente',
                    ]),
            ])
            ->recordActions([
                Action::make('analisar')
                    ->label('Analisar')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Denuncia $r) => $r->estaAberta())
                    ->modalHeading('Analisar denúncia')
                    ->schema([
                        Radio::make('estado')
                            ->label('Desfecho')
                            ->required()
                            ->options([
                                Denuncia::PROCEDENTE => 'Procedente',
                                Denuncia::IMPROCEDENTE => 'Improcedente',
                            ])
                            ->descriptions([
                                Denuncia::PROCEDENTE => 'Registra o desfecho. A suspensão, se couber, é ato separado.',
                                Denuncia::IMPROCEDENTE => 'Encerra sem consequência para o alvo.',
                            ]),
                        Textarea::make('desfecho')
                            ->label('Fundamento')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, Denuncia $registro) {
                        $operador = auth('operacao')->user();

                        $registro->forceFill([
                            'estado' => $data['estado'],
                            'desfecho' => $data['desfecho'],
                            'analisada_por_id' => $operador->id,
                            'analisada_em' => now(),
                        ])->save();

                        // RF-ADM-10 — toda ação da Operação entra na trilha.
                        RegistroAuditoria::registrar(
                            $operador,
                            "denuncia_analisada:{$data['estado']}",
                            $registro,
                            $data['desfecho'],
                        );

                        Notification::make()->title('Denúncia analisada')->success()->send();
                    }),
            ]);
    }
}
