<?php

namespace App\Dominio\Negociacao;

use App\Dominio\Reputacao\AvaliacaoPendente;
use App\Models\ItemProposta;
use App\Models\ParametroNegocio;
use App\Models\Proposta;
use App\Models\Ticket;
use App\Models\Usuario;
use App\Models\VersaoProposta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Envio de uma proposta de troca (RF-NEG-01, RF-NEG-02, RF-NEG-05).
 *
 * O envio faz três coisas que precisam acontecer juntas ou não acontecer:
 * cria a versão 1 da oferta, bloqueia todos os tickets envolvidos e dá prazo
 * à proposta. Bloquear sem criar a versão prenderia tickets órfãos; criar a
 * versão sem bloquear deixaria o mesmo ticket em duas propostas (I14).
 *
 * RF-NEG-14 e I17 — a ausência de bloqueio entre as partes é conferida em
 * `VerificarComposicao`, junto com o restante da elegibilidade.
 */
class EnviarProposta
{
    public function __construct(
        private readonly VerificarComposicao $verificador = new VerificarComposicao(),
        private readonly AvaliacaoPendente $avaliacoes = new AvaliacaoPendente(),
    ) {
    }

    public function executar(
        Usuario $remetente,
        Usuario $destinatario,
        Composicao $composicao,
        ?Carbon $momento = null,
    ): Proposta {
        if ($remetente->id === $destinatario->id) {
            throw new LogicException('Não se negocia consigo mesmo.');
        }

        $momento ??= Carbon::now();

        /**
         * RF-NEG-15 e RF-AVA-11 — quem tem avaliação vencida não envia
         * proposta nova.
         *
         * A restrição é sobre **enviar**, não sobre aceitar nem revisar:
         * 02 §11.4 quer destravar o ciclo de quem está esperando, e travar a
         * resposta a uma proposta já enviada prenderia tickets de terceiros
         * por conta de uma pendência que não é deles.
         */
        if ($this->avaliacoes->restringeNovasPropostas($remetente, $momento)) {
            throw new LogicException(
                'Você tem avaliação pendente além do prazo. '
                .'Avalie os favores concluídos para voltar a enviar propostas.'
            );
        }

        return DB::transaction(function () use ($remetente, $destinatario, $composicao, $momento) {
            $tickets = $this->verificador->executar(
                $composicao,
                $remetente,
                $destinatario,
                momento: $momento,
            );

            $proposta = new Proposta();
            $proposta->forceFill([
                'remetente_id' => $remetente->id,
                'destinatario_id' => $destinatario->id,
                'estado' => Proposta::ENVIADA,
                'versao_atual' => 1,
                'enviada_em' => $momento,
                'expira_em' => $this->prazoDeResposta($momento),
            ])->save();

            $versao = VersaoProposta::create([
                'proposta_id' => $proposta->id,
                'numero' => 1,
                'criada_por_id' => $remetente->id,
                'criada_em' => $momento,
            ]);

            foreach ($composicao->todos() as $ticketId) {
                ItemProposta::create([
                    'versao_proposta_id' => $versao->id,
                    'ticket_id' => $ticketId,
                    'lado' => $composicao->lado($ticketId),
                ]);

                // RF-NEG-05 — o bloqueio é a mudança de estado do próprio
                // ticket, não uma marca à parte. Assim I14 é apurada pela
                // mesma máquina de estados que rege todo o resto (P2).
                $tickets->get($ticketId)->transicionar(
                    Ticket::EM_NEGOCIACAO,
                    'proposta_enviada',
                    $remetente->id,
                );
            }

            return $proposta;
        });
    }

    /**
     * RF-NEG-13 — a proposta tem prazo, e o prazo é parâmetro (P6).
     *
     * Consultado no instante do envio: uma proposta aberta sob validade de
     * 7 dias continua valendo 7 dias ainda que o parâmetro mude depois (05 §9).
     */
    private function prazoDeResposta(Carbon $momento): Carbon
    {
        $dias = ParametroNegocio::inteiroEm(
            ParametroNegocio::VALIDADE_PROPOSTA_DIAS,
            $momento
        );

        if ($dias === null) {
            throw new LogicException(
                'O parâmetro de validade da proposta não está definido. '
                .'Rode o ParametrosNegocioSeeder.'
            );
        }

        return $momento->copy()->addDays($dias);
    }
}
