<?php

namespace Database\Seeders;

use App\Dominio\Ticket\EmitirTickets;
use App\Models\Categoria;
use App\Models\Endereco;
use App\Models\Favor;
use App\Models\JanelaDisponibilidade;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Subcategoria;
use App\Models\TermoPopular;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dados para percorrer o loop sem cadastrar tudo à mão.
 *
 * **Não roda em produção.** É andaime de desenvolvimento e de demonstração,
 * e o catálogo real é responsabilidade da Operação (RF-ADM-01).
 */
class DemonstracaoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('O DemonstracaoSeeder não roda em produção.');

            return;
        }

        $catalogo = $this->catalogo();
        [$marta, $joel] = $this->pessoas();

        $this->favorDe($marta, $catalogo['eletrica'], [
            'titulo' => 'Troco resistência de chuveiro e conserto tomada',
            'descricao' => "Levo as ferramentas. A peça (resistência, tomada, disjuntor) é por sua conta — aviso antes qual comprar.\n\nNão mexo em quadro de entrada nem em rede da rua.",
            'tempo_estimado_minutos' => 60,
            'modalidade' => Favor::MODALIDADE_PRESENCIAL,
            'area_atendimento_km' => 8,
            'antecedencia_minima_dias' => 2,
            'validade_ticket_dias' => 180,
            'maximo_circulacao' => 4,
        ], janelas: [[3, '08:00', '12:00'], [6, '09:00', '17:00']]);

        $this->favorDe($joel, $catalogo['reforco'], [
            'titulo' => 'Reforço de matemática do 6º ao 9º ano',
            'descricao' => "Uma hora por encontro, on-line. Trabalho em cima da lista de exercícios da escola.\n\nSe a criança tiver prova marcada, me avise antes para eu preparar.",
            'tempo_estimado_minutos' => 60,
            'modalidade' => Favor::MODALIDADE_REMOTO,
            'area_atendimento_km' => null,
            'antecedencia_minima_dias' => 1,
            'validade_ticket_dias' => 120,
            'maximo_circulacao' => 6,
        ]);

        $this->favorDe($marta, $catalogo['bolo'], [
            'titulo' => 'Bolo caseiro para aniversário',
            'descricao' => "Bolo de 25 pessoas, sabor a combinar. Você fornece os ingredientes ou eu compro e a gente acerta a lista antes.\n\nPreciso de dois dias de antecedência.",
            'tempo_estimado_minutos' => 180,
            'modalidade' => Favor::MODALIDADE_PRESENCIAL,
            'area_atendimento_km' => 5,
            'antecedencia_minima_dias' => 2,
            'validade_ticket_dias' => 90,
            'maximo_circulacao' => 2,
        ]);

        $this->command->info('Entre com marta@exemplo.br ou joel@exemplo.br — senha "segredo123".');
    }

    /** @return array<string, Proficiencia> */
    private function catalogo(): array
    {
        $proficiencias = [];

        $arvore = [
            'Casa e reparos' => [
                'Elétrica' => [
                    'eletrica' => ['Elétrica residencial', ['chuveiro', 'resistencia', 'disjuntor', 'tomada', 'curto', 'lampada', 'fiacao', 'chuveiro queimado']],
                ],
                'Hidráulica' => [
                    'encanamento' => ['Encanamento', ['vazamento', 'torneira', 'cano', 'pia entupida', 'ralo', 'descarga']],
                ],
            ],
            'Aulas e reforço' => [
                'Escolar' => [
                    'reforco' => ['Reforço escolar', ['matematica', 'dever de casa', 'reforco', 'prova', 'licao']],
                ],
            ],
            'Cozinha' => [
                'Doces e bolos' => [
                    'bolo' => ['Bolos e doces', ['bolo', 'aniversario', 'doce', 'festa']],
                ],
            ],
            'Cuidados' => [
                'Acompanhamento' => [
                    'idoso' => ['Acompanhamento de idoso', ['idoso', 'acamado', 'banho', 'acompanhar', 'cuidar da minha mae']],
                ],
            ],
        ];

        foreach ($arvore as $nomeCategoria => $subcategorias) {
            $categoria = Categoria::firstOrCreate(['nome' => $nomeCategoria]);

            foreach ($subcategorias as $nomeSub => $itens) {
                $subcategoria = Subcategoria::firstOrCreate([
                    'categoria_id' => $categoria->id,
                    'nome' => $nomeSub,
                ]);

                foreach ($itens as $chave => [$nome, $termos]) {
                    $proficiencia = Proficiencia::firstOrCreate([
                        'subcategoria_id' => $subcategoria->id,
                        'nome' => $nome,
                    ]);

                    foreach ($termos as $termo) {
                        TermoPopular::firstOrCreate([
                            'proficiencia_id' => $proficiencia->id,
                            'termo_normalizado' => TermoPopular::normalizar($termo),
                        ], ['termo' => $termo]);
                    }

                    $proficiencias[$chave] = $proficiencia;
                }
            }
        }

        return $proficiencias;
    }

    /** @return array<int, Usuario> */
    private function pessoas(): array
    {
        $pessoas = [
            ['Marta Vilela', 'marta@exemplo.br', '11988420001', '52998224725', -23.5505, -46.6333, 'Sé'],
            ['Joel Batista', 'joel@exemplo.br', '11988420002', '15350946056', -23.5610, -46.6560, 'Pinheiros'],
        ];

        return array_map(function (array $dados) {
            [$nome, $email, $telefone, $cpf, $lat, $lon, $bairro] = $dados;

            $usuario = Usuario::firstOrCreate(['email' => $email], [
                'nome' => $nome,
                'telefone' => $telefone,
                'cpf' => $cpf,
                'senha' => 'segredo123',
            ]);

            Endereco::firstOrCreate(['usuario_id' => $usuario->id], [
                'cep' => '01001000',
                'logradouro' => 'Rua das Oliveiras',
                'numero' => '100',
                'bairro' => $bairro,
                'municipio' => 'São Paulo',
                'uf' => 'SP',
            ]);

            DB::update(
                'UPDATE enderecos SET coordenada = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                 WHERE usuario_id = ?',
                [$lon, $lat, $usuario->id]
            );

            return $usuario;
        }, $pessoas);
    }

    private function favorDe(
        Usuario $ajudante,
        Proficiencia $proficiencia,
        array $atributos,
        array $janelas = [],
    ): void {
        $declarada = ProficienciaUsuario::firstOrCreate([
            'usuario_id' => $ajudante->id,
            'proficiencia_id' => $proficiencia->id,
        ], [
            'nivel' => ProficienciaUsuario::NIVEL_AVANCADO,
            'declarada_em' => now(),
        ]);

        if (Favor::where('proficiencia_usuario_id', $declarada->id)
            ->where('titulo', $atributos['titulo'])->exists()) {
            return;
        }

        $favor = Favor::create(array_merge($atributos, [
            'proficiencia_usuario_id' => $declarada->id,
            'trocavel' => true,
        ]));

        foreach ($janelas as [$dia, $inicio, $fim]) {
            JanelaDisponibilidade::create([
                'favor_id' => $favor->id,
                'dia_semana' => $dia,
                'hora_inicio' => $inicio,
                'hora_fim' => $fim,
            ]);
        }

        $favor->ativar();

        app(EmitirTickets::class)->executar($favor->fresh(), $atributos['maximo_circulacao']);
    }
}
