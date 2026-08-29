<?php

namespace App\Dominio\Consumo;

use App\Dominio\Seguranca\GerirTermoDeCiencia;
use App\Models\Favor;
use App\Models\Solicitacao;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Resposta do ajudante à solicitação (RF-SOL-04, RF-SOL-05, RF-SOL-06).
 *
 * O ajudante deve respeitar a solicitação que esteja de acordo com as
 * condições publicadas (02 §8). Recusar não é livre arbítrio: exige apontar
 * a condição violada. Quem quer deixar de atender desativa o favor — o que
 * impede novas emissões e preserva os tickets já emitidos.
 */
class ResponderSolicitacao
{
    public function __construct(
        private readonly VerificarCondicoesPublicadas $condicoes = new VerificarCondicoesPublicadas(),
        private readonly GerirTermoDeCiencia $termo = new GerirTermoDeCiencia(),
    ) {
    }

    /**
     * RF-SOL-04 e RF-SOL-06 — confirmar agenda e reserva o ticket.
     *
     * Em favor presencial, o ajudante também aceita o termo de ciência
     * (RF-SEG-03): quem recebe alguém em casa corre o mesmo tipo de risco de
     * quem vai à casa de alguém, e o termo é do atendimento, não de um lado.
     */
    public function agendar(
        Solicitacao $solicitacao,
        Usuario $ajudante,
        ?Carbon $momento = null,
        bool $cienteDosRiscos = false,
    ): Solicitacao {
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($solicitacao, $ajudante, $momento, $cienteDosRiscos) {
            $solicitacao = $this->travar($solicitacao, $ajudante);

            if (! $solicitacao->podeSerTransicionadaPara(Solicitacao::AGENDADA)) {
                throw new LogicException(
                    "A solicitação está em \"{$solicitacao->estado}\" e não pode ser agendada."
                );
            }

            /**
             * 05 §7.3 — a verificação de sobreposição acontece na confirmação,
             * sob bloqueio da agenda do ajudante.
             *
             * A linha do ajudante é travada para serializar todas as
             * confirmações dele; a restrição de exclusão da tabela é a
             * garantia final. O bloqueio existe para produzir mensagem
             * legível, não para substituir a restrição.
             */
            Usuario::query()->whereKey($ajudante->getKey())->lockForUpdate()->firstOrFail();

            $sobreposta = Solicitacao::query()
                ->where('ajudante_id', $ajudante->id)
                ->whereIn('estado', [Solicitacao::AGENDADA, Solicitacao::AGUARDANDO_CONFIRMACAO])
                ->where('inicio_previsto', '<', $solicitacao->fim_previsto)
                ->where('fim_previsto', '>', $solicitacao->inicio_previsto)
                ->first();

            if ($sobreposta !== null) {
                throw new LogicException(
                    'Você já tem atendimento agendado das '
                    .$sobreposta->inicio_previsto->format('H:i').' às '
                    .$sobreposta->fim_previsto->format('H:i')
                    .' em '.$sobreposta->inicio_previsto->format('d/m/Y').'.'
                );
            }

            // I18 reconferida: entre o pedido e a confirmação, a validade do
            // ticket pode ter passado.
            $ticket = Ticket::query()
                ->whereKey($solicitacao->ticket_id)
                ->with('favor')
                ->lockForUpdate()
                ->firstOrFail();

            $this->condicoes->executar(
                $ticket,
                $solicitacao->solicitante,
                $solicitacao->inicio_previsto,
                $momento,
            );

            if ($ticket->favor->modalidade === Favor::MODALIDADE_PRESENCIAL) {
                if (! $cienteDosRiscos) {
                    throw new LogicException(
                        'Favor presencial exige o aceite do termo de ciência de riscos.'
                    );
                }

                $this->termo->aceitar($solicitacao, $ajudante, $momento);
            }

            try {
                $solicitacao->transicionar(Solicitacao::AGENDADA, 'solicitacao_agendada', $ajudante->id);
            } catch (QueryException $erro) {
                if (str_contains($erro->getMessage(), 'solicitacoes_sem_sobreposicao_do_ajudante')) {
                    throw new LogicException(
                        'Outro atendimento ocupou este horário enquanto você confirmava.'
                    );
                }

                throw $erro;
            }

            // 05 §6.1 — Solicitacao.AGENDADA → Ticket.RESERVADO.
            $ticket->transicionar(Ticket::RESERVADO, 'solicitacao_agendada', $ajudante->id);

            return $solicitacao;
        });
    }

    /**
     * RF-SOL-05 — a recusa exige dizer qual condição foi violada.
     *
     * O ticket segue `DISPONIVEL`: nunca saiu de lá, e quem tem o direito
     * continua podendo pedir outro horário.
     */
    public function recusar(
        Solicitacao $solicitacao,
        Usuario $ajudante,
        string $motivo,
        ?Carbon $momento = null,
    ): Solicitacao {
        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new LogicException(
                'A recusa exige apontar a condição publicada que a solicitação violou.'
            );
        }

        return DB::transaction(function () use ($solicitacao, $ajudante, $motivo) {
            $solicitacao = $this->travar($solicitacao, $ajudante);

            if (! $solicitacao->podeSerTransicionadaPara(Solicitacao::RECUSADA)) {
                throw new LogicException(
                    "A solicitação está em \"{$solicitacao->estado}\" e não pode ser recusada."
                );
            }

            $solicitacao->forceFill(['motivo_recusa' => $motivo])->save();
            $solicitacao->transicionar(Solicitacao::RECUSADA, 'solicitacao_recusada', $ajudante->id);

            return $solicitacao;
        });
    }

    private function travar(Solicitacao $solicitacao, Usuario $ajudante): Solicitacao
    {
        $travada = Solicitacao::query()
            ->whereKey($solicitacao->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if ($travada->ajudante_id !== $ajudante->id) {
            throw new LogicException('Somente o ajudante responde à solicitação.');
        }

        return $travada;
    }
}
