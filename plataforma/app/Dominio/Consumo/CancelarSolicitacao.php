<?php

namespace App\Dominio\Consumo;

use App\Dominio\Moderacao\RegistrarOcorrencia;
use App\Dominio\Notificacao\Notificar;
use App\Models\Notificacao;
use App\Models\Ocorrencia;
use App\Models\ParametroNegocio;
use App\Models\Solicitacao;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Cancelamento da solicitação pelos dois lados (02 §9).
 *
 * Em qualquer dos casos o ticket volta a `DISPONIVEL`: o direito não se
 * extingue porque um encontro não aconteceu (05 §6.1, RF-CAN-05).
 *
 * O sistema **não bloqueia** o cancelamento do solicitante, mesmo em cima da
 * hora (RF-CAN-07). Bloquear não faz ninguém comparecer — apenas converte um
 * aviso em ausência sem aviso, que prejudica mais o ajudante (02 §9.2).
 *
 * A ocorrência é registrada dentro da mesma transação do cancelamento
 * (RF-CAN-06, RF-CAN-15): cancelar sem deixar o registro faria o histórico
 * mentir sobre o que aconteceu.
 */
class CancelarSolicitacao
{
    public function __construct(
        private readonly RegistrarOcorrencia $ocorrencias = new RegistrarOcorrencia(),
    ) {
    }

    /**
     * RF-CAN-01 — o cancelamento pelo ajudante exige justificativa.
     *
     * E gera ocorrência sempre: quem se comprometeu a atender e desmarcou
     * responde por isso, independentemente da antecedência (02 §9.1).
     */
    public function peloAjudante(
        Solicitacao $solicitacao,
        Usuario $ajudante,
        string $justificativa,
        ?Carbon $momento = null,
    ): Solicitacao {
        $justificativa = trim($justificativa);

        if ($justificativa === '') {
            throw new LogicException('O cancelamento pelo ajudante exige justificativa.');
        }

        return $this->cancelar(
            $solicitacao,
            $ajudante,
            Solicitacao::CANCELADA_AJUDANTE,
            $justificativa,
            $momento,
        );
    }

    /**
     * RF-CAN-07 — o solicitante cancela a qualquer momento.
     *
     * A justificativa é opcional aqui, ao contrário do cancelamento pelo
     * ajudante: 02 §9.2 proíbe criar obstáculo, e exigir texto de quem
     * desiste é obstáculo.
     */
    public function peloSolicitante(
        Solicitacao $solicitacao,
        Usuario $solicitante,
        ?string $justificativa = null,
        ?Carbon $momento = null,
    ): Solicitacao {
        return $this->cancelar(
            $solicitacao,
            $solicitante,
            Solicitacao::CANCELADA_SOLICITANTE,
            $justificativa === null ? null : (trim($justificativa) ?: null),
            $momento,
        );
    }

    /**
     * RF-CAN-08, RF-CAN-15 e RF-CAN-16 — a antecedência separa o
     * cancelamento sem consequência daquele que registra ocorrência.
     *
     * Responder isso **antes** de confirmar é o que RF-CAN-16 exige: quem
     * cancela precisa saber o que vai acontecer antes de decidir.
     *
     * A antecedência do ajudante não entra na conta: o cancelamento dele
     * gera ocorrência em qualquer prazo (02 §9.1).
     */
    public function geraOcorrencia(
        Solicitacao $solicitacao,
        Usuario $quemCancela,
        ?Carbon $momento = null,
    ): bool {
        if ($solicitacao->papelDe($quemCancela) === Solicitacao::PARTE_AJUDANTE) {
            return true;
        }

        $momento ??= Carbon::now();

        $horas = ParametroNegocio::inteiroEm(
            ParametroNegocio::CANCELAMENTO_SEM_OCORRENCIA_HORAS,
            $solicitacao->created_at,
        );

        if ($horas === null) {
            throw new LogicException(
                'O parâmetro de antecedência de cancelamento não está definido. '
                .'Rode o ParametrosNegocioSeeder.'
            );
        }

        return $momento->greaterThan($solicitacao->inicio_previsto->copy()->subHours($horas));
    }

    private function cancelar(
        Solicitacao $solicitacao,
        Usuario $autor,
        string $estadoFinal,
        ?string $justificativa,
        ?Carbon $momento,
    ): Solicitacao {
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($solicitacao, $autor, $estadoFinal, $justificativa, $momento) {
            $solicitacao = Solicitacao::query()
                ->whereKey($solicitacao->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $papelEsperado = $estadoFinal === Solicitacao::CANCELADA_AJUDANTE
                ? Solicitacao::PARTE_AJUDANTE
                : Solicitacao::PARTE_SOLICITANTE;

            if ($solicitacao->papelDe($autor) !== $papelEsperado) {
                throw new LogicException("Este cancelamento cabe ao {$papelEsperado}.");
            }

            if (! $solicitacao->podeSerTransicionadaPara($estadoFinal)) {
                throw new LogicException(
                    "A solicitação está em \"{$solicitacao->estado}\" e não pode ser cancelada."
                );
            }

            $estadoAnterior = $solicitacao->estado;

            // Apurado antes da transição: depois do cancelamento a pergunta
            // "qual era o papel de quem cancelou" continua respondível, mas a
            // resposta de RF-CAN-16 precisa ser a mesma que foi mostrada à
            // pessoa antes de ela confirmar.
            $geraOcorrencia = $this->geraOcorrencia($solicitacao, $autor, $momento);

            $solicitacao->forceFill([
                'justificativa_cancelamento' => $justificativa,
                'cancelada_por_id' => $autor->id,
            ])->save();

            $solicitacao->transicionar($estadoFinal, $estadoFinal, $autor->id, $momento);

            if ($geraOcorrencia) {
                $ehAjudante = $estadoFinal === Solicitacao::CANCELADA_AJUDANTE;

                $this->ocorrencias->peloSistema(
                    $ehAjudante
                        ? Ocorrencia::CANCELAMENTO_DO_AJUDANTE
                        : Ocorrencia::CANCELAMENTO_EM_CIMA_DA_HORA,
                    $autor,
                    $solicitacao,
                    $justificativa,
                    $momento,
                );
            }

            // O ticket só estava reservado se houve agendamento; cancelar um
            // pedido ainda não confirmado não tem reserva a desfazer.
            if ($estadoAnterior === Solicitacao::AGENDADA) {
                Ticket::query()
                    ->whereKey($solicitacao->ticket_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->transicionar(Ticket::DISPONIVEL, $estadoFinal, $autor->id, $momento);
            }

            // RF-NOT-07 — quem contava com o encontro precisa saber que ele
            // não vai acontecer, e com que justificativa.
            (new Notificar())->enviar(
                $solicitacao->contraparteDe($autor),
                Notificacao::PRESTACAO_CANCELADA,
                $solicitacao,
                ['justificativa' => $justificativa],
                $momento,
            );

            return $solicitacao;
        });
    }
}
