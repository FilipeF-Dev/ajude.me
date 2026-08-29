<?php

namespace App\Dominio\Reputacao;

use App\Models\Avaliacao;
use App\Models\ParametroNegocio;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use LogicException;

/**
 * Avaliação pendente e a restrição que dela decorre (02 §11.4, RF-AVA-11).
 *
 * > A obrigatoriedade será garantida por **restrição de novas operações**.
 *
 * A escolha é deliberada: quem não avalia deixa de enviar propostas novas,
 * e não recebe punição nem perde nada do que já tem. O objetivo é destravar
 * o ciclo do outro — que fica com o favor em aberto e a reputação parada —
 * sem transformar esquecimento em castigo.
 *
 * A restrição cai sozinha assim que a avaliação é registrada.
 */
class AvaliacaoPendente
{
    /**
     * Solicitações realizadas em que a pessoa ainda não avaliou.
     *
     * @return Collection<int, Solicitacao>
     */
    public function de(Usuario $usuario): Collection
    {
        return Solicitacao::query()
            ->where('estado', Solicitacao::REALIZADA)
            ->where(fn ($consulta) => $consulta
                ->where('ajudante_id', $usuario->id)
                ->orWhere('solicitante_id', $usuario->id))
            ->whereNotExists(fn ($consulta) => $consulta
                ->selectRaw('1')
                ->from('avaliacoes')
                ->whereColumn('avaliacoes.solicitacao_id', 'solicitacoes.id')
                ->where('avaliacoes.avaliador_id', $usuario->id))
            ->get();
    }

    /**
     * RF-AVA-11 — pendente **além do prazo**.
     *
     * O prazo corre a partir do instante em que a avaliação foi liberada, e o
     * valor consultado é o vigente naquele momento (05 §9): alterar o
     * parâmetro não pode encurtar retroativamente o prazo de quem já estava
     * dentro dele.
     */
    public function emAtraso(Usuario $usuario, ?Carbon $momento = null): Collection
    {
        $momento ??= Carbon::now();

        return $this->de($usuario)->filter(function (Solicitacao $solicitacao) use ($momento) {
            $liberadaEm = $solicitacao->realizadaEm();

            if ($liberadaEm === null) {
                return false;
            }

            $dias = ParametroNegocio::inteiroEm(
                ParametroNegocio::AVALIACAO_PENDENTE_DIAS,
                $liberadaEm,
            );

            if ($dias === null) {
                throw new LogicException(
                    'O parâmetro de prazo de avaliação não está definido. '
                    .'Rode o ParametrosNegocioSeeder.'
                );
            }

            return $liberadaEm->copy()->addDays($dias)->lessThanOrEqualTo($momento);
        })->values();
    }

    public function restringeNovasPropostas(Usuario $usuario, ?Carbon $momento = null): bool
    {
        return $this->emAtraso($usuario, $momento)->isNotEmpty();
    }

    /**
     * RF-AVA-12 — o aviso precede a restrição.
     *
     * Quem está pendente mas ainda dentro do prazo é quem deve ser avisado;
     * aplicar a restrição sem ter avisado transformaria a regra em armadilha.
     *
     * @return Collection<int, Solicitacao>
     */
    public function aAvisar(Usuario $usuario, ?Carbon $momento = null): Collection
    {
        $momento ??= Carbon::now();
        $emAtraso = $this->emAtraso($usuario, $momento)->pluck('id')->all();

        return $this->de($usuario)
            ->reject(fn (Solicitacao $solicitacao) => in_array($solicitacao->id, $emAtraso, true))
            ->values();
    }

    /** Usado pela consulta de reputação: quantas avaliações a pessoa recebeu. */
    public function recebidas(Usuario $usuario, string $papel): int
    {
        return Avaliacao::query()
            ->where('avaliado_id', $usuario->id)
            ->where('papel_avaliado', $papel)
            ->count();
    }
}
