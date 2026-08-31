<?php

namespace App\Dominio\Saida\Canais;

use App\Dominio\Saida\CanalDeSaida;
use App\Dominio\Saida\Mensagem;
use App\Models\Envio;
use RuntimeException;

/**
 * Canal para testes: finge entregar, e finge falhar quando mandado.
 *
 * Diferente do `CanalRegistrado`, este **marca entregue** — é como se
 * comporta um provedor de verdade. Serve para exercitar o caminho feliz e o
 * caminho de falha sem depender de rede, e para que a rotina de reenvio possa
 * ser testada antes de existir provedor.
 *
 * Não deve ser configurado fora de teste.
 */
class CanalDeMentira implements CanalDeSaida
{
    /** @var array<int, Mensagem> */
    public array $entregues = [];

    public function __construct(
        private readonly bool $falhaSempre = false,
        private readonly string $motivoDaFalha = 'Provedor indisponível.',
    ) {
    }

    public function entregar(Envio $envio, Mensagem $mensagem): void
    {
        if ($this->falhaSempre) {
            throw new RuntimeException($this->motivoDaFalha);
        }

        $this->entregues[] = $mensagem;
    }

    public function entregaDeVerdade(): bool
    {
        return true;
    }

    public function entregouPara(string $destino): bool
    {
        foreach ($this->entregues as $mensagem) {
            if ($mensagem->destino === $destino) {
                return true;
            }
        }

        return false;
    }
}
