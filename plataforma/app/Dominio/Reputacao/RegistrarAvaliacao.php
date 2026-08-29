<?php

namespace App\Dominio\Reputacao;

use App\Models\Avaliacao;
use App\Models\MotivoQualidade;
use App\Models\NotaCriterio;
use App\Models\Solicitacao;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Registro da avaliação de uma parte pela outra (02 §11).
 *
 * A avaliação é liberada apenas quando o favor chega a realizado — por
 * confirmação, confirmação tácita ou decisão da Operação (RF-SOL-20, I20).
 * Não há avaliação em solicitação não realizada ou inconclusiva: o favor não
 * aconteceu, e avaliar experiência inexistente distorceria a reputação dos
 * dois.
 *
 * Registrada a segunda avaliação, o ciclo encerra: a solicitação vai a
 * `ENCERRADA` e o ticket a `ENCERRADO` (I21, 05 §6.1).
 */
class RegistrarAvaliacao
{
    /**
     * @param  array<string, int>  $notas             critério => nota de 0 a 7
     * @param  array<int, string>  $motivosQualidade  obrigatórios ao avaliar Qualidade
     */
    public function executar(
        Solicitacao $solicitacao,
        Usuario $avaliador,
        array $notas,
        array $motivosQualidade = [],
        ?string $comentario = null,
        ?Carbon $momento = null,
    ): Avaliacao {
        $momento ??= Carbon::now();

        return DB::transaction(function () use (
            $solicitacao, $avaliador, $notas, $motivosQualidade, $comentario, $momento
        ) {
            $solicitacao = Solicitacao::query()
                ->whereKey($solicitacao->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($solicitacao->estado !== Solicitacao::REALIZADA) {
                throw new LogicException(
                    "A solicitação está em \"{$solicitacao->estado}\": a avaliação só é "
                    .'liberada quando o favor é dado por realizado.'
                );
            }

            $papelDoAvaliador = $solicitacao->papelDe($avaliador);

            [$avaliado, $papelAvaliado] = $papelDoAvaliador === Solicitacao::PARTE_SOLICITANTE
                ? [$solicitacao->ajudante, Solicitacao::PARTE_AJUDANTE]
                : [$solicitacao->solicitante, Solicitacao::PARTE_SOLICITANTE];

            $criterios = Avaliacao::criteriosDe($papelAvaliado);

            $this->conferirNotas($notas, $criterios);

            if (in_array(Avaliacao::QUALIDADE, $criterios, true)) {
                $motivosQualidade = $this->conferirMotivos($motivosQualidade);
            } else {
                $motivosQualidade = [];
            }

            $comentario = $comentario === null ? null : (trim($comentario) ?: null);

            $avaliacao = new Avaliacao();
            $avaliacao->forceFill([
                'solicitacao_id' => $solicitacao->id,
                'avaliador_id' => $avaliador->id,
                'avaliado_id' => $avaliado->id,
                'papel_avaliado' => $papelAvaliado,
                'comentario' => $comentario,
                'criada_em' => $momento,
            ]);

            try {
                $avaliacao->save();
            } catch (UniqueConstraintViolationException) {
                // I24 — uma avaliação por par (solicitação, avaliador). A
                // segunda tentativa não é correção: a avaliação é imutável.
                throw new LogicException('Você já avaliou este favor.');
            }

            foreach ($criterios as $criterio) {
                NotaCriterio::create([
                    'avaliacao_id' => $avaliacao->id,
                    'criterio' => $criterio,
                    'nota' => $notas[$criterio],
                ]);
            }

            foreach ($motivosQualidade as $motivo) {
                MotivoQualidade::create([
                    'avaliacao_id' => $avaliacao->id,
                    'motivo' => $motivo,
                ]);
            }

            if ($solicitacao->ambasAvaliaram()) {
                $this->encerrarCiclo($solicitacao, $avaliador);
            }

            return $avaliacao;
        });
    }

    /**
     * RF-AVA-06 — as notas por critério são obrigatórias, todas elas.
     *
     * Faltar um critério deixaria a média geral comparando pessoas avaliadas
     * em dimensões diferentes.
     */
    private function conferirNotas(array $notas, array $criterios): void
    {
        $faltando = array_diff($criterios, array_keys($notas));

        if ($faltando !== []) {
            throw new LogicException(
                'Faltam notas nos critérios: '.implode(', ', $faltando).'.'
            );
        }

        $sobrando = array_diff(array_keys($notas), $criterios);

        if ($sobrando !== []) {
            throw new LogicException(
                'Critérios que não se aplicam a este papel: '.implode(', ', $sobrando).'.'
            );
        }

        foreach ($criterios as $criterio) {
            $nota = $notas[$criterio];

            if (! is_int($nota) || $nota < Avaliacao::NOTA_MINIMA || $nota > Avaliacao::NOTA_MAXIMA) {
                throw new LogicException(
                    "A nota de \"{$criterio}\" precisa ser um inteiro de "
                    .Avaliacao::NOTA_MINIMA.' a '.Avaliacao::NOTA_MAXIMA.'.'
                );
            }
        }
    }

    /**
     * RF-AVA-05 — Qualidade vem acompanhada de motivos pré-determinados.
     *
     * @return array<int, string>
     */
    private function conferirMotivos(array $motivos): array
    {
        $motivos = array_values(array_unique($motivos));

        if ($motivos === []) {
            throw new LogicException(
                'O critério Qualidade exige ao menos um motivo entre os '
                .'pré-determinados: '.implode(', ', Avaliacao::MOTIVOS_QUALIDADE).'.'
            );
        }

        $desconhecidos = array_diff($motivos, Avaliacao::MOTIVOS_QUALIDADE);

        if ($desconhecidos !== []) {
            throw new LogicException(
                'Motivos fora da lista: '.implode(', ', $desconhecidos).'.'
            );
        }

        return $motivos;
    }

    /**
     * I21 e 05 §6.1 — avaliações completas encerram a solicitação e o ticket.
     *
     * O ticket já estava consumido; encerrar é o passo que fecha o episódio
     * de vez. Ele não some do histórico: `ENCERRADO` é estado terminal, não
     * exclusão (02 §20.3).
     */
    private function encerrarCiclo(Solicitacao $solicitacao, Usuario $ultimoAvaliador): void
    {
        $solicitacao->transicionar(
            Solicitacao::ENCERRADA,
            'ciclo_encerrado',
            $ultimoAvaliador->id,
        );

        Ticket::query()
            ->whereKey($solicitacao->ticket_id)
            ->lockForUpdate()
            ->firstOrFail()
            ->transicionar(Ticket::ENCERRADO, 'ciclo_encerrado', $ultimoAvaliador->id);
    }
}
