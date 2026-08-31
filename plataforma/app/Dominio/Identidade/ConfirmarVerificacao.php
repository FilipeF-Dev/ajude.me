<?php

namespace App\Dominio\Identidade;

use App\Models\ParametroNegocio;
use App\Models\Usuario;
use App\Models\VerificacaoContato;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

/**
 * Confere o código e marca o contato como verificado.
 *
 * O selo "Contato verificado" (RF-CAD-06) é derivado disto, e só aparece com
 * e-mail **e** telefone confirmados — nunca é armazenado.
 *
 * Confirmar não muda o que a conta pode fazer: o MVP não bloqueia quem não
 * verificou. O selo é informação para quem vai decidir uma troca, e não uma
 * catraca — o `03 §4.1` assume essa dívida conscientemente.
 */
class ConfirmarVerificacao
{
    public function executar(
        Usuario $usuario,
        string $tipo,
        string $codigo,
        ?Carbon $momento = null,
    ): VerificacaoContato {
        $momento ??= Carbon::now();

        /**
         * A transação devolve o desfecho; a exceção sobe depois dela.
         *
         * Lançar de dentro faria o incremento de tentativa ser desfeito junto
         * com a transação — e um limite que se apaga a cada erro não limita
         * nada. O caso de código errado precisa **confirmar** a escrita antes
         * de recusar.
         */
        $desfecho = DB::transaction(function () use ($usuario, $tipo, $codigo, $momento) {
            $verificacao = VerificacaoContato::query()
                ->where('usuario_id', $usuario->id)
                ->where('tipo', $tipo)
                ->lockForUpdate()
                ->first();

            if ($verificacao === null) {
                throw new LogicException('Peça um código antes de confirmar.');
            }

            if ($verificacao->estaVerificado()) {
                return ['verificacao' => $verificacao];
            }

            $maximo = ParametroNegocio::inteiroEm(
                ParametroNegocio::VERIFICACAO_TENTATIVAS_MAXIMAS,
                $momento,
            ) ?? 5;

            // NF-SEG-06 — seis dígitos são adivinháveis sem limite de erros.
            if ($verificacao->tentativas >= $maximo) {
                throw new LogicException(
                    'Muitas tentativas erradas. Peça um código novo.'
                );
            }

            if ($verificacao->codigo_expira_em?->lessThanOrEqualTo($momento) ?? true) {
                throw new LogicException('Este código venceu. Peça outro.');
            }

            if (! Hash::check($codigo, (string) $verificacao->codigo)) {
                $verificacao->forceFill([
                    'tentativas' => $verificacao->tentativas + 1,
                ])->save();

                return ['erro' => 'Código incorreto.'];
            }

            $verificacao->forceFill([
                'verificado_em' => $momento,
                // O código deixa de existir assim que cumpre sua função:
                // credencial usada não precisa continuar guardada.
                'codigo' => null,
                'codigo_expira_em' => null,
                'tentativas' => 0,
            ])->save();

            return ['verificacao' => $verificacao];
        });

        if (isset($desfecho['erro'])) {
            throw new LogicException($desfecho['erro']);
        }

        return $desfecho['verificacao'];
    }
}
