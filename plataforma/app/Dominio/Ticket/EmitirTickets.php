<?php

namespace App\Dominio\Ticket;

use App\Models\Favor;
use App\Models\ParametroNegocio;
use App\Models\Ticket;
use App\Models\TransferenciaTicket;
use App\Models\TransicaoTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Emissão de tickets a partir de um favor (RF-FAV-07).
 *
 * Fica em uma ação, e não no modelo, porque a operação atravessa três coisas
 * ao mesmo tempo: conta vagas do favor, congela a condição publicada e cria
 * tickets com a cadeia de propriedade. É a fronteira onde a invariante I4
 * pode ser violada por concorrência.
 */
class EmitirTickets
{
    /**
     * @return array<int, Ticket>
     */
    public function executar(Favor $favor, int $quantidade, ?Carbon $momento = null): array
    {
        if ($quantidade < 1) {
            throw new LogicException('A quantidade de tickets a emitir precisa ser positiva.');
        }

        $momento ??= Carbon::now();

        return DB::transaction(function () use ($favor, $quantidade, $momento) {
            /**
             * I4 — tickets em circulação nunca excedem o máximo do favor.
             *
             * O bloqueio da linha do favor é o que impede duas emissões
             * simultâneas de lerem a mesma contagem e gravarem por cima
             * (05 §7.2). Sem ele, duas requisições com uma vaga disponível
             * criariam dois tickets.
             *
             * A contagem deriva dos tickets, que são a fonte de verdade.
             * Um contador materializado seria uma segunda verdade a divergir.
             */
            $favorTravado = Favor::query()
                ->whereKey($favor->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $favorTravado->podeEmitirTicket()) {
                throw new LogicException(
                    'Este favor não pode emitir tickets: verifique se está ativo, '
                    .'se a proficiência não foi restrita pela curadoria e se a conta está ativa.'
                );
            }

            $emCirculacao = Ticket::query()
                ->where('favor_id', $favorTravado->id)
                ->whereIn('estado', Ticket::EM_CIRCULACAO)
                ->count();

            $vagas = $favorTravado->maximo_circulacao - $emCirculacao;

            if ($quantidade > $vagas) {
                throw new LogicException(
                    "O favor comporta {$favorTravado->maximo_circulacao} tickets em circulação "
                    ."e já possui {$emCirculacao}. Vagas disponíveis: {$vagas}."
                );
            }

            $this->garantirValidadeDentroDoTeto($favorTravado, $momento);

            // RF-FAV-10 — a condição publicada congela na primeira emissão.
            $favorTravado->congelar();

            $ajudante = $favorTravado->proficienciaUsuario->usuario;
            $expiraEm = $momento->copy()->addDays($favorTravado->validade_ticket_dias);

            $tickets = [];

            for ($i = 0; $i < $quantidade; $i++) {
                $ticket = new Ticket();
                $ticket->forceFill([
                    'codigo' => $this->gerarCodigo(),
                    'favor_id' => $favorTravado->id,
                    'proprietario_id' => $ajudante->id,
                    'estado' => Ticket::DISPONIVEL,
                    'emitido_em' => $momento,
                    'expira_em' => $expiraEm,
                ])->save();

                // I8 — o ticket nasce vindo de lugar nenhum.
                TransferenciaTicket::create([
                    'ticket_id' => $ticket->id,
                    'de_usuario_id' => null,
                    'para_usuario_id' => $ajudante->id,
                    'motivo' => TransferenciaTicket::MOTIVO_EMISSAO,
                    'ocorrido_em' => $momento,
                ]);

                // P2 — nem o nascimento fica sem rastro.
                TransicaoTicket::create([
                    'ticket_id' => $ticket->id,
                    'de_estado' => null,
                    'para_estado' => Ticket::DISPONIVEL,
                    'evento' => 'emissao',
                    'ator_id' => $ajudante->id,
                    'ocorrido_em' => $momento,
                ]);

                $tickets[] = $ticket;
            }

            return $tickets;
        });
    }

    /**
     * RF-FAV-21 — a validade do ticket respeita o teto vigente.
     *
     * O teto vem de ParametroNegocio e não do código, porque prazo é
     * parâmetro (P6). A verificação acontece na emissão, e não na criação do
     * favor, para que o valor consultado seja o vigente no momento em que o
     * direito nasce.
     */
    private function garantirValidadeDentroDoTeto(Favor $favor, Carbon $momento): void
    {
        $teto = ParametroNegocio::inteiroEm(
            ParametroNegocio::VALIDADE_MAXIMA_TICKET_DIAS,
            $momento
        );

        if ($teto !== null && $favor->validade_ticket_dias > $teto) {
            throw new LogicException(
                "A validade do ticket ({$favor->validade_ticket_dias} dias) excede o teto vigente de {$teto} dias."
            );
        }
    }

    private function gerarCodigo(): string
    {
        do {
            $codigo = 'TK-'.strtoupper(base_convert((string) random_int(100000, 9999999), 10, 36));
        } while (Ticket::where('codigo', $codigo)->exists());

        return $codigo;
    }
}
