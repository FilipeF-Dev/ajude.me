<?php

namespace App\Dominio\Consumo;

use App\Models\Solicitacao;
use App\Models\Ticket;
use App\Models\TransicaoSolicitacao;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use LogicException;

/**
 * Solicitação do favor pelo portador do ticket (RF-SOL-01 a RF-SOL-03).
 *
 * O ticket **não** muda de estado aqui: continua `DISPONIVEL` até o ajudante
 * confirmar (RF-SOL-06). Reservar antes da confirmação prenderia o direito
 * por conta de um pedido que ainda pode ser recusado.
 *
 * O que impede dois pedidos vivos sobre o mesmo ticket é o índice único
 * parcial da tabela (02 §5.2), não uma consulta prévia: duas requisições
 * simultâneas passariam pela consulta e só uma passa pelo índice.
 */
class SolicitarFavor
{
    public function __construct(
        private readonly VerificarCondicoesPublicadas $condicoes = new VerificarCondicoesPublicadas(),
    ) {
    }

    public function executar(
        Ticket $ticket,
        Usuario $solicitante,
        Carbon $inicioPrevisto,
        ?Carbon $momento = null,
    ): Solicitacao {
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($ticket, $solicitante, $inicioPrevisto, $momento) {
            $ticket = Ticket::query()
                ->whereKey($ticket->getKey())
                ->with('favor')
                ->lockForUpdate()
                ->firstOrFail();

            $this->condicoes->executar($ticket, $solicitante, $inicioPrevisto, $momento);

            $favor = $ticket->favor;

            $solicitacao = new Solicitacao();
            $solicitacao->forceFill([
                'ticket_id' => $ticket->id,
                'solicitante_id' => $solicitante->id,
                'ajudante_id' => $favor->proficienciaUsuario->usuario_id,
                'estado' => Solicitacao::SOLICITADA,
                'inicio_previsto' => $inicioPrevisto,
                'fim_previsto' => VerificarCondicoesPublicadas::fimPrevisto($favor, $inicioPrevisto),
            ]);

            try {
                $solicitacao->save();
            } catch (UniqueConstraintViolationException) {
                throw new LogicException(
                    "O ticket {$ticket->codigo} já tem uma solicitação em andamento. "
                    .'Um ticket vale por uma realização.'
                );
            }

            // P2 — nem o nascimento da solicitação fica sem rastro.
            TransicaoSolicitacao::create([
                'solicitacao_id' => $solicitacao->id,
                'de_estado' => null,
                'para_estado' => Solicitacao::SOLICITADA,
                'evento' => 'solicitacao_enviada',
                'ator_id' => $solicitante->id,
                'ocorrido_em' => $momento,
            ]);

            return $solicitacao;
        });
    }
}
