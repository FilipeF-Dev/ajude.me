<?php

namespace App\Console\Commands;

use App\Dominio\Rotinas\RotinasTemporais;
use Illuminate\Console\Command;

/**
 * Executa as rotinas temporais (05 §15.2).
 *
 * Agendado a cada dez minutos em `routes/console.php`. A granularidade não
 * precisa ser fina: as condições são de horas e dias, e todas são avaliação
 * de estado (A6) — atrasar uma execução adia o efeito, nunca o perde.
 */
class RodarRotinasTemporais extends Command
{
    protected $signature = 'ajudeme:rotinas';

    protected $description = 'Encerra prazos vencidos, apura confirmações tácitas e cumpre a fila de avisos';

    public function handle(RotinasTemporais $rotinas): int
    {
        $resultado = $rotinas->executar();

        foreach ($resultado as $passo => $quantidade) {
            $this->line(str_pad($passo, 34).$quantidade);
        }

        return self::SUCCESS;
    }
}
