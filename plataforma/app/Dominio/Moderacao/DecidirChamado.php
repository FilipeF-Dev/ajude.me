<?php

namespace App\Dominio\Moderacao;

use App\Dominio\Notificacao\Notificar;
use App\Models\Chamado;
use App\Models\Notificacao;
use App\Models\Ocorrencia;
use App\Models\Operador;
use App\Models\RegistroAuditoria;
use App\Models\Solicitacao;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Decisão do chamado pela Operação (02 §8.5, RF-ADM-13, RF-ADM-14).
 *
 * Os três desfechos e seus efeitos:
 *
 * | Decisão          | Ticket     | Solicitação    | Ocorrência                  |
 * |------------------|------------|----------------|-----------------------------|
 * | Realizado        | Consumido  | Realizada      | Contra quem contestou       |
 * | Não realizado    | Devolvido  | Não realizada  | Contra o ajudante           |
 * | Inconclusivo     | Devolvido  | Inconclusiva   | Nenhuma                     |
 *
 * O inconclusivo favorece o portador do ticket sem atribuir culpa. Decorre de
 * 02 §20: ticket emitido é direito adquirido e não se extingue por falta de
 * prova. E a ausência de ocorrência preserva a reputação dos dois quando não
 * há elementos para responsabilizar ninguém.
 *
 * Quem decide é a Operação — `Operador`, nunca `Usuario`. E toda decisão
 * entra na trilha de auditoria com autor e fundamento (RF-ADM-10).
 */
class DecidirChamado
{
    public function __construct(
        private readonly RegistrarOcorrencia $ocorrencias = new RegistrarOcorrencia(),
    ) {
    }

    public function executar(
        Chamado $chamado,
        Operador $operador,
        string $decisao,
        string $fundamento,
        ?Carbon $momento = null,
    ): Chamado {
        $fundamento = trim($fundamento);

        if ($fundamento === '') {
            // RF-SOL-19 — a decisão é comunicada às partes com o fundamento.
            // Sem ele, não há o que comunicar.
            throw new LogicException('A decisão do chamado exige fundamento.');
        }

        if (! in_array($decisao, [
            Chamado::REALIZADO,
            Chamado::NAO_REALIZADO,
            Chamado::INCONCLUSIVO,
        ], true)) {
            throw new LogicException("Decisão desconhecida: \"{$decisao}\".");
        }

        $momento ??= Carbon::now();

        return DB::transaction(function () use ($chamado, $operador, $decisao, $fundamento, $momento) {
            $chamado = Chamado::query()
                ->whereKey($chamado->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $chamado->estaAberto()) {
                throw new LogicException('Este chamado já foi decidido.');
            }

            $solicitacao = Solicitacao::query()
                ->whereKey($chamado->solicitacao_id)
                ->lockForUpdate()
                ->firstOrFail();

            $ticket = Ticket::query()
                ->whereKey($solicitacao->ticket_id)
                ->lockForUpdate()
                ->firstOrFail();

            $evento = "chamado_{$decisao}";

            match ($decisao) {
                Chamado::REALIZADO => $this->realizado($solicitacao, $ticket, $chamado, $evento),
                Chamado::NAO_REALIZADO => $this->naoRealizado($solicitacao, $ticket, $evento, $momento),
                Chamado::INCONCLUSIVO => $this->inconclusivo($solicitacao, $ticket, $evento),
            };

            $chamado->forceFill([
                'estado' => Chamado::DECIDIDO,
                'decisao' => $decisao,
                'fundamento' => $fundamento,
                'decidido_por_id' => $operador->id,
                'decidido_em' => $momento,
            ])->save();

            RegistroAuditoria::registrar(
                $operador,
                "chamado_decidido:{$decisao}",
                $chamado,
                $fundamento,
                $momento,
            );

            // RF-SOL-19 — a decisão e seu fundamento são comunicados às duas
            // partes. Decidir sem comunicar deixaria quem perdeu sem saber
            // por quê, e quem ganhou sem saber que ganhou.
            (new Notificar())->enviarA(
                [$solicitacao->ajudante, $solicitacao->solicitante],
                Notificacao::CHAMADO_DECIDIDO,
                $chamado,
                ['decisao' => $decisao, 'fundamento' => $fundamento],
                $momento,
            );

            return $chamado;
        });
    }

    /**
     * RF-SOL-16 — o favor aconteceu; a ocorrência vai contra quem contestou.
     *
     * Contestar é gratuito e não exige justificativa prévia (02 §8.4), mas
     * não é sem consequência quando a apuração mostra que o favor ocorreu.
     * É o que separa o direito de contestar do incentivo a contestar sempre.
     */
    private function realizado(
        Solicitacao $solicitacao,
        Ticket $ticket,
        Chamado $chamado,
        string $evento,
    ): void {
        $solicitacao->transicionar(Solicitacao::REALIZADA, $evento);
        $ticket->transicionar(Ticket::CONSUMIDO, $evento);

        $this->ocorrencias->peloSistema(
            Ocorrencia::CONTESTACAO_SEM_FUNDAMENTO,
            $chamado->abertoPor,
            $solicitacao,
            'A Operação concluiu que o favor foi realizado.',
        );
    }

    /**
     * RF-SOL-17 — o favor não aconteceu; o ticket volta ao solicitante e a
     * ocorrência vai contra o ajudante.
     */
    private function naoRealizado(
        Solicitacao $solicitacao,
        Ticket $ticket,
        string $evento,
        Carbon $momento,
    ): void {
        $solicitacao->transicionar(Solicitacao::NAO_REALIZADA, $evento);
        $ticket->transicionar(Ticket::DISPONIVEL, $evento);

        $this->ocorrencias->peloSistema(
            Ocorrencia::FAVOR_NAO_REALIZADO,
            $solicitacao->ajudante,
            $solicitacao,
            'A Operação concluiu que o favor não foi realizado.',
            $momento,
        );
    }

    /**
     * RF-SOL-18 — sem elementos, o ticket volta e ninguém carrega ocorrência.
     */
    private function inconclusivo(Solicitacao $solicitacao, Ticket $ticket, string $evento): void
    {
        $solicitacao->transicionar(Solicitacao::INCONCLUSIVA, $evento);
        $ticket->transicionar(Ticket::DISPONIVEL, $evento);
    }
}
