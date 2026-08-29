<?php

namespace App\Dominio\Descoberta;

use App\Dominio\Notificacao\Notificar;
use App\Models\AvisoNecessidade;
use App\Models\Favor;
use App\Models\Notificacao;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * RF-BUS-23 e RF-NOT-18 — avisa quem estava esperando.
 *
 * A fila de aviso é o outro lado da necessidade não atendida: alguém
 * procurou, não achou, e pediu para saber quando aparecesse. Quando um favor
 * daquela proficiência passa a ter ticket disponível na região, o pedido é
 * cumprido e encerrado.
 *
 * O aviso vai **só para quem pediu** (RF-BUS-27): ninguém é notificado de que
 * outra pessoa procurou alguma coisa. A fila é um pedido de quem espera, não
 * uma lista de demanda para quem oferta.
 */
class AvisarProficienciaDisponivel
{
    public function __construct(
        private readonly Notificar $notificacoes = new Notificar(),
    ) {
    }

    /**
     * Percorre a fila e avisa quem cabe avisar.
     *
     * @return int quantos avisos foram cumpridos
     */
    public function executar(?Carbon $momento = null): int
    {
        $momento ??= Carbon::now();
        $cumpridos = 0;

        $aguardando = AvisoNecessidade::query()
            ->whereNull('atendido_em')
            ->with(['usuario', 'proficiencia'])
            ->get();

        foreach ($aguardando as $aviso) {
            $favor = $this->favorDisponivelPara($aviso);

            if ($favor === null) {
                continue;
            }

            DB::transaction(function () use ($aviso, $favor, $momento, &$cumpridos) {
                $this->notificacoes->enviar(
                    $aviso->usuario,
                    Notificacao::PROFICIENCIA_AGUARDADA_DISPONIVEL,
                    $favor,
                    ['proficiencia' => $aviso->proficiencia->nome],
                    $momento,
                );

                $aviso->forceFill(['atendido_em' => $momento])->save();

                $cumpridos++;
            });
        }

        return $cumpridos;
    }

    /**
     * Um favor ativo daquela proficiência, com ticket disponível, na região
     * de quem espera.
     *
     * Sem região registrada, a proximidade não é exigida: melhor avisar de
     * algo talvez distante do que deixar de avisar quem não completou o
     * endereço.
     */
    private function favorDisponivelPara(AvisoNecessidade $aviso): ?Favor
    {
        return Favor::query()
            ->where('favores.estado', Favor::ESTADO_ATIVO)
            ->join('proficiencias_usuario', 'proficiencias_usuario.id', '=', 'favores.proficiencia_usuario_id')
            ->where('proficiencias_usuario.proficiencia_id', $aviso->proficiencia_id)
            // Quem espera não é avisado da própria oferta.
            ->where('proficiencias_usuario.usuario_id', '<>', $aviso->usuario_id)
            ->whereExists(fn ($sub) => $sub
                ->selectRaw('1')
                ->from('tickets')
                ->whereColumn('tickets.favor_id', 'favores.id')
                ->where('tickets.estado', Ticket::DISPONIVEL))
            ->when($aviso->municipio !== null, fn ($consulta) => $consulta
                ->join('enderecos', 'enderecos.usuario_id', '=', 'proficiencias_usuario.usuario_id')
                ->where('enderecos.municipio', $aviso->municipio)
                ->where('enderecos.uf', $aviso->uf))
            ->select('favores.*')
            ->first();
    }
}
