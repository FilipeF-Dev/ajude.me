<?php

namespace App\Dominio\Consumo;

use App\Models\Favor;
use App\Models\JanelaDisponibilidade;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * I18 e RF-SOL-02 — a solicitação respeita todas as condições publicadas.
 *
 * A condição publicada é a que congelou na primeira emissão (P5): quem
 * adquiriu o ticket contou com o que estava escrito, e é contra isso que a
 * solicitação é medida — nunca contra o favor como ele está hoje.
 *
 * O favor **não** precisa continuar ativo. Desativar impede novas emissões e
 * não invalida ticket já emitido (RF-FAV-12): quem tem o direito continua
 * podendo exercê-lo.
 *
 * Serve à solicitação e é reconferida na confirmação do ajudante, porque
 * entre um momento e outro a validade do ticket pode ter passado.
 */
class VerificarCondicoesPublicadas
{
    public function executar(
        Ticket $ticket,
        Usuario $solicitante,
        Carbon $inicioPrevisto,
        ?Carbon $momento = null,
    ): void {
        $momento ??= Carbon::now();
        $favor = $ticket->favor;
        $ajudante = $favor->proficienciaUsuario->usuario;

        if ($ticket->proprietario_id !== $solicitante->id) {
            throw new LogicException('Este ticket não está na sua carteira.');
        }

        if ($ajudante->id === $solicitante->id) {
            // O ticket pode voltar à carteira de quem o emitiu por uma troca.
            // Consumi-lo ali não move ajuda nenhuma de lugar.
            throw new LogicException('Não se solicita a si mesmo o favor que se oferece.');
        }

        // RF-CAD-14 — quem está encerrando continua obrigado a atender o que
        // já emitiu. Só conta desativada ou suspensa impede.
        if (! $ajudante->deveAtender()) {
            throw new LogicException(
                "A conta de {$ajudante->nome} não está em condição de atender."
            );
        }

        $this->validade($ticket, $inicioPrevisto, $momento);
        $this->antecedenciaMinima($favor, $inicioPrevisto, $momento);
        $this->janelaDeDisponibilidade($favor, $inicioPrevisto);
        $this->areaDeAtendimento($favor, $ajudante, $solicitante);
    }

    public static function fimPrevisto(Favor $favor, Carbon $inicioPrevisto): Carbon
    {
        return $inicioPrevisto->copy()->addMinutes($favor->tempo_estimado_minutos);
    }

    /**
     * O ticket precisa estar valendo, e o atendimento precisa caber dentro da
     * validade. Agendar para depois do vencimento seria prometer o exercício
     * de um direito que já terá acabado.
     */
    private function validade(Ticket $ticket, Carbon $inicioPrevisto, Carbon $momento): void
    {
        if ($ticket->estado !== Ticket::DISPONIVEL) {
            throw new LogicException(
                "O ticket {$ticket->codigo} está em \"{$ticket->estado}\" e não pode ser solicitado agora."
            );
        }

        if ($ticket->expira_em->lessThanOrEqualTo($momento)) {
            throw new LogicException("A validade do ticket {$ticket->codigo} já passou.");
        }

        if ($inicioPrevisto->greaterThan($ticket->expira_em)) {
            throw new LogicException(
                "O ticket {$ticket->codigo} vale até "
                .$ticket->expira_em->format('d/m/Y').': escolha uma data anterior.'
            );
        }
    }

    private function antecedenciaMinima(Favor $favor, Carbon $inicioPrevisto, Carbon $momento): void
    {
        $minimo = $momento->copy()->addDays($favor->antecedencia_minima_dias);

        if ($inicioPrevisto->lessThan($minimo)) {
            throw new LogicException(
                "Este favor pede {$favor->antecedencia_minima_dias} dia(s) de antecedência: "
                .'a data escolhida é cedo demais.'
            );
        }
    }

    /**
     * O atendimento inteiro cabe dentro de uma única janela publicada.
     *
     * Aceitar um início dentro da janela e um fim depois dela empurraria o
     * ajudante para além do que ele se dispôs a atender — e a estimativa de
     * tempo é condição publicada como qualquer outra.
     */
    private function janelaDeDisponibilidade(Favor $favor, Carbon $inicioPrevisto): void
    {
        $janelas = $favor->janelas()->get();

        if ($janelas->isEmpty()) {
            return;
        }

        $fimPrevisto = self::fimPrevisto($favor, $inicioPrevisto);
        $diaSemana = (int) $inicioPrevisto->dayOfWeek;
        $inicio = $inicioPrevisto->format('H:i:s');
        $fim = $fimPrevisto->format('H:i:s');

        $cabe = $janelas
            ->where('dia_semana', $diaSemana)
            ->first(fn (JanelaDisponibilidade $janela) => $janela->hora_inicio <= $inicio
                && $janela->hora_fim >= $fim
                && $inicioPrevisto->isSameDay($fimPrevisto));

        if ($cabe === null) {
            throw new LogicException(
                'O horário escolhido está fora das janelas de atendimento publicadas para este favor.'
            );
        }
    }

    /**
     * RF-SOL-02 — favor presencial respeita a área de atendimento.
     *
     * Sem as duas coordenadas não há verificação possível, e seguir em frente
     * seria fingir que houve. A recusa é explícita para que a pessoa saiba o
     * que completar.
     */
    private function areaDeAtendimento(Favor $favor, Usuario $ajudante, Usuario $solicitante): void
    {
        if ($favor->modalidade !== Favor::MODALIDADE_PRESENCIAL) {
            return;
        }

        // `geography` faz o ST_Distance devolver metros sobre o elipsoide,
        // sem projeção intermediária.
        $medida = DB::selectOne(
            'SELECT ST_Distance(de_quem_ajuda.coordenada, de_quem_pede.coordenada) AS metros
             FROM enderecos AS de_quem_ajuda, enderecos AS de_quem_pede
             WHERE de_quem_ajuda.usuario_id = ?
               AND de_quem_pede.usuario_id = ?
               AND de_quem_ajuda.coordenada IS NOT NULL
               AND de_quem_pede.coordenada IS NOT NULL',
            [$ajudante->id, $solicitante->id]
        );

        if ($medida === null) {
            throw new LogicException(
                'Favor presencial exige endereço com localização nas duas contas '
                .'para conferir a área de atendimento.'
            );
        }

        $limiteMetros = $favor->area_atendimento_km * 1000;

        if ((float) $medida->metros > $limiteMetros) {
            throw new LogicException(
                "Este favor atende até {$favor->area_atendimento_km} km, "
                .'e o endereço informado está fora dessa área.'
            );
        }
    }
}
