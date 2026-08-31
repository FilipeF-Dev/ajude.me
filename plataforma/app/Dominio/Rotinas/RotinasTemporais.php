<?php

namespace App\Dominio\Rotinas;

use App\Dominio\Consumo\ApurarRealizacao;
use App\Dominio\Descoberta\ApagarTextoDasBuscas;
use App\Dominio\Descoberta\AvisarProficienciaDisponivel;
use App\Dominio\Identidade\EncerrarConta;
use App\Dominio\Negociacao\EncerrarProposta;
use App\Dominio\Notificacao\Notificar;
use App\Dominio\Reputacao\AvaliacaoPendente;
use App\Dominio\Seguranca\AvisarContatoDeConfianca;
use App\Models\Favor;
use App\Models\ItemProposta;
use App\Models\ParametroNegocio;
use App\Models\RegistroComparecimento;
use App\Models\Notificacao;
use App\Models\Proposta;
use App\Models\Solicitacao;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Support\Carbon;

/**
 * O que o tempo faz sozinho.
 *
 * Este sistema tem mais trabalho agendado do que aparenta (05 §15.2): fechar
 * janela de contestação, aplicar confirmação tácita, avaliar expiração de
 * ticket, cobrar avaliação pendente, cumprir fila de aviso, apagar texto de
 * busca vencido.
 *
 * Nada aqui decide nada de novo. Cada rotina apenas **chama a ação de
 * domínio** que já sabe decidir, no momento em que a condição passa a valer.
 * Concentrar as chamadas em um lugar torna visível o conjunto do que corre
 * sem ninguém clicar — e é esse conjunto que faz a plataforma funcionar
 * quando ninguém está olhando.
 *
 * A ordem importa: propostas são encerradas antes de os tickets expirarem,
 * para que um ticket vencido não deixe proposta pendurada.
 *
 * @return array<string, int> o que cada passo fez, para registro e diagnóstico
 */
class RotinasTemporais
{
    public function __construct(
        private readonly EncerrarProposta $propostas = new EncerrarProposta(),
        private readonly ApurarRealizacao $realizacoes = new ApurarRealizacao(),
        private readonly AvaliacaoPendente $avaliacoes = new AvaliacaoPendente(),
        private readonly AvisarProficienciaDisponivel $avisos = new AvisarProficienciaDisponivel(),
        private readonly ApagarTextoDasBuscas $retencao = new ApagarTextoDasBuscas(),
        private readonly Notificar $notificacoes = new Notificar(),
        private readonly EncerrarConta $encerramentos = new EncerrarConta(),
        // Resolvido pelo contêiner quando não injetado: depende do canal de
        // saída configurado, que não cabe em valor padrão de parâmetro.
        private readonly ?AvisarContatoDeConfianca $avisosDeSeguranca = null,
    ) {
    }

    /** @return array<string, int> */
    public function executar(?Carbon $momento = null): array
    {
        $momento ??= Carbon::now();

        return [
            'propostas_expiradas' => $this->expirarPropostas($momento),
            'propostas_por_ticket_vencido' => $this->cancelarPropostasComTicketVencido($momento),
            'tickets_expirados' => $this->expirarTickets($momento),
            'janelas_abertas' => $this->abrirJanelasDeContestacao($momento),
            'confirmacoes_tacitas' => $this->aplicarConfirmacaoTacita($momento),
            'avisos_de_avaliacao' => $this->avisarAvaliacaoPendente($momento),
            'avisos_de_proficiencia' => $this->avisos->executar($momento),
            'textos_de_busca_apagados' => $this->retencao->executar($momento),
            'contas_encerradas' => $this->concluirEncerramentos($momento),
            'avisos_de_checkout' => $this->avisarCheckoutAusente($momento),
        ];
    }

    /**
     * RF-SEG-10 e RF-NOT-11 — o check-out que não veio.
     *
     * Registro de saída que ninguém observa não protege ninguém: a ausência
     * dele é o sinal. Avisa apenas quem **fez check-in e não fez check-out**,
     * passada a tolerância — quem nunca entrou provavelmente não foi, e isso
     * é outro problema.
     */
    private function avisarCheckoutAusente(Carbon $momento): int
    {
        $tolerancia = ParametroNegocio::inteiroEm(
            ParametroNegocio::CHECKOUT_ATRASO_MINUTOS,
            $momento,
        ) ?? 60;

        $avisos = 0;

        Solicitacao::query()
            ->whereIn('estado', [Solicitacao::AGENDADA, Solicitacao::AGUARDANDO_CONFIRMACAO])
            ->where('fim_previsto', '<=', $momento->copy()->subMinutes($tolerancia))
            ->with(['comparecimentos', 'solicitante', 'ajudante', 'ticket.favor'])
            ->each(function (Solicitacao $solicitacao) use ($momento, &$avisos) {
                if ($solicitacao->ticket->favor->modalidade !== Favor::MODALIDADE_PRESENCIAL) {
                    return;
                }

                foreach ([Solicitacao::PARTE_SOLICITANTE, Solicitacao::PARTE_AJUDANTE] as $parte) {
                    $entrou = $solicitacao->comparecimentos
                        ->where('parte', $parte)
                        ->where('tipo', RegistroComparecimento::INICIO)
                        ->isNotEmpty();

                    $saiu = $solicitacao->comparecimentos
                        ->where('parte', $parte)
                        ->where('tipo', RegistroComparecimento::FIM)
                        ->isNotEmpty();

                    if (! $entrou || $saiu) {
                        continue;
                    }

                    $pessoa = $parte === Solicitacao::PARTE_SOLICITANTE
                        ? $solicitacao->solicitante
                        : $solicitacao->ajudante;

                    // A idempotência da camada de saída impede repetir o
                    // mesmo aviso a cada dez minutos enquanto o check-out
                    // não vier.
                    $avisador = $this->avisosDeSeguranca ?? app(AvisarContatoDeConfianca::class);

                    if ($avisador->daAusenciaDeCheckout($solicitacao, $pessoa, $momento)) {
                        $avisos++;
                    }
                }
            });

        return $avisos;
    }

    /**
     * RF-CAD-15 — a conta em encerramento sai quando não deve mais nada.
     *
     * Vem depois da expiração de tickets, e a ordem não é acidental: o último
     * ticket de quem está saindo costuma sair de cena por vencimento, e
     * vencimento não avisa ninguém. Sem esta rotina, a conta ficaria presa
     * até alguém tocar nela por acaso.
     */
    private function concluirEncerramentos(Carbon $momento): int
    {
        $encerradas = 0;

        Usuario::query()
            ->where('estado_conta', Usuario::ESTADO_ENCERRANDO)
            ->orderBy('id')
            ->each(function (Usuario $usuario) use ($momento, &$encerradas) {
                if ($this->encerramentos->concluir($usuario, $momento)) {
                    $encerradas++;
                }
            });

        return $encerradas;
    }

    /** RF-NEG-13 — esgotado o prazo, os tickets são liberados. */
    private function expirarPropostas(Carbon $momento): int
    {
        $vencidas = Proposta::query()
            ->whereIn('estado', Proposta::ATIVA)
            ->where('expira_em', '<=', $momento)
            ->get();

        foreach ($vencidas as $proposta) {
            $this->propostas->expirar($proposta, $momento);

            $this->notificacoes->enviarA(
                [$proposta->remetente, $proposta->destinatario],
                Notificacao::PROPOSTA_RESPONDIDA,
                $proposta,
                ['desfecho' => Proposta::EXPIRADA],
                $momento,
            );
        }

        return $vencidas->count();
    }

    /**
     * 02 §5.4 — ticket que vence durante a negociação derruba a proposta.
     *
     * Roda antes da expiração dos tickets: encerrada a proposta, o ticket
     * volta a `DISPONIVEL` e o passo seguinte pode expirá-lo pelo caminho
     * normal, sem transição inválida no meio.
     */
    private function cancelarPropostasComTicketVencido(Carbon $momento): int
    {
        $emNegociacaoVencidos = Ticket::query()
            ->where('estado', Ticket::EM_NEGOCIACAO)
            ->where('expira_em', '<=', $momento)
            ->pluck('id');

        if ($emNegociacaoVencidos->isEmpty()) {
            return 0;
        }

        $propostas = Proposta::query()
            ->whereIn('estado', Proposta::ATIVA)
            ->whereExists(fn ($sub) => $sub
                ->selectRaw('1')
                ->from('itens_proposta')
                ->join('versoes_proposta', 'versoes_proposta.id', '=', 'itens_proposta.versao_proposta_id')
                ->whereColumn('versoes_proposta.proposta_id', 'propostas.id')
                ->whereColumn('versoes_proposta.numero', 'propostas.versao_atual')
                ->whereIn('itens_proposta.ticket_id', $emNegociacaoVencidos))
            ->get();

        foreach ($propostas as $proposta) {
            $this->propostas->expirarPorTicketVencido($proposta, $momento);
        }

        return $propostas->count();
    }

    /**
     * A6 — expiração é avaliação de estado, não evento agendado por ticket.
     *
     * A rotina não decide quem expira: pergunta a cada ticket se ele deve.
     * Reservado e em análise seguem protegidos (I6, I7) sem que esta rotina
     * precise saber disso.
     */
    private function expirarTickets(Carbon $momento): int
    {
        $candidatos = Ticket::query()
            ->whereIn('estado', Ticket::EM_CIRCULACAO)
            ->where('expira_em', '<=', $momento)
            ->with('favor')
            ->get();

        $expirados = 0;

        foreach ($candidatos as $ticket) {
            if (! $ticket->deveExpirar($momento)) {
                continue;
            }

            $ticket->transicionar(Ticket::EXPIRADO, 'validade_alcancada', momento: $momento);

            $this->notificacoes->enviarUmaVez(
                $ticket->proprietario,
                Notificacao::TICKET_EXPIRADO,
                $ticket,
                [],
                $momento,
            );

            $expirados++;
        }

        return $expirados;
    }

    /** RF-SOL-08 e RF-NOT-12 — alcançada a data, convida-se a confirmar. */
    private function abrirJanelasDeContestacao(Carbon $momento): int
    {
        $vencidas = Solicitacao::query()
            ->where('estado', Solicitacao::AGENDADA)
            ->where('fim_previsto', '<=', $momento)
            ->with(['ajudante', 'solicitante'])
            ->get();

        foreach ($vencidas as $solicitacao) {
            $this->realizacoes->abrirJanela($solicitacao, $momento);

            $this->notificacoes->enviarA(
                [$solicitacao->ajudante, $solicitacao->solicitante],
                Notificacao::CONFIRMACAO_PENDENTE,
                $solicitacao,
                [],
                $momento,
            );
        }

        return $vencidas->count();
    }

    /**
     * RF-SOL-10 e 05 §7.5 — a confirmação tácita é apurada **após** o limite.
     *
     * A contestada não entra: ela já saiu de `AGUARDANDO_CONFIRMACAO` no ato
     * da contestação, e o filtro por estado basta para excluí-la.
     */
    private function aplicarConfirmacaoTacita(Carbon $momento): int
    {
        $encerradas = Solicitacao::query()
            ->where('estado', Solicitacao::AGUARDANDO_CONFIRMACAO)
            ->where('janela_contestacao_ate', '<=', $momento)
            ->get();

        foreach ($encerradas as $solicitacao) {
            $this->realizacoes->apurarConfirmacaoTacita($solicitacao, $momento);
        }

        return $encerradas->count();
    }

    /**
     * RF-AVA-12, RF-NOT-13 e RF-NOT-14 — avisar antes de restringir.
     *
     * Aplicar a restrição sem ter avisado transformaria a regra em armadilha.
     * Quem já está em atraso recebe o aviso da restrição; quem ainda está no
     * prazo recebe a cobrança.
     */
    private function avisarAvaliacaoPendente(Carbon $momento): int
    {
        $avisados = 0;

        $comPendencia = Usuario::query()
            ->whereExists(fn ($sub) => $sub
                ->selectRaw('1')
                ->from('solicitacoes')
                ->where('solicitacoes.estado', Solicitacao::REALIZADA)
                ->where(fn ($parte) => $parte
                    ->whereColumn('solicitacoes.ajudante_id', 'usuarios.id')
                    ->orWhereColumn('solicitacoes.solicitante_id', 'usuarios.id')))
            ->get();

        foreach ($comPendencia as $usuario) {
            foreach ($this->avaliacoes->aAvisar($usuario, $momento) as $solicitacao) {
                $enviada = $this->notificacoes->enviarUmaVez(
                    $usuario,
                    Notificacao::AVALIACAO_PENDENTE,
                    $solicitacao,
                    [],
                    $momento,
                );

                $avisados += $enviada === null ? 0 : 1;
            }

            foreach ($this->avaliacoes->emAtraso($usuario, $momento) as $solicitacao) {
                $enviada = $this->notificacoes->enviarUmaVez(
                    $usuario,
                    Notificacao::RESTRICAO_POR_AVALIACAO,
                    $solicitacao,
                    [],
                    $momento,
                );

                $avisados += $enviada === null ? 0 : 1;
            }
        }

        return $avisados;
    }
}
