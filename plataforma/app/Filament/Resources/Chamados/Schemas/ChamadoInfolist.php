<?php

namespace App\Filament\Resources\Chamados\Schemas;

use App\Models\Chamado;
use App\Models\ManifestacaoRealizacao;
use App\Models\Ocorrencia;
use App\Models\RegistroComparecimento;
use App\Models\Solicitacao;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * O que a Operação precisa ver para decidir (RF-ADM-12).
 *
 * A manifestação de cada parte, o registro de comparecimento e o histórico
 * de ocorrências de ambas — os três, na mesma tela. Decidir com metade da
 * evidência é o modo mais fácil de errar, e a decisão é definitiva.
 */
class ChamadoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('O atendimento em disputa')
                ->schema([
                    TextEntry::make('solicitacao.ticket.favor.titulo')->label('Favor'),
                    TextEntry::make('solicitacao.ajudante.nome')->label('Ajudante'),
                    TextEntry::make('solicitacao.solicitante.nome')->label('Solicitante'),
                    TextEntry::make('solicitacao.inicio_previsto')
                        ->label('Combinado para')
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('abertoPor.nome')
                        ->label('Quem contestou')
                        ->badge()
                        ->color('warning'),
                    TextEntry::make('manifestacao_ate')
                        ->label('Prazo das partes')
                        ->dateTime('d/m/Y H:i')
                        // RF-SOL-15 — a ausência de manifestação não impede o
                        // prosseguimento.
                        ->helperText('Esgotado o prazo, a análise segue com o que houver.'),
                ])
                ->columns(2),

            Section::make('O que cada parte diz')
                ->schema([
                    TextEntry::make('manifestacoes')
                        ->hiddenLabel()
                        ->state(fn (Chamado $registro) => self::manifestacoes($registro))
                        ->html(),
                ]),

            Section::make('Registro de comparecimento')
                ->description('Evidência independente da confirmação (02 §8.1).')
                ->schema([
                    TextEntry::make('comparecimento')
                        ->hiddenLabel()
                        ->state(fn (Chamado $registro) => self::comparecimento($registro))
                        ->html(),
                ]),

            Section::make('Como cada parte se manifestou sobre a realização')
                ->schema([
                    TextEntry::make('realizacao')
                        ->hiddenLabel()
                        ->state(fn (Chamado $registro) => self::manifestacoesDeRealizacao($registro))
                        ->html(),
                ]),

            Section::make('Histórico de ocorrências das partes')
                ->description('Comportamento recorrente é o que este quadro revela.')
                ->schema([
                    TextEntry::make('ocorrencias')
                        ->hiddenLabel()
                        ->state(fn (Chamado $registro) => self::ocorrencias($registro))
                        ->html(),
                ]),

            Section::make('Decisão')
                ->visible(fn (Chamado $registro) => ! $registro->estaAberto())
                ->schema([
                    TextEntry::make('decisao')
                        ->label('Desfecho')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => match ($state) {
                            Chamado::REALIZADO => 'Favor realizado',
                            Chamado::NAO_REALIZADO => 'Favor não realizado',
                            Chamado::INCONCLUSIVO => 'Inconclusivo',
                            default => '—',
                        }),
                    TextEntry::make('decididoPor.nome')->label('Decidido por'),
                    TextEntry::make('decidido_em')->label('Em')->dateTime('d/m/Y H:i'),
                    TextEntry::make('fundamento')->label('Fundamento')->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }

    private static function manifestacoes(Chamado $chamado): HtmlString
    {
        $partes = [
            Solicitacao::PARTE_AJUDANTE => $chamado->solicitacao->ajudante->nome,
            Solicitacao::PARTE_SOLICITANTE => $chamado->solicitacao->solicitante->nome,
        ];

        $linhas = [];

        foreach ($partes as $papel => $nome) {
            $manifestacao = $chamado->manifestacaoDe($papel);

            $linhas[] = $manifestacao
                ? '<p class="mb-3"><strong>'.e($nome).'</strong> · '
                    .$manifestacao->enviada_em->format('d/m/Y H:i').'<br>'
                    .nl2br(e($manifestacao->texto)).'</p>'
                : '<p class="mb-3 opacity-60"><strong>'.e($nome).'</strong> não se manifestou.</p>';
        }

        return new HtmlString(implode('', $linhas));
    }

    private static function comparecimento(Chamado $chamado): HtmlString
    {
        $registros = $chamado->solicitacao->comparecimentos()->get();

        if ($registros->isEmpty()) {
            return new HtmlString('<p class="opacity-60">Nenhum registro. '
                .'A ausência também é evidência.</p>');
        }

        $linhas = $registros->map(fn (RegistroComparecimento $r) => '<li>'
            .($r->parte === Solicitacao::PARTE_AJUDANTE ? 'Ajudante' : 'Solicitante')
            .' · '.($r->tipo === RegistroComparecimento::INICIO ? 'início' : 'fim')
            .' · '.$r->momento->format('d/m/Y H:i').'</li>');

        return new HtmlString('<ul class="list-disc ps-5">'.$linhas->implode('').'</ul>');
    }

    private static function manifestacoesDeRealizacao(Chamado $chamado): HtmlString
    {
        $manifestacoes = $chamado->solicitacao->manifestacoes()->get();

        if ($manifestacoes->isEmpty()) {
            return new HtmlString('<p class="opacity-60">Nenhuma.</p>');
        }

        $linhas = $manifestacoes->map(fn (ManifestacaoRealizacao $m) => '<li>'
            .($m->parte === Solicitacao::PARTE_AJUDANTE ? 'Ajudante' : 'Solicitante')
            .' · <strong>'.($m->tipo === ManifestacaoRealizacao::CONFIRMA ? 'confirmou' : 'contestou')
            .'</strong> · '.$m->momento->format('d/m/Y H:i').'</li>');

        return new HtmlString('<ul class="list-disc ps-5">'.$linhas->implode('').'</ul>');
    }

    private static function ocorrencias(Chamado $chamado): HtmlString
    {
        $ids = [$chamado->solicitacao->ajudante_id, $chamado->solicitacao->solicitante_id];

        $ocorrencias = Ocorrencia::query()
            ->whereIn('contra_usuario_id', $ids)
            ->with('contraUsuario')
            ->orderByDesc('registrada_em')
            ->limit(30)
            ->get();

        if ($ocorrencias->isEmpty()) {
            return new HtmlString('<p class="opacity-60">Nenhuma ocorrência no histórico das partes.</p>');
        }

        $linhas = $ocorrencias->map(fn (Ocorrencia $o) => '<li>'
            .$o->registrada_em->format('d/m/Y')
            .' · contra <strong>'.e($o->contraUsuario->nome).'</strong>'
            .' · '.e(str_replace('_', ' ', $o->tipo))
            .($o->ehAutomatica() ? ' <em>(do sistema)</em>' : ' <em>(relato da parte)</em>')
            .'</li>');

        return new HtmlString('<ul class="list-disc ps-5">'.$linhas->implode('').'</ul>');
    }
}
