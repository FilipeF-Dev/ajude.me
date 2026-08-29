<?php

namespace App\Filament\Resources\TermoCiencias\Pages;

use App\Dominio\Seguranca\GerirTermoDeCiencia;
use App\Filament\Resources\TermoCiencias\TermoCienciaResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use LogicException;

class ListTermoCiencias extends ListRecords
{
    protected static string $resource = TermoCienciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publicar')
                ->label('Publicar nova versão')
                ->icon('heroicon-o-document-plus')
                ->modalHeading('Publicar nova versão do termo')
                ->modalDescription(
                    'A versão atual será encerrada. O texto publicado nunca muda: '
                    .'quem aceitar esta versão aceitou exatamente este texto.'
                )
                ->schema([
                    Textarea::make('texto')
                        ->label('Texto do termo')
                        ->required()
                        ->rows(14)
                        ->helperText('Depende de apoio jurídico (02 §8.2). O mecanismo já funciona com qualquer redação aprovada.'),
                ])
                ->action(function (array $data) {
                    try {
                        $termo = app(GerirTermoDeCiencia::class)
                            ->publicar($data['texto'], auth('operacao')->user());
                    } catch (LogicException $erro) {
                        Notification::make()->title('Não foi possível publicar')
                            ->body($erro->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()
                        ->title("Versão {$termo->versao} publicada")
                        ->success()
                        ->send();
                }),
        ];
    }
}
