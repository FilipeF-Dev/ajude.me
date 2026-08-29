<?php

namespace App\Dominio\Notificacao;

use App\Models\Notificacao;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Publicação de notificações (04 §16, 05 §4.9).
 *
 * Interface própria desde o início, conforme a decisão A4 (05 §15): os
 * eventos são síncronos hoje, mas quem publica não deve mudar quando o
 * processamento virar assíncrono. Por isso todo contexto chama este serviço,
 * e nenhum escreve na tabela direto.
 *
 * **A gravação é o registro; a entrega é outro problema.** Push, e-mail e SMS
 * dependem de provedor externo ainda não escolhido (05 §15.1). O que existe
 * aqui é a fila do que precisa ser dito e a quem — e ela já serve à
 * interface, que lê as notificações do próprio usuário.
 *
 * RF-NOT-17 — nada de marketing, engajamento ou recomendação. Toda
 * notificação decorre de algo que aconteceu e que alguém precisa saber para
 * agir.
 */
class Notificar
{
    public function enviar(
        Usuario $destinatario,
        string $tipo,
        ?Model $referencia = null,
        array $dados = [],
        ?Carbon $momento = null,
    ): Notificacao {
        return Notificacao::create([
            'destinatario_id' => $destinatario->id,
            'tipo' => $tipo,
            'referencia_tipo' => $referencia === null ? null : $referencia::class,
            'referencia_id' => $referencia?->getKey(),
            'dados' => $dados === [] ? null : $dados,
            'enviada_em' => $momento ?? Carbon::now(),
        ]);
    }

    /**
     * Envia a mesma notificação às duas partes de um episódio.
     *
     * @param  array<int, Usuario>  $destinatarios
     * @return array<int, Notificacao>
     */
    public function enviarA(
        array $destinatarios,
        string $tipo,
        ?Model $referencia = null,
        array $dados = [],
        ?Carbon $momento = null,
    ): array {
        return array_map(
            fn (Usuario $destinatario) => $this->enviar($destinatario, $tipo, $referencia, $dados, $momento),
            $destinatarios,
        );
    }

    /**
     * Evita repetir a mesma notificação sobre a mesma referência.
     *
     * As rotinas temporais rodam de tempos em tempos e reavaliam o mesmo
     * estado; sem isto, um lembrete diário viraria um lembrete por execução.
     */
    public function enviarUmaVez(
        Usuario $destinatario,
        string $tipo,
        Model $referencia,
        array $dados = [],
        ?Carbon $momento = null,
    ): ?Notificacao {
        $jaExiste = Notificacao::query()
            ->where('destinatario_id', $destinatario->id)
            ->where('tipo', $tipo)
            ->where('referencia_tipo', $referencia::class)
            ->where('referencia_id', $referencia->getKey())
            ->exists();

        return $jaExiste ? null : $this->enviar($destinatario, $tipo, $referencia, $dados, $momento);
    }
}
