<?php

namespace App\Filament\Resources\Chamados\Pages;

use App\Dominio\Moderacao\DecidirChamado;
use App\Filament\Resources\Chamados\ChamadoResource;
use App\Models\Chamado;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use LogicException;

/**
 * A apuração de um chamado e o ato de decidir (RF-ADM-13, RF-ADM-14).
 *
 * A decisão sai daqui por uma ação com fundamento obrigatório, e não por um
 * campo editável: RF-SOL-19 manda comunicar a decisão **e o seu fundamento**
 * às partes, e um campo em branco não tem o que comunicar.
 *
 * A ação chama `DecidirChamado`, que aplica os efeitos sobre ticket,
 * solicitação e ocorrências numa transação. A tela não reimplementa nada
 * disso — se reimplementasse, haveria duas versões da regra.
 */
class VerChamado extends ViewRecord
{
    protected static string $resource = ChamadoResource::class;

    public function getTitle(): string
    {
        return "Chamado nº {$this->record->id}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('decidir')
                ->label('Decidir o chamado')
                ->icon('heroicon-o-scale')
                ->color('warning')
                ->visible(fn (Chamado $registro) => $registro->estaAberto())
                ->modalHeading('Decidir o chamado')
                ->modalDescription(
                    'A decisão é definitiva, aplica efeitos sobre o ticket e é '
                    .'comunicada às duas partes com o fundamento.'
                )
                ->modalSubmitActionLabel('Registrar decisão')
                ->schema([
                    Radio::make('decisao')
                        ->label('Desfecho')
                        ->required()
                        ->options([
                            Chamado::REALIZADO => 'Favor realizado',
                            Chamado::NAO_REALIZADO => 'Favor não realizado',
                            Chamado::INCONCLUSIVO => 'Inconclusivo',
                        ])
                        // Os efeitos ditos antes da escolha: quem decide
                        // precisa saber o que cada opção provoca (02 §8.5).
                        ->descriptions([
                            Chamado::REALIZADO => 'Ticket consumido. Ocorrência contra quem contestou.',
                            Chamado::NAO_REALIZADO => 'Ticket devolvido ao solicitante. Ocorrência contra o ajudante.',
                            Chamado::INCONCLUSIVO => 'Ticket devolvido. Nenhuma ocorrência — favorece o portador sem culpar ninguém.',
                        ]),

                    Textarea::make('fundamento')
                        ->label('Fundamento')
                        ->required()
                        ->rows(4)
                        ->helperText('Será comunicado às duas partes (RF-SOL-19).'),
                ])
                ->action(function (array $data, Chamado $registro) {
                    try {
                        app(DecidirChamado::class)->executar(
                            $registro,
                            auth('operacao')->user(),
                            $data['decisao'],
                            $data['fundamento'],
                        );
                    } catch (LogicException $erro) {
                        Notification::make()
                            ->title('Não foi possível decidir')
                            ->body($erro->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Chamado decidido')
                        ->body('As partes foram notificadas e os efeitos aplicados.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
