<?php

namespace App\Dominio\Identidade;

use App\Models\Favor;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Encerramento de conta (RF-CAD-11 a RF-CAD-16, 02 §2.2).
 *
 * A regra que governa tudo aqui: **ticket emitido não desaparece porque quem
 * o emitiu quis sair** (02 §20, princípio 3). Sem dinheiro no MVP não existe
 * compensação possível, então a única proteção ao portador é o prazo — e ela
 * é integral.
 *
 * Daí os dois desfechos: quem não deve nada sai na hora; quem tem ticket seu
 * circulando entra em `ENCERRANDO`, para de começar coisa nova e continua
 * obrigado a atender o que prometeu, até o último ticket ser consumido ou
 * vencer.
 */
class EncerrarConta
{
    /**
     * RF-CAD-11, RF-CAD-12 e RF-CAD-16.
     */
    public function solicitar(Usuario $usuario, ?Carbon $momento = null): Usuario
    {
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($usuario, $momento) {
            $usuario = Usuario::query()->whereKey($usuario->getKey())->lockForUpdate()->firstOrFail();

            if (in_array($usuario->estado_conta, [
                Usuario::ESTADO_DESATIVADA,
                Usuario::ESTADO_ENCERRANDO,
            ], true)) {
                throw new LogicException('Esta conta já está sendo encerrada.');
            }

            if ($usuario->estado_conta === Usuario::ESTADO_SUSPENSA) {
                // Suspensão é decisão da Operação. Sair por vontade própria
                // enquanto se responde por algo apagaria a análise em curso.
                throw new LogicException(
                    'Contas suspensas não são encerradas a pedido. Fale com a Operação.'
                );
            }

            // RF-CAD-13 — nada de oferta nova. Desativar os favores é o que
            // os tira do feed sem invalidar ticket já emitido (RF-FAV-12).
            $this->desativarFavores($usuario, $momento);

            $pendentes = $this->ticketsEmitidosEmCirculacao($usuario);

            $usuario->forceFill([
                'estado_conta' => $pendentes > 0
                    ? Usuario::ESTADO_ENCERRANDO
                    : Usuario::ESTADO_DESATIVADA,
                'encerramento_solicitado_em' => $momento,
                'encerrada_em' => $pendentes > 0 ? null : $momento,
            ])->save();

            return $usuario;
        });
    }

    /**
     * RF-CAD-15 — encerra de vez quando não há mais nada devido.
     *
     * Chamada pela rotina temporal: o último ticket costuma sair de cena por
     * vencimento, e vencimento não avisa ninguém.
     */
    public function concluir(Usuario $usuario, ?Carbon $momento = null): bool
    {
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($usuario, $momento) {
            $usuario = Usuario::query()->whereKey($usuario->getKey())->lockForUpdate()->firstOrFail();

            if ($usuario->estado_conta !== Usuario::ESTADO_ENCERRANDO) {
                return false;
            }

            if ($this->ticketsEmitidosEmCirculacao($usuario) > 0) {
                return false;
            }

            $usuario->forceFill([
                'estado_conta' => Usuario::ESTADO_DESATIVADA,
                'encerrada_em' => $momento,
            ])->save();

            return true;
        });
    }

    /**
     * O que prende a conta: ticket que **ela emitiu** e ainda vale.
     *
     * Não é o que a pessoa tem na carteira. Direito que ela possui é dela e
     * some com a conta, por escolha dela; direito que ela deve a outro é
     * obrigação, e obrigação não se cancela saindo.
     */
    public function ticketsEmitidosEmCirculacao(Usuario $usuario): int
    {
        return Ticket::query()
            ->whereIn('estado', Ticket::EM_CIRCULACAO)
            ->whereIn('favor_id', $this->favoresDe($usuario))
            ->count();
    }

    private function desativarFavores(Usuario $usuario, Carbon $momento): void
    {
        Favor::query()
            ->whereIn('id', $this->favoresDe($usuario))
            ->where('estado', '!=', Favor::ESTADO_DESATIVADO)
            ->update([
                'estado' => Favor::ESTADO_DESATIVADO,
                'desativado_em' => $momento,
                'updated_at' => $momento,
            ]);
    }

    /** @return \Illuminate\Database\Query\Builder */
    private function favoresDe(Usuario $usuario)
    {
        return DB::table('favores')
            ->join('proficiencias_usuario', 'proficiencias_usuario.id', '=', 'favores.proficiencia_usuario_id')
            ->where('proficiencias_usuario.usuario_id', $usuario->id)
            ->select('favores.id');
    }
}
