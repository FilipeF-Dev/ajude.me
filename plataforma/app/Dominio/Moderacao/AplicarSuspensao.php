<?php

namespace App\Dominio\Moderacao;

use App\Models\Favor;
use App\Models\Operador;
use App\Models\RegistroAuditoria;
use App\Models\Suspensao;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Suspensão de usuário ou de favor pela Operação (RF-ADM-06).
 *
 * Suspender uma conta impede ofertar e negociar — `Usuario::podeOfertar()` e
 * `podeNegociar()` já exigem conta Ativa. O que a suspensão **não** faz é
 * apagar obrigação: `deveAtender()` não cobre conta suspensa, e essa é uma
 * decisão consciente da Operação ao suspender alguém com atendimento marcado.
 *
 * Suspender um favor impede novas emissões e **não** invalida ticket já
 * emitido, pela mesma razão que desativar não invalida (02 §20.3): quem
 * adquiriu o direito contou com o que estava publicado, e não participou da
 * conduta que motivou a suspensão.
 */
class AplicarSuspensao
{
    public function usuario(
        Usuario $alvo,
        Operador $operador,
        string $motivo,
        ?Carbon $vigenteAte = null,
        ?Carbon $momento = null,
    ): Suspensao {
        $motivo = $this->exigirMotivo($motivo);
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($alvo, $operador, $motivo, $vigenteAte, $momento) {
            $suspensao = Suspensao::create([
                'alvo_usuario_id' => $alvo->id,
                'motivo' => $motivo,
                'aplicada_por_id' => $operador->id,
                'vigente_de' => $momento,
                'vigente_ate' => $vigenteAte,
            ]);

            $alvo->forceFill(['estado_conta' => Usuario::ESTADO_SUSPENSA])->save();

            RegistroAuditoria::registrar($operador, 'usuario_suspenso', $alvo, $motivo, $momento);

            return $suspensao;
        });
    }

    public function favor(
        Favor $alvo,
        Operador $operador,
        string $motivo,
        ?Carbon $vigenteAte = null,
        ?Carbon $momento = null,
    ): Suspensao {
        $motivo = $this->exigirMotivo($motivo);
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($alvo, $operador, $motivo, $vigenteAte, $momento) {
            $suspensao = Suspensao::create([
                'alvo_favor_id' => $alvo->id,
                'motivo' => $motivo,
                'aplicada_por_id' => $operador->id,
                'vigente_de' => $momento,
                'vigente_ate' => $vigenteAte,
            ]);

            // Desativar usa o caminho já existente: impede novas emissões e
            // preserva os tickets em circulação (RF-FAV-11, RF-FAV-12).
            $alvo->desativar();

            RegistroAuditoria::registrar($operador, 'favor_suspenso', $alvo, $motivo, $momento);

            return $suspensao;
        });
    }

    /** Encerra a vigência sem apagar o registro: o passado continua consultável. */
    public function encerrar(
        Suspensao $suspensao,
        Operador $operador,
        string $motivo,
        ?Carbon $momento = null,
    ): Suspensao {
        $motivo = $this->exigirMotivo($motivo);
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($suspensao, $operador, $motivo, $momento) {
            if (! $suspensao->estaVigente($momento)) {
                throw new LogicException('Esta suspensão já não está vigente.');
            }

            $suspensao->forceFill(['vigente_ate' => $momento])->save();

            if ($suspensao->alvo_usuario_id !== null) {
                $suspensao->alvoUsuario->forceFill([
                    'estado_conta' => Usuario::ESTADO_ATIVA,
                ])->save();
            }

            RegistroAuditoria::registrar($operador, 'suspensao_encerrada', $suspensao, $motivo, $momento);

            return $suspensao;
        });
    }

    private function exigirMotivo(string $motivo): string
    {
        $motivo = trim($motivo);

        if ($motivo === '') {
            // RF-ADM-06 e RF-ADM-10 — ação da Operação sem motivo registrado
            // não é auditável, e a auditoria é a contrapartida do poder.
            throw new LogicException('A suspensão exige registro de motivo.');
        }

        return $motivo;
    }
}
