<?php

namespace App\Filament\Resources\Convites\Pages;

use App\Dominio\Identidade\GerirConvites;
use App\Filament\Resources\Convites\ConviteResource;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use LogicException;

class ListConvites extends ListRecords
{
    protected static string $resource = ConviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('emitir')
                ->label('Emitir convites')
                ->icon('heroicon-o-plus')
                ->modalHeading('Emitir convites')
                ->modalDescription(
                    'Em lote, porque é assim que o piloto funciona: prepare um punhado '
                    .'antes de ir a campo.'
                )
                ->schema([
                    TextInput::make('quantidade')
                        ->label('Quantos')
                        ->numeric()
                        ->default(5)
                        ->minValue(1)
                        ->maxValue(100)
                        ->required(),

                    TextInput::make('destinatario')
                        ->label('Para quem (opcional)')
                        ->helperText('Ajuda a acompanhar o piloto sem depender da memória.'),

                    DatePicker::make('expira_em')
                        ->label('Vence em (opcional)')
                        ->helperText('Em branco, não vence.'),

                    Textarea::make('observacao')->label('Observação (opcional)')->rows(2),
                ])
                ->action(function (array $data) {
                    try {
                        $convites = app(GerirConvites::class)->emitir(
                            auth('operacao')->user(),
                            (int) $data['quantidade'],
                            $data['destinatario'] ?: null,
                            $data['observacao'] ?: null,
                            $data['expira_em'] ? Carbon::parse($data['expira_em']) : null,
                        );
                    } catch (LogicException $erro) {
                        Notification::make()
                            ->title('Não foi possível emitir')
                            ->body($erro->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    // Persistente e com os códigos à vista: quem emitiu vai
                    // copiá-los agora, não depois.
                    Notification::make()
                        ->title($convites->count().' convite(s) emitido(s)')
                        ->body($convites->pluck('codigo')->implode(' · '))
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
