<?php

namespace App\Dominio\Seguranca;

use App\Dominio\Saida\Despachar;
use App\Dominio\Saida\Mensagem;
use App\Models\ContatoConfianca;
use App\Models\Favor;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Support\Carbon;

/**
 * Avisos ao contato de confiança (RF-SEG-02, RF-SEG-10, RF-NOT-08, RF-NOT-11).
 *
 * É o único mecanismo do produto que age **fora da plataforma** em situação de
 * risco, e o único endereçado a alguém que não tem conta. Por isso não passa
 * por `Notificar`, que fala com contas, e sim pela camada de saída, que fala
 * com endereços (05 §14.3).
 *
 * Três cuidados que não são detalhe:
 *
 *  - **A recusa vale sempre.** Quem pediu para não receber não recebe, nem em
 *    emergência. Consentimento que se ignora quando convém não é consentimento.
 *  - **A primeira mensagem se apresenta.** A pessoa não sabe o que é o
 *    Ajude.me e nunca aceitou termo algum: mandar só "fulano marcou às 16h"
 *    seria assustador e inútil.
 *  - **Falhar aqui é incidente, não log.** NF-DIS-03. O envio fica registrado
 *    como falho e visível, porque ninguém descobre em tempo que o aviso de
 *    segurança não saiu.
 */
class AvisarContatoDeConfianca
{
    public function __construct(
        private readonly Despachar $despachar,
    ) {
    }

    /**
     * RF-SEG-02 e RF-NOT-08 — atendimento presencial marcado.
     *
     * Os dois lados são avisados: cada contato recebe o nome e o endereço da
     * **contraparte**, que é onde a pessoa dele pode estar.
     *
     * @return int quantos avisos saíram
     */
    public function doAgendamento(Solicitacao $solicitacao, ?Carbon $momento = null): int
    {
        if ($solicitacao->ticket->favor->modalidade !== Favor::MODALIDADE_PRESENCIAL) {
            return 0;
        }

        $momento ??= Carbon::now();
        $saíram = 0;

        foreach ([$solicitacao->solicitante, $solicitacao->ajudante] as $pessoa) {
            $contraparte = $solicitacao->contraparteDe($pessoa);

            $texto = sprintf(
                '%s marcou um atendimento com %s em %s, às %s. %s',
                $pessoa->nome,
                $contraparte->nome,
                $solicitacao->inicio_previsto->format('d/m'),
                $solicitacao->inicio_previsto->format('H\hi'),
                $this->onde($contraparte),
            );

            $saíram += $this->enviar(
                $pessoa,
                $texto,
                "agendamento-{$solicitacao->id}-{$pessoa->id}",
                $solicitacao,
                $momento,
            ) ? 1 : 0;
        }

        return $saíram;
    }

    /**
     * RF-SEG-10 e RF-NOT-11 — o check-out não veio.
     *
     * Registro de saída que ninguém observa não protege ninguém: a ausência
     * dele é justamente o sinal relevante.
     */
    public function daAusenciaDeCheckout(
        Solicitacao $solicitacao,
        Usuario $pessoa,
        ?Carbon $momento = null,
    ): bool {
        $momento ??= Carbon::now();
        $contraparte = $solicitacao->contraparteDe($pessoa);

        $texto = sprintf(
            'O atendimento de %s com %s era para ter terminado às %s e ainda não foi encerrado no '
            .'aplicativo. Pode ser esquecimento. Se puder, ligue para %s.',
            $pessoa->nome,
            $contraparte->nome,
            $solicitacao->fim_previsto->format('H\hi'),
            $pessoa->telefone,
        );

        return $this->enviar(
            $pessoa,
            $texto,
            "checkout-ausente-{$solicitacao->id}-{$pessoa->id}",
            $solicitacao,
            $momento,
        );
    }

    private function enviar(
        Usuario $pessoa,
        string $texto,
        string $chave,
        Solicitacao $solicitacao,
        Carbon $momento,
    ): bool {
        $contato = $pessoa->contatoConfianca;

        if ($contato === null || ! $contato->podeReceberAviso()) {
            return false;
        }

        $corpo = $contato->precisaDeApresentacao()
            ? $this->apresentacao($pessoa).' '.$texto
            : 'Ajude.me: '.$texto;

        $this->despachar->executar(
            Mensagem::sms(
                destino: $contato->telefone,
                tipo: 'aviso_contato_confianca',
                corpo: $corpo,
                referencia: $solicitacao,
                chaveIdempotencia: $chave,
            ),
            $momento,
        );

        if ($contato->precisaDeApresentacao()) {
            $contato->forceFill(['avisado_em' => $momento])->save();
        }

        return true;
    }

    /**
     * A primeira mensagem diz o que é isto, quem indicou e como sair.
     *
     * Sem isso, a pessoa recebe um endereço de estranho vindo de um número
     * desconhecido — e a saída dela é bloquear o remetente, o que desliga
     * justamente o canal de segurança.
     */
    private function apresentacao(Usuario $pessoa): string
    {
        return sprintf(
            'Ajude.me: %s indicou você como contato de confiança. Avisamos você quando %s '
            .'marcar um atendimento presencial, para que alguém saiba onde ela está. '
            .'Para não receber mais, responda SAIR.',
            $pessoa->nome,
            mb_strtolower($this->primeiroNome($pessoa)) === '' ? 'essa pessoa' : $this->primeiroNome($pessoa),
        );
    }

    private function primeiroNome(Usuario $pessoa): string
    {
        return explode(' ', trim($pessoa->nome))[0] ?? '';
    }

    /**
     * O endereço da contraparte, que é onde a pessoa avisada pode estar.
     *
     * O modelo não registra em qual das duas casas o favor acontece — não há
     * campo para isso, e o aplicativo já resolve mostrando o endereço do outro
     * lado. Lacuna anotada; enquanto durar, este aviso segue a mesma regra.
     */
    private function onde(Usuario $contraparte): string
    {
        $endereco = $contraparte->endereco;

        if ($endereco === null) {
            return 'O endereço não foi informado.';
        }

        return sprintf(
            'Endereco: %s, %s - %s, %s.',
            $endereco->logradouro,
            $endereco->numero,
            $endereco->bairro,
            $endereco->municipio,
        );
    }
}
