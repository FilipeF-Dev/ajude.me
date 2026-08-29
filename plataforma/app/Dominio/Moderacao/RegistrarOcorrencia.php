<?php

namespace App\Dominio\Moderacao;

use App\Models\Ocorrencia;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Registro de ocorrência no histórico de um usuário (02 §10).
 *
 * Duas origens, uma tabela: o que o sistema conclui sozinho — um cancelamento
 * em cima da hora — e o que uma parte relata. A Operação analisa os dois
 * juntos, mas o campo `origem` impede que um relato seja lido como fato
 * apurado.
 */
class RegistrarOcorrencia
{
    /**
     * Consequência automática de um ato do próprio usuário (RF-CAN-06,
     * RF-CAN-15, RF-SOL-16, RF-SOL-17). Não tem autor porque ninguém acusou:
     * a regra é que concluiu.
     */
    public function peloSistema(
        string $tipo,
        Usuario $contra,
        ?Solicitacao $solicitacao = null,
        ?string $descricao = null,
        ?Carbon $momento = null,
    ): Ocorrencia {
        return Ocorrencia::create([
            'tipo' => $tipo,
            'origem' => Ocorrencia::ORIGEM_SISTEMA,
            'autor_id' => null,
            'contra_usuario_id' => $contra->id,
            'solicitacao_id' => $solicitacao?->id,
            'descricao' => $descricao,
            'registrada_em' => $momento ?? Carbon::now(),
        ]);
    }

    /**
     * RF-CAN-10 — ambas as partes registram ocorrência sobre a prestação.
     *
     * Só as partes: quem não participou do atendimento não tem o que relatar
     * sobre ele. Denúncia contra estranho é outro caminho (RF-BLO-07).
     */
    public function pelaParte(
        Solicitacao $solicitacao,
        Usuario $autor,
        string $descricao,
        ?Carbon $momento = null,
    ): Ocorrencia {
        $descricao = trim($descricao);

        if ($descricao === '') {
            throw new LogicException('A ocorrência precisa descrever o que aconteceu.');
        }

        // Lança se o autor não for parte.
        $papel = $solicitacao->papelDe($autor);

        $contra = $papel === Solicitacao::PARTE_AJUDANTE
            ? $solicitacao->solicitante
            : $solicitacao->ajudante;

        return Ocorrencia::create([
            'tipo' => Ocorrencia::RELATO_DA_PARTE,
            'origem' => Ocorrencia::ORIGEM_USUARIO,
            'autor_id' => $autor->id,
            'contra_usuario_id' => $contra->id,
            'solicitacao_id' => $solicitacao->id,
            'descricao' => $descricao,
            'registrada_em' => $momento ?? Carbon::now(),
        ]);
    }
}
