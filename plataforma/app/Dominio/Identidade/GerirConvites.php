<?php

namespace App\Dominio\Identidade;

use App\Models\Convite;
use App\Models\Operador;
use App\Models\ParametroNegocio;
use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Convites de acesso da base restrita (RF-ADM-08, 06 §5.2).
 *
 * O cadastro por convite é **modo**, não regra permanente: vale enquanto a
 * base for restrita e se desliga por parâmetro quando a Fase 4 abrir o
 * cadastro (06 §8). Por isso a chave vive em `ParametroNegocio`, com
 * vigência, e não numa constante — abrir a base não pode exigir reimplantar
 * a aplicação.
 */
class GerirConvites
{
    /**
     * Emite convites em lote.
     *
     * Em lote porque é assim que o piloto funciona: a Operação prepara um
     * punhado antes de ir a campo, e não um por vez enquanto conversa com
     * alguém.
     *
     * @return Collection<int, Convite>
     */
    public function emitir(
        Operador $operador,
        int $quantidade = 1,
        ?string $destinatario = null,
        ?string $observacao = null,
        ?Carbon $expiraEm = null,
    ): Collection {
        if ($quantidade < 1 || $quantidade > 100) {
            throw new LogicException('A quantidade de convites precisa estar entre 1 e 100.');
        }

        return DB::transaction(function () use ($operador, $quantidade, $destinatario, $observacao, $expiraEm) {
            $convites = collect();

            for ($i = 0; $i < $quantidade; $i++) {
                $convites->push(Convite::create([
                    'codigo' => Convite::gerarCodigo(),
                    'criado_por_id' => $operador->id,
                    'destinatario' => $destinatario,
                    'observacao' => $observacao,
                    'expira_em' => $expiraEm,
                ]));
            }

            // RF-ADM-10 — quem abriu a porta, e quando.
            RegistroAuditoria::registrar(
                $operador,
                'convites_emitidos',
                $convites->first(),
                "{$quantidade} convite(s)".($destinatario ? " para {$destinatario}" : ''),
            );

            return $convites;
        });
    }

    /**
     * Revoga um convite ainda não usado.
     *
     * Convite já usado não se revoga: a conta existe, e fingir que desfazer o
     * convite a desfaz seria pior do que não permitir. Para agir sobre a
     * conta, o caminho é a suspensão (RF-ADM-06).
     */
    public function revogar(Convite $convite, Operador $operador, string $motivo): Convite
    {
        if ($convite->foiUsado()) {
            throw new LogicException(
                'Este convite já foi usado. Para agir sobre a conta criada, use a suspensão.'
            );
        }

        if ($convite->foiRevogado()) {
            throw new LogicException('Este convite já está revogado.');
        }

        return DB::transaction(function () use ($convite, $operador, $motivo) {
            $convite->forceFill([
                'revogado_em' => now(),
                'revogado_por_id' => $operador->id,
            ])->save();

            RegistroAuditoria::registrar($operador, 'convite_revogado', $convite, $motivo);

            return $convite;
        });
    }

    /**
     * Confere o código digitado e devolve o convite, ou explica a recusa.
     *
     * A mensagem distingue "não existe" de "já usado" e de "vencido" porque
     * são situações diferentes para quem está tentando entrar — e nenhuma
     * delas revela nada sobre outra pessoa.
     */
    public function conferir(string $codigo, ?Carbon $momento = null): Convite
    {
        $convite = Convite::where('codigo', Convite::normalizar($codigo))->first();

        if ($convite === null) {
            throw new LogicException('Convite não encontrado. Confira o código.');
        }

        if ($convite->foiUsado()) {
            throw new LogicException('Este convite já foi usado.');
        }

        if ($convite->foiRevogado()) {
            throw new LogicException('Este convite foi cancelado.');
        }

        if ($convite->estaVencido($momento)) {
            throw new LogicException('Este convite venceu. Peça outro a quem o entregou.');
        }

        return $convite;
    }

    /**
     * Marca o convite como usado, sob bloqueio.
     *
     * Chamado dentro da transação do cadastro. O bloqueio impede que duas
     * pessoas com o mesmo código criem duas contas — e a chave única em
     * `usado_por_id` é a garantia final.
     */
    public function resgatar(Convite $convite, Usuario $usuario, ?Carbon $momento = null): void
    {
        $travado = Convite::query()
            ->whereKey($convite->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if (! $travado->podeSerUsado($momento)) {
            throw new LogicException('Este convite não está mais disponível.');
        }

        $travado->forceFill([
            'usado_por_id' => $usuario->id,
            'usado_em' => $momento ?? now(),
        ])->save();
    }

    /**
     * O cadastro exige convite agora?
     *
     * Desligável por parâmetro: abrir a base é decisão de fase (06 §8), não
     * de código.
     */
    public function cadastroExigeConvite(?Carbon $momento = null): bool
    {
        return ParametroNegocio::inteiroEm(
            ParametroNegocio::CADASTRO_POR_CONVITE,
            $momento,
        ) === 1;
    }
}
