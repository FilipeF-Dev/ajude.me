<?php

namespace App\Dominio\Seguranca;

use App\Models\AceiteTermo;
use App\Models\Operador;
use App\Models\RegistroAuditoria;
use App\Models\Solicitacao;
use App\Models\TermoCiencia;
use App\Models\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Publicação e aceite do termo de ciência de riscos (02 §8.2, RF-ADM-07).
 *
 * > **Termo de ciência é documento, e documento não socorre ninguém.**
 *
 * Ele cobre o risco jurídico da plataforma. O risco de o evento acontecer é
 * coberto pela curadoria de categorias e pelo registro de comparecimento — e
 * a existência do termo não substitui nenhum dos dois (02 §8.3).
 */
class GerirTermoDeCiencia
{
    /**
     * Publica uma redação nova e encerra a anterior.
     *
     * O texto de uma versão publicada nunca muda: quem aceitou a versão 2
     * aceitou aquele texto, e é isso que RF-SEG-04 exige demonstrar depois.
     */
    public function publicar(string $texto, Operador $operador, ?Carbon $momento = null): TermoCiencia
    {
        $texto = trim($texto);

        if ($texto === '') {
            throw new LogicException('O termo de ciência não pode ser vazio.');
        }

        $momento ??= Carbon::now();

        return DB::transaction(function () use ($texto, $operador, $momento) {
            $vigente = TermoCiencia::vigente();

            $vigente?->forceFill(['encerrado_em' => $momento])->save();

            $termo = TermoCiencia::create([
                'versao' => ($vigente?->versao ?? 0) + 1,
                'texto' => $texto,
                'publicado_em' => $momento,
                'publicado_por_id' => $operador->id,
            ]);

            RegistroAuditoria::registrar(
                $operador,
                'termo_de_ciencia_publicado',
                $termo,
                "Versão {$termo->versao}.",
                $momento,
            );

            return $termo;
        });
    }

    /**
     * RF-SEG-03 e RF-SEG-04 — registra o aceite de uma parte, com a versão.
     *
     * O aceite é por atendimento, não por conta. Um termo assinado uma única
     * vez no cadastro não conscientiza ninguém sobre o encontro de amanhã —
     * é a mesma razão pela qual 02 §8.2 proíbe exibi-lo no cadastro.
     */
    public function aceitar(
        Solicitacao $solicitacao,
        Usuario $parte,
        ?Carbon $momento = null,
    ): AceiteTermo {
        // Lança se não for parte do atendimento.
        $solicitacao->papelDe($parte);

        $termo = TermoCiencia::exigirVigente();

        try {
            return AceiteTermo::create([
                'usuario_id' => $parte->id,
                'termo_ciencia_id' => $termo->id,
                'solicitacao_id' => $solicitacao->id,
                'aceito_em' => $momento ?? Carbon::now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new LogicException('Você já aceitou o termo para este atendimento.');
        }
    }

    public static function aceitouPara(Solicitacao $solicitacao, Usuario $parte): bool
    {
        return AceiteTermo::query()
            ->where('solicitacao_id', $solicitacao->id)
            ->where('usuario_id', $parte->id)
            ->exists();
    }
}
