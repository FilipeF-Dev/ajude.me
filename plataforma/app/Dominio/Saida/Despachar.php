<?php

namespace App\Dominio\Saida;

use App\Models\Envio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Registra a mensagem e pede ao canal que a entregue.
 *
 * A ordem importa: **grava primeiro, entrega depois.** Se o provedor cair no
 * meio, o que se quis dizer e a quem continua existindo, e o reenvio não
 * precisa reconstruir nada.
 */
class Despachar
{
    public function __construct(
        private readonly CanalDeSaida $canal,
    ) {
    }

    public function executar(Mensagem $mensagem, ?Carbon $momento = null): Envio
    {
        $momento ??= Carbon::now();
        $chave = $mensagem->chave();

        // NF-DIS-04 — reenviar o mesmo comando não duplica a mensagem.
        // Vale para o retentar de uma fila e para o duplo clique no botão.
        $existente = Envio::where('chave_idempotencia', $chave)->first();

        if ($existente !== null) {
            return $existente;
        }

        $envio = DB::transaction(function () use ($mensagem, $chave, $momento) {
            $novo = new Envio();
            $novo->forceFill([
                'chave_idempotencia' => $chave,
                'canal' => $mensagem->canal,
                'destino' => $mensagem->destino,
                'tipo' => $mensagem->tipo,
                'assunto' => $mensagem->assunto,
                'corpo' => $mensagem->corpo,
                'referencia_tipo' => $mensagem->referencia === null ? null : $mensagem->referencia::class,
                'referencia_id' => $mensagem->referencia?->getKey(),
                'estado' => Envio::REGISTRADO,
                'despachado_em' => $momento,
            ]);
            $novo->save();

            return $novo;
        });

        try {
            $this->canal->entregar($envio, $mensagem);

            if ($this->canal->entregaDeVerdade()) {
                $envio->marcarEntregue($momento);
            }
        } catch (Throwable $falha) {
            // Falha de entrega não derruba quem chamou: ninguém deve perder
            // um cadastro porque o SMS não saiu. Fica registrada para a
            // rotina de reenvio e para o painel (NF-DIS-02, NF-DIS-03).
            $envio->marcarFalha($falha->getMessage());
        }

        return $envio->fresh();
    }
}
