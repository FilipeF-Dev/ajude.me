<?php

namespace App\Dominio\Saida;

use App\Models\Envio;

/**
 * O contrato que um provedor precisa cumprir.
 *
 * Existe desde antes de haver provedor, de propósito: assim a verificação de
 * contato e a recuperação de senha podem ser construídas e testadas agora, e
 * a escolha do provedor vira uma linha de configuração em vez de uma
 * reescrita (05 §15.1).
 */
interface CanalDeSaida
{
    /**
     * Entrega a mensagem e atualiza o envio.
     *
     * Implementação nenhuma deve lançar por falha de entrega: falha é estado
     * registrado no `Envio`, não exceção que derruba a operação que a
     * originou. Ninguém deve perder um cadastro porque o SMS não saiu.
     */
    public function entregar(Envio $envio, Mensagem $mensagem): void;

    /** Se este canal realmente coloca a mensagem no mundo. */
    public function entregaDeVerdade(): bool;
}
