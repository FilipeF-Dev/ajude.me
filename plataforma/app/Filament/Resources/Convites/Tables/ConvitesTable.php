<?php

namespace App\Filament\Resources\Convites\Tables;

use App\Dominio\Identidade\GerirConvites;
use App\Models\Convite;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

class ConvitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->weight('bold')
                    // Copiável porque vai ser colado numa mensagem ou lido em
                    // voz alta para alguém digitar.
                    ->copyable()
                    ->copyMessage('Código copiado')
                    ->searchable(),

                TextColumn::make('situacao')
                    ->label('Situação')
                    ->badge()
                    ->state(fn (Convite $r) => $r->situacao())
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'disponivel' => 'Disponível',
                        'usado' => 'Usado',
                        'revogado' => 'Cancelado',
                        'vencido' => 'Vencido',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'disponivel' => 'success',
                        'usado' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('destinatario')
                    ->label('Para')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('usadoPor.nome')
                    ->label('Entrou como')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('usado_em')
                    ->label('Usado em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),

                TextColumn::make('expira_em')
                    ->label('Vence em')
                    ->dateTime('d/m/Y')
                    ->placeholder('sem prazo'),

                TextColumn::make('criadoPor.nome')->label('Emitido por'),

                TextColumn::make('observacao')
                    ->label('Observação')
                    ->wrap()
                    ->limit(80)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('situacao')
                    ->label('Situação')
                    ->options([
                        'disponivel' => 'Disponível',
                        'usado' => 'Usado',
                        'revogado' => 'Cancelado',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'disponivel' => $query->whereNull('usado_em')->whereNull('revogado_em')
                            ->where(fn ($c) => $c->whereNull('expira_em')->orWhere('expira_em', '>', now())),
                        'usado' => $query->whereNotNull('usado_em'),
                        'revogado' => $query->whereNotNull('revogado_em'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('revogar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Convite $r) => $r->podeSerUsado())
                    ->modalHeading('Cancelar convite')
                    ->modalDescription('O código deixa de valer. Quem já entrou por ele não é afetado.')
                    ->schema([
                        Textarea::make('motivo')->label('Motivo')->required()->rows(2),
                    ])
                    ->action(function (array $data, Convite $registro) {
                        try {
                            app(GerirConvites::class)->revogar(
                                $registro,
                                auth('operacao')->user(),
                                $data['motivo'],
                            );
                        } catch (LogicException $erro) {
                            Notification::make()
                                ->title('Não foi possível cancelar')
                                ->body($erro->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()->title('Convite cancelado')->success()->send();
                    }),
            ]);
    }
}
