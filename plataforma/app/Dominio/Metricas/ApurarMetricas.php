<?php

namespace App\Dominio\Metricas;

use App\Models\Proposta;
use App\Models\Solicitacao;
use App\Models\Ticket;
use App\Models\TransferenciaTicket;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Apura as métricas de 03 §7 e 06 §10.2.
 *
 * Sobre a forma dos números, e não só sobre o cálculo:
 *
 * Não existe referência publicada para redes de troca de favores sem dinheiro
 * em comunidades de baixa renda no Brasil. Fixar limiar absoluto agora seria
 * inventar. Por isso a apuração é **por coorte mensal** (06 §10.1): a pergunta
 * que importa não é "qual o número", e sim "a turma que entrou este mês está
 * melhor que a do mês passado".
 *
 * Critério direcional também resiste ao viés dos primeiros usuários, que são
 * sempre os mais entusiasmados e puxam qualquer média para cima.
 */
class ApurarMetricas
{
    /**
     * Recorte do período inteiro, para leitura rápida.
     *
     * @return array<string, int|float|null>
     */
    public function panorama(?Carbon $de = null, ?Carbon $ate = null): array
    {
        $de ??= Carbon::create(2000, 1, 1);
        $ate ??= Carbon::now();

        $ativos = $this->participantesAtivos($de, $ate);

        return [
            'participantes' => $ativos,
            'com_favor' => $this->participantesComFavor($de, $ate),
            'propostas_enviadas' => $this->propostasEnviadas($de, $ate),
            'taxa_aceite' => $this->taxaAceite($de, $ate),
            'consumos_concluidos' => $this->consumosConcluidos($de, $ate),
            'tickets_transferidos' => $this->ticketsTransferidos($de, $ate),
            'reciprocidade' => $this->reciprocidade($de, $ate),
            'profundidade_circulacao' => $this->profundidadeCirculacao($de, $ate),
            'taxa_cancelamento_ajudante' => $this->taxaCancelamento($de, $ate, Solicitacao::CANCELADA_AJUDANTE),
            'taxa_cancelamento_solicitante' => $this->taxaCancelamento($de, $ate, Solicitacao::CANCELADA_SOLICITANTE),
            'taxa_contestacao' => $this->taxaContestacao($de, $ate),
        ];
    }

    /**
     * A leitura que o roadmap manda fazer: coortes mensais lado a lado.
     *
     * Uma coorte é o conjunto de quem se cadastrou naquele mês, e cada
     * indicador olha o que **essa turma** fez — não o que a plataforma toda
     * fez no mês. É a diferença entre "o produto está melhorando" e "o produto
     * acumulou usuários".
     *
     * @return array<int, array<string, mixed>>
     */
    public function coortes(int $meses = 6): array
    {
        $inicio = Carbon::now()->startOfMonth()->subMonths($meses - 1);
        $coortes = [];

        for ($i = 0; $i < $meses; $i++) {
            $mes = $inicio->copy()->addMonths($i);
            $fimDoMes = $mes->copy()->endOfMonth();

            $integrantes = Usuario::query()
                ->whereBetween('created_at', [$mes, $fimDoMes])
                ->pluck('id');

            if ($integrantes->isEmpty()) {
                $coortes[] = [
                    'mes' => $mes->format('m/Y'),
                    'integrantes' => 0,
                    'com_favor' => null,
                    'reciprocidade' => null,
                    'dias_ate_primeira_troca' => null,
                ];

                continue;
            }

            $coortes[] = [
                'mes' => $mes->format('m/Y'),
                'integrantes' => $integrantes->count(),
                'com_favor' => $this->proporcaoComFavor($integrantes),
                'reciprocidade' => $this->proporcaoReciproca($integrantes),
                'dias_ate_primeira_troca' => $this->medianaDiasAtePrimeiraTroca($integrantes),
            ];
        }

        return $coortes;
    }

    private function participantesAtivos(Carbon $de, Carbon $ate): int
    {
        return Usuario::query()->whereBetween('created_at', [$de, $ate])->count();
    }

    /**
     * H1 — as pessoas conseguem descrever o que sabem fazer como favor.
     */
    private function participantesComFavor(Carbon $de, Carbon $ate): int
    {
        return Usuario::query()
            ->whereBetween('created_at', [$de, $ate])
            ->whereExists(function ($consulta) {
                $consulta->select(DB::raw(1))
                    ->from('favores')
                    ->join('proficiencias_usuario', 'proficiencias_usuario.id', '=', 'favores.proficiencia_usuario_id')
                    ->whereColumn('proficiencias_usuario.usuario_id', 'usuarios.id');
            })
            ->count();
    }

    private function propostasEnviadas(Carbon $de, Carbon $ate): int
    {
        return Proposta::query()
            ->whereNotNull('enviada_em')
            ->whereBetween('enviada_em', [$de, $ate])
            ->count();
    }

    /**
     * H3 — a troca sem dinheiro é aceita.
     *
     * Denominador são as propostas que chegaram a ser respondidas: incluir
     * as ainda pendentes puniria o indicador por propostas recentes.
     */
    private function taxaAceite(Carbon $de, Carbon $ate): ?float
    {
        $respondidas = Proposta::query()
            ->whereNotNull('enviada_em')
            ->whereBetween('enviada_em', [$de, $ate])
            ->whereIn('estado', [Proposta::ACEITA, Proposta::RECUSADA])
            ->count();

        if ($respondidas === 0) {
            return null;
        }

        $aceitas = Proposta::query()
            ->whereNotNull('enviada_em')
            ->whereBetween('enviada_em', [$de, $ate])
            ->where('estado', Proposta::ACEITA)
            ->count();

        return round($aceitas / $respondidas * 100, 1);
    }

    /**
     * H4 — o favor negociado acontece no mundo real.
     */
    private function consumosConcluidos(Carbon $de, Carbon $ate): int
    {
        return Solicitacao::query()
            ->whereBetween('created_at', [$de, $ate])
            ->whereIn('estado', [Solicitacao::REALIZADA, Solicitacao::ENCERRADA])
            ->count();
    }

    private function ticketsTransferidos(Carbon $de, Carbon $ate): int
    {
        return TransferenciaTicket::query()
            ->whereBetween('ocorrido_em', [$de, $ate])
            ->where('motivo', TransferenciaTicket::MOTIVO_PROPOSTA_ACEITA)
            ->count();
    }

    /**
     * H5 — a hipótese mais importante do produto (03 §2).
     *
     * Uma rede cheia de solicitantes e vazia de ajudantes fracassa mesmo com
     * cadastro alto, e falha de forma silenciosa: parece crescimento. Este é
     * o alarme do risco R2, e por isso é observado desde a primeira coorte.
     */
    private function reciprocidade(Carbon $de, Carbon $ate): ?float
    {
        $ids = Usuario::query()->whereBetween('created_at', [$de, $ate])->pluck('id');

        if ($ids->isEmpty()) {
            return null;
        }

        return $this->proporcaoReciproca($ids);
    }

    private function proporcaoReciproca($ids): ?float
    {
        if ($ids->isEmpty()) {
            return null;
        }

        $concluidas = [Solicitacao::REALIZADA, Solicitacao::ENCERRADA];

        $prestaram = Solicitacao::query()
            ->whereIn('ajudante_id', $ids)
            ->whereIn('estado', $concluidas)
            ->distinct()
            ->pluck('ajudante_id');

        $receberam = Solicitacao::query()
            ->whereIn('solicitante_id', $ids)
            ->whereIn('estado', $concluidas)
            ->distinct()
            ->pluck('solicitante_id');

        $ambos = $prestaram->intersect($receberam)->count();

        return round($ambos / $ids->count() * 100, 1);
    }

    private function proporcaoComFavor($ids): ?float
    {
        if ($ids->isEmpty()) {
            return null;
        }

        $comFavor = DB::table('favores')
            ->join('proficiencias_usuario', 'proficiencias_usuario.id', '=', 'favores.proficiencia_usuario_id')
            ->whereIn('proficiencias_usuario.usuario_id', $ids)
            ->distinct()
            ->count('proficiencias_usuario.usuario_id');

        return round($comFavor / $ids->count() * 100, 1);
    }

    /**
     * O indicador que testa o conceito central (06 §10.3).
     *
     * Quantas vezes um ticket muda de mãos antes de ser consumido. Se for
     * sempre 1, a transferibilidade não está em uso e o ticket é um vale
     * nominal: o produto funciona como catálogo de favores, não como rede de
     * circulação. É diferença estratégica, não detalhe.
     */
    private function profundidadeCirculacao(Carbon $de, Carbon $ate): ?float
    {
        $consumidos = Ticket::query()
            ->whereIn('estado', [Ticket::CONSUMIDO, Ticket::ENCERRADO])
            ->whereBetween('emitido_em', [$de, $ate])
            ->pluck('id');

        if ($consumidos->isEmpty()) {
            return null;
        }

        // A emissão não conta como troca: é o nascimento do ticket.
        $trocas = TransferenciaTicket::query()
            ->whereIn('ticket_id', $consumidos)
            ->where('motivo', TransferenciaTicket::MOTIVO_PROPOSTA_ACEITA)
            ->count();

        return round($trocas / $consumidos->count(), 2);
    }

    private function taxaCancelamento(Carbon $de, Carbon $ate, string $estado): ?float
    {
        $agendadas = Solicitacao::query()
            ->whereBetween('created_at', [$de, $ate])
            ->whereNotIn('estado', [Solicitacao::SOLICITADA, Solicitacao::RECUSADA])
            ->count();

        if ($agendadas === 0) {
            return null;
        }

        $canceladas = Solicitacao::query()
            ->whereBetween('created_at', [$de, $ate])
            ->where('estado', $estado)
            ->count();

        return round($canceladas / $agendadas * 100, 1);
    }

    /**
     * Quanto do encerramento depende de arbitragem humana. Sobe, e a operação
     * manual satura — que é o portão G3.3 do roadmap.
     */
    private function taxaContestacao(Carbon $de, Carbon $ate): ?float
    {
        $chegaramAoFim = Solicitacao::query()
            ->whereBetween('created_at', [$de, $ate])
            ->whereIn('estado', [
                Solicitacao::REALIZADA,
                Solicitacao::ENCERRADA,
                Solicitacao::EM_ANALISE,
                Solicitacao::NAO_REALIZADA,
                Solicitacao::INCONCLUSIVA,
            ])
            ->count();

        if ($chegaramAoFim === 0) {
            return null;
        }

        $contestadas = Solicitacao::query()
            ->whereBetween('created_at', [$de, $ate])
            ->whereIn('estado', [
                Solicitacao::EM_ANALISE,
                Solicitacao::NAO_REALIZADA,
                Solicitacao::INCONCLUSIVA,
            ])
            ->count();

        return round($contestadas / $chegaramAoFim * 100, 1);
    }

    /**
     * Mede atrito: quanto tempo entre entrar e a primeira troca fechada.
     * Mediana, não média — um caso extremo não deve mover o indicador.
     */
    private function medianaDiasAtePrimeiraTroca($ids): ?float
    {
        $dias = DB::table('usuarios')
            ->join('transferencias_ticket', function ($juncao) {
                $juncao->on('transferencias_ticket.para_usuario_id', '=', 'usuarios.id')
                    ->where('transferencias_ticket.motivo', '=', TransferenciaTicket::MOTIVO_PROPOSTA_ACEITA);
            })
            ->whereIn('usuarios.id', $ids)
            ->groupBy('usuarios.id', 'usuarios.created_at')
            ->selectRaw('EXTRACT(EPOCH FROM (MIN(transferencias_ticket.ocorrido_em) - usuarios.created_at)) / 86400 AS dias')
            ->pluck('dias')
            ->map(fn ($valor) => (float) $valor)
            ->sort()
            ->values();

        if ($dias->isEmpty()) {
            return null;
        }

        $meio = intdiv($dias->count(), 2);

        $mediana = $dias->count() % 2 === 1
            ? $dias[$meio]
            : ($dias[$meio - 1] + $dias[$meio]) / 2;

        return round($mediana, 1);
    }
}
