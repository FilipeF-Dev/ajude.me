<?php

namespace App\Dominio\Moderacao;

use App\Models\Chamado;
use App\Models\ManifestacaoChamado;
use App\Models\ParametroNegocio;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Abertura do chamado por contestação da realização (RF-SOL-12, RF-SOL-14).
 *
 * Chamado por solicitação, um só: a contestação leva a solicitação à análise
 * uma única vez, e dois chamados sobre o mesmo episódio permitiriam duas
 * decisões para o mesmo fato.
 *
 * O prazo de manifestação vem do parâmetro vigente quando o chamado abre.
 * Esgotado, a análise segue com o que houver (RF-SOL-15): a parte que se cala
 * não trava a apuração da outra.
 */
class AbrirChamado
{
    public function executar(
        Solicitacao $solicitacao,
        Usuario $quemContestou,
        ?Carbon $momento = null,
    ): Chamado {
        $momento ??= Carbon::now();

        $dias = ParametroNegocio::inteiroEm(
            ParametroNegocio::MANIFESTACAO_CHAMADO_DIAS,
            $momento,
        );

        if ($dias === null) {
            throw new LogicException(
                'O parâmetro de prazo de manifestação não está definido. '
                .'Rode o ParametrosNegocioSeeder.'
            );
        }

        $chamado = new Chamado();
        $chamado->forceFill([
            'solicitacao_id' => $solicitacao->id,
            'estado' => Chamado::ABERTO,
            'aberto_por_id' => $quemContestou->id,
            'aberto_em' => $momento,
            'manifestacao_ate' => $momento->copy()->addDays($dias),
        ]);

        try {
            $chamado->save();
        } catch (UniqueConstraintViolationException) {
            throw new LogicException('Esta solicitação já tem chamado aberto.');
        }

        return $chamado;
    }

    /**
     * RF-SOL-14 — cada parte apresenta a sua versão do ocorrido.
     *
     * Uma por parte. Reescrever a própria versão depois de ler a da outra não
     * seria apuração, seria ajuste.
     */
    public function manifestar(
        Chamado $chamado,
        Usuario $parte,
        string $texto,
        ?Carbon $momento = null,
    ): ManifestacaoChamado {
        $texto = trim($texto);

        if ($texto === '') {
            throw new LogicException('A manifestação não pode ser vazia.');
        }

        if (! $chamado->estaAberto()) {
            throw new LogicException('Este chamado já foi decidido.');
        }

        $momento ??= Carbon::now();

        if ($chamado->prazoDeManifestacaoEncerrou($momento)) {
            throw new LogicException('O prazo de manifestação deste chamado já se encerrou.');
        }

        $papel = $chamado->solicitacao->papelDe($parte);

        try {
            return ManifestacaoChamado::create([
                'chamado_id' => $chamado->id,
                'parte' => $papel,
                'texto' => $texto,
                'enviada_em' => $momento,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new LogicException('Você já apresentou sua versão neste chamado.');
        }
    }
}
