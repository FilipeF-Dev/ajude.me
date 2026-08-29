<?php

namespace App\Dominio\Consumo;

use App\Models\RegistroComparecimento as Registro;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Registro de início e fim do atendimento (02 §8.1, RF-SEG-05 a RF-SEG-07).
 *
 * Cada parte registra por si: é o encontro das duas marcações que constitui
 * a evidência. Uma pessoa marcando pelas duas não prova comparecimento
 * nenhum.
 *
 * O registro não muda o estado da solicitação. Ele é evidência independente
 * da confirmação (02 §8.4) — e essa independência é o que o torna útil
 * quando as duas versões do ocorrido divergem.
 */
class RegistrarComparecimento
{
    public function inicio(Solicitacao $solicitacao, Usuario $parte, ?Carbon $momento = null): Registro
    {
        return $this->registrar($solicitacao, $parte, Registro::INICIO, $momento);
    }

    public function fim(Solicitacao $solicitacao, Usuario $parte, ?Carbon $momento = null): Registro
    {
        return $this->registrar($solicitacao, $parte, Registro::FIM, $momento);
    }

    private function registrar(
        Solicitacao $solicitacao,
        Usuario $parte,
        string $tipo,
        ?Carbon $momento,
    ): Registro {
        $papel = $solicitacao->papelDe($parte);

        // Antes de agendar não há atendimento a delimitar; depois da janela
        // de contestação, o que houver de registrar já deveria estar posto.
        if (! in_array($solicitacao->estado, [
            Solicitacao::AGENDADA,
            Solicitacao::AGUARDANDO_CONFIRMACAO,
        ], true)) {
            throw new LogicException(
                "A solicitação está em \"{$solicitacao->estado}\": não há atendimento a registrar."
            );
        }

        if ($tipo === Registro::FIM
            && ! $solicitacao->comparecimentos()
                ->where('parte', $papel)
                ->where('tipo', Registro::INICIO)
                ->exists()
        ) {
            throw new LogicException('Registre o início do atendimento antes do fim.');
        }

        try {
            return Registro::create([
                'solicitacao_id' => $solicitacao->id,
                'parte' => $papel,
                'tipo' => $tipo,
                'momento' => $momento ?? Carbon::now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Só inserção: corrigir um horário registrado não é opção, e é
            // exatamente por isso que o registro serve de evidência.
            throw new LogicException('Este registro já foi feito e não se repete.');
        }
    }
}
