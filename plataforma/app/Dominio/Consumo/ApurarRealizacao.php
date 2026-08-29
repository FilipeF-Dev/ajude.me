<?php

namespace App\Dominio\Consumo;

use App\Dominio\Moderacao\AbrirChamado;
use App\Models\ManifestacaoRealizacao;
use App\Models\ParametroNegocio;
use App\Models\Solicitacao;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Confirmação da realização do favor (02 §8.4).
 *
 * > **O silêncio confirma. Somente a contestação ativa impede o encerramento.**
 *
 * A confirmação tácita existe porque o encerramento não pode depender da
 * diligência das duas partes: um ciclo aberto para sempre prende o ticket,
 * trava as avaliações e congela a reputação de ambos.
 */
class ApurarRealizacao
{
    public function __construct(
        private readonly AbrirChamado $chamados = new AbrirChamado(),
    ) {
    }

    /**
     * RF-SOL-08 — alcançada a data prevista, abre-se a janela de contestação.
     *
     * O prazo vem do parâmetro vigente no momento em que a solicitação
     * nasceu, não no de agora (05 §9): mudar a configuração não pode
     * reescrever as condições de uma relação em curso (P5).
     */
    public function abrirJanela(Solicitacao $solicitacao, ?Carbon $momento = null): Solicitacao
    {
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($solicitacao, $momento) {
            $solicitacao = $this->travar($solicitacao);

            if (! $solicitacao->janelaDeveAbrir($momento)) {
                throw new LogicException(
                    'A janela de contestação só abre depois do fim previsto de uma solicitação agendada.'
                );
            }

            $horas = ParametroNegocio::inteiroEm(
                ParametroNegocio::JANELA_CONTESTACAO_HORAS,
                $solicitacao->created_at,
            );

            if ($horas === null) {
                throw new LogicException(
                    'O parâmetro da janela de contestação não está definido. '
                    .'Rode o ParametrosNegocioSeeder.'
                );
            }

            $solicitacao->forceFill([
                'janela_contestacao_ate' => $solicitacao->fim_previsto->copy()->addHours($horas),
            ])->save();

            $solicitacao->transicionar(
                Solicitacao::AGUARDANDO_CONFIRMACAO,
                'janela_de_contestacao_aberta',
                momento: $momento,
            );

            return $solicitacao;
        });
    }

    /**
     * RF-SOL-09 e RF-SOL-11 — cada parte confirma ou contesta.
     *
     * Contestar não exige justificativa prévia e vale durante toda a janela.
     * A confirmação das duas partes encerra a janela imediatamente, sem
     * esperar o prazo.
     */
    public function manifestar(
        Solicitacao $solicitacao,
        Usuario $parte,
        string $tipo,
        ?Carbon $momento = null,
    ): Solicitacao {
        $momento ??= Carbon::now();

        if (! in_array($tipo, [ManifestacaoRealizacao::CONFIRMA, ManifestacaoRealizacao::CONTESTA], true)) {
            throw new LogicException("Manifestação desconhecida: \"{$tipo}\".");
        }

        return DB::transaction(function () use ($solicitacao, $parte, $tipo, $momento) {
            $solicitacao = $this->travar($solicitacao);
            $papel = $solicitacao->papelDe($parte);

            if ($solicitacao->estado !== Solicitacao::AGUARDANDO_CONFIRMACAO) {
                throw new LogicException(
                    "A solicitação está em \"{$solicitacao->estado}\" e não aguarda manifestação."
                );
            }

            /**
             * 05 §7.5 — a contestação registrada dentro da janela prevalece.
             *
             * Por isso o limite é comparado com `>`: manifestar no instante
             * exato do vencimento ainda está dentro. A confirmação tácita é
             * apurada **após** o limite, nunca antecipada.
             */
            if ($momento->greaterThan($solicitacao->janela_contestacao_ate)) {
                throw new LogicException('A janela de contestação já se encerrou.');
            }

            try {
                ManifestacaoRealizacao::create([
                    'solicitacao_id' => $solicitacao->id,
                    'parte' => $papel,
                    'tipo' => $tipo,
                    'momento' => $momento,
                ]);
            } catch (UniqueConstraintViolationException) {
                // I22 — uma manifestação por parte. Quem já se manifestou não
                // muda de ideia por outro registro; a apuração cabe à Operação.
                throw new LogicException('Você já se manifestou sobre esta solicitação.');
            }

            $solicitacao->refresh();

            if ($tipo === ManifestacaoRealizacao::CONTESTA) {
                return $this->contestar($solicitacao, $parte, $momento);
            }

            // RF-SOL-09 — a confirmação bilateral encerra a janela na hora.
            if ($solicitacao->ambasConfirmaram()) {
                return $this->realizar($solicitacao, 'realizacao_confirmada', $parte->id, $momento);
            }

            return $solicitacao;
        });
    }

    /**
     * RF-SOL-10 — encerrada a janela sem contestação, o favor está realizado,
     * havendo ou não confirmações.
     */
    public function apurarConfirmacaoTacita(Solicitacao $solicitacao, ?Carbon $momento = null): Solicitacao
    {
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($solicitacao, $momento) {
            $solicitacao = $this->travar($solicitacao);

            if (! $solicitacao->janelaEncerrou($momento)) {
                throw new LogicException('A janela de contestação ainda está aberta.');
            }

            // Guarda de coerência: uma contestação leva a solicitação para
            // análise no ato, então chegar aqui contestada seria contradição.
            if ($solicitacao->foiContestada()) {
                throw new LogicException(
                    'Esta solicitação foi contestada e pertence à análise, não à confirmação tácita.'
                );
            }

            return $this->realizar(
                $solicitacao,
                'realizacao_tacitamente_confirmada',
                momento: $momento,
            );
        });
    }

    /**
     * RF-SOL-12 — a contestação leva à análise, bloqueia o ticket e abre um
     * chamado para a Operação.
     *
     * As três coisas em uma transação: uma solicitação em análise sem chamado
     * seria disputa que ninguém apura, e um ticket livre durante a disputa
     * poderia ser trocado antes da decisão.
     */
    private function contestar(Solicitacao $solicitacao, Usuario $parte, Carbon $momento): Solicitacao
    {
        $solicitacao->transicionar(Solicitacao::EM_ANALISE, 'realizacao_contestada', $parte->id, $momento);

        // 05 §6.1 — Solicitacao.EM_ANALISE → Ticket.EM_ANALISE.
        // I7: a partir daqui a validade do ticket para de correr. A demora da
        // plataforma não pode extinguir um direito em disputa.
        $this->ticket($solicitacao)->transicionar(
            Ticket::EM_ANALISE,
            'realizacao_contestada',
            $parte->id,
            $momento,
        );

        $this->chamados->executar($solicitacao, $parte, $momento);

        return $solicitacao;
    }

    /**
     * O momento importa aqui mais do que em qualquer outra transição: é dele
     * que sai `realizadaEm()`, e é de `realizadaEm()` que corre o prazo de
     * avaliação (RF-AVA-16). Gravar a hora em que a rotina passou encurtaria
     * o prazo de quem não tem culpa de a rotina ter demorado.
     */
    private function realizar(
        Solicitacao $solicitacao,
        string $evento,
        ?int $atorId = null,
        ?Carbon $momento = null,
    ): Solicitacao {
        $solicitacao->transicionar(Solicitacao::REALIZADA, $evento, $atorId, $momento);

        // 05 §6.1 — Solicitacao.REALIZADA → Ticket.CONSUMIDO. É aqui que a
        // vaga de circulação do favor é liberada (02 §5.1) e as avaliações
        // ficam disponíveis (RF-SOL-20).
        $this->ticket($solicitacao)->transicionar(Ticket::CONSUMIDO, $evento, $atorId, $momento);

        return $solicitacao;
    }

    private function ticket(Solicitacao $solicitacao): Ticket
    {
        return Ticket::query()
            ->whereKey($solicitacao->ticket_id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function travar(Solicitacao $solicitacao): Solicitacao
    {
        return Solicitacao::query()
            ->whereKey($solicitacao->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }
}
