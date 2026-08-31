<?php

namespace Database\Seeders;

use App\Models\ParametroNegocio;
use Illuminate\Database\Seeder;

/**
 * Valores decididos na Fase 0 (02 §21).
 *
 * Ficam no banco, não no código, porque o P6 proíbe prazo como constante —
 * e porque alterá-los tem de ser possível sem reimplantar a aplicação.
 */
class ParametrosNegocioSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            [
                ParametroNegocio::JANELA_CONTESTACAO_HORAS,
                '72',
                'Prazo para contestar a realização antes da confirmação tácita (02 §8.4).',
            ],
            [
                ParametroNegocio::MANIFESTACAO_CHAMADO_DIAS,
                '5',
                'Prazo de cada parte para apresentar sua versão em um chamado (02 §8.5).',
            ],
            [
                ParametroNegocio::AVALIACAO_PENDENTE_DIAS,
                '7',
                'Prazo para avaliar antes de ficar impedido de enviar novas propostas (02 §11.4).',
            ],
            [
                ParametroNegocio::VALIDADE_PROPOSTA_DIAS,
                '7',
                'Validade de uma proposta de troca; os tickets ficam bloqueados nesse período (02 §5.4).',
            ],
            [
                ParametroNegocio::CANCELAMENTO_SEM_OCORRENCIA_HORAS,
                '24',
                'Antecedência que separa cancelar sem consequência de cancelar registrando ocorrência (02 §9.2).',
            ],
            [
                ParametroNegocio::VALIDADE_MAXIMA_TICKET_DIAS,
                '365',
                'Teto de validade do ticket. É o que impede uma conta em encerramento de ficar presa indefinidamente (02 §2.2).',
            ],
            [
                ParametroNegocio::RETENCAO_TEXTO_BUSCA_DIAS,
                '90',
                'Prazo após o qual o texto original da busca é apagado, restando apenas o agregado (02 §18.3).',
            ],
            [
                ParametroNegocio::VERIFICACAO_VALIDADE_MINUTOS,
                '30',
                'Quanto tempo um código de verificação continua valendo (RF-CAD-04, RF-CAD-05).',
            ],
            [
                ParametroNegocio::VERIFICACAO_TENTATIVAS_MAXIMAS,
                '5',
                'Erros de código antes de travar a verificação. Limita adivinhação (NF-SEG-06).',
            ],
            [
                ParametroNegocio::VERIFICACAO_INTERVALO_REENVIO_SEGUNDOS,
                '60',
                'Espera entre pedidos de código. SMS custa por mensagem e pedir código é gatilho de abuso (05 §15.1).',
            ],
            [
                ParametroNegocio::RECUPERACAO_VALIDADE_MINUTOS,
                '60',
                'Quanto tempo o link de recuperação de senha continua valendo (RF-CAD-08).',
            ],
            [
                ParametroNegocio::RECUPERACAO_INTERVALO_SEGUNDOS,
                '60',
                'Espera entre pedidos de recuperação, para o formulário não virar torneira de mensagens.',
            ],
            [
                ParametroNegocio::CADASTRO_POR_CONVITE,
                '1',
                'Base restrita: o cadastro exige convite. Desligar na Fase 4, quando o cadastro abrir (06 §8).',
            ],
        ];

        foreach ($parametros as [$chave, $valor, $descricao]) {
            if (ParametroNegocio::valorEm($chave) !== null) {
                continue;
            }

            ParametroNegocio::create([
                'chave' => $chave,
                'valor' => $valor,
                'descricao' => $descricao,
                'vigente_de' => now(),
            ]);
        }
    }
}
