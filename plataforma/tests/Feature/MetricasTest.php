<?php

namespace Tests\Feature;

use App\Dominio\Consumo\ApurarRealizacao;
use App\Dominio\Consumo\ResponderSolicitacao;
use App\Dominio\Consumo\SolicitarFavor;
use App\Dominio\Descoberta\BuscarPorNecessidade;
use App\Dominio\Metricas\ApurarCobertura;
use App\Dominio\Metricas\ApurarMetricas;
use App\Dominio\Ticket\EmitirTickets;
use App\Models\Categoria;
use App\Models\Favor;
use App\Models\ManifestacaoRealizacao;
use App\Models\Operador;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Solicitacao;
use App\Models\Subcategoria;
use App\Models\TermoPopular;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Apuração das métricas do piloto (03 §7, 06 §10).
 *
 * O que estes testes protegem não é o número em si, e sim o **significado**
 * dele: reciprocidade conta quem fez as duas coisas, profundidade não conta a
 * emissão como troca, e falta de palavra não se mistura com falta de gente.
 * Errar qualquer um desses faria o painel mentir com aparência de precisão.
 */
class MetricasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametrosNegocioSeeder::class);
    }

    private function usuario(string $nome): Usuario
    {
        return Usuario::create([
            'nome' => $nome,
            'email' => uniqid('pessoa').'@exemplo.br',
            'telefone' => '11988420000',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'senha' => 'segredo123',
        ]);
    }

    private function proficiencia(string $nome): Proficiencia
    {
        $categoria = Categoria::create(['nome' => 'Casa e reparos '.uniqid()]);
        $subcategoria = Subcategoria::create([
            'categoria_id' => $categoria->id,
            'nome' => 'Reparos '.uniqid(),
        ]);

        return Proficiencia::create([
            'subcategoria_id' => $subcategoria->id,
            'nome' => $nome,
        ]);
    }

    private function favorDe(Usuario $ajudante, ?Proficiencia $proficiencia = null): Favor
    {
        $proficiencia ??= $this->proficiencia('Elétrica residencial');

        $proficienciaUsuario = ProficienciaUsuario::create([
            'usuario_id' => $ajudante->id,
            'proficiencia_id' => $proficiencia->id,
            'nivel' => ProficienciaUsuario::NIVEL_ESPECIALISTA,
            'declarada_em' => now(),
        ]);

        $favor = Favor::create([
            'proficiencia_usuario_id' => $proficienciaUsuario->id,
            'titulo' => 'Reparos em geral',
            'descricao' => 'Troco resistência de chuveiro, tomada e disjuntor.',
            'tempo_estimado_minutos' => 60,
            'modalidade' => Favor::MODALIDADE_REMOTO,
            'area_atendimento_km' => null,
            'antecedencia_minima_dias' => 2,
            'validade_ticket_dias' => 365,
            'maximo_circulacao' => 5,
            'trocavel' => true,
        ]);

        $favor->ativar();

        return $favor->fresh();
    }

    /**
     * Leva um favor de ponta a ponta até `REALIZADA`.
     */
    private function ciclo(Usuario $ajudante, Usuario $solicitante, ?Favor $favor = null): Solicitacao
    {
        $favor ??= $this->favorDe($ajudante);

        [$ticket] = (new EmitirTickets())->executar($favor, 1);
        $ticket->transferirPara($solicitante, 'proposta_aceita');

        $inicio = Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0);

        $solicitacao = (new SolicitarFavor())->executar($ticket->fresh(), $solicitante, $inicio);
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $ajudante);

        $apurar = new ApurarRealizacao();
        $solicitacao = $apurar->abrirJanela($solicitacao, $solicitacao->fim_previsto->copy()->addMinute());

        $momento = $solicitacao->fim_previsto->copy()->addHour();
        $apurar->manifestar($solicitacao, $ajudante, ManifestacaoRealizacao::CONFIRMA, $momento);

        return $apurar->manifestar(
            $solicitacao->fresh(),
            $solicitante,
            ManifestacaoRealizacao::CONFIRMA,
            $momento,
        );
    }

    public function test_reciprocidade_conta_apenas_quem_prestou_e_recebeu(): void
    {
        $marta = $this->usuario('Marta Vilela');
        $joel = $this->usuario('Joel Batista');

        // Só uma direção: Marta ajuda, Joel recebe.
        $this->ciclo($marta, $joel);

        $panorama = (new ApurarMetricas())->panorama();

        // Ninguém fez as duas coisas ainda — a rede é assimétrica.
        $this->assertSame(0.0, $panorama['reciprocidade']);

        // Agora Joel devolve a ajuda.
        $this->ciclo($joel, $marta);

        $panorama = (new ApurarMetricas())->panorama();

        $this->assertSame(100.0, $panorama['reciprocidade']);
    }

    public function test_participante_que_so_recebe_nao_conta_como_reciproco(): void
    {
        $ajudante = $this->usuario('Marta Vilela');
        $recebeSempre = $this->usuario('Joel Batista');
        $sofa = $this->usuario('Beto Sousa');

        $this->ciclo($ajudante, $recebeSempre);
        $this->ciclo($ajudante, $recebeSempre);

        $panorama = (new ApurarMetricas())->panorama();

        // Três pessoas, nenhuma prestou E recebeu. Beto nem apareceu.
        // É exatamente o risco R2: parece movimento, é extração.
        $this->assertSame(0.0, $panorama['reciprocidade']);
        $this->assertSame(3, $panorama['participantes']);
        $this->assertSame(2, $panorama['consumos_concluidos']);

        unset($sofa);
    }

    public function test_profundidade_nao_conta_a_emissao_como_troca(): void
    {
        $ajudante = $this->usuario('Marta Vilela');
        $solicitante = $this->usuario('Joel Batista');

        $this->ciclo($ajudante, $solicitante);

        $panorama = (new ApurarMetricas())->panorama();

        // Uma transferência real. A emissão é o nascimento do ticket, não uma
        // troca — contá-la inflaria o indicador que testa o conceito central.
        $this->assertSame(1.0, $panorama['profundidade_circulacao']);
    }

    public function test_profundidade_sobe_quando_o_ticket_circula_antes_do_consumo(): void
    {
        $ajudante = $this->usuario('Marta Vilela');
        $primeiro = $this->usuario('Joel Batista');
        $segundo = $this->usuario('Rita Alves');

        $favor = $this->favorDe($ajudante);
        [$ticket] = (new EmitirTickets())->executar($favor, 1);

        // O ticket passa por duas mãos antes de ser usado — é o mecanismo que
        // ataca a dupla coincidência de desejos (06 §2.3).
        $ticket->transferirPara($primeiro, 'proposta_aceita');
        $ticket->fresh()->transferirPara($segundo, 'proposta_aceita');

        $inicio = Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0);
        $solicitacao = (new SolicitarFavor())->executar($ticket->fresh(), $segundo, $inicio);
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $ajudante);

        $apurar = new ApurarRealizacao();
        $solicitacao = $apurar->abrirJanela($solicitacao, $solicitacao->fim_previsto->copy()->addMinute());
        $momento = $solicitacao->fim_previsto->copy()->addHour();
        $apurar->manifestar($solicitacao, $ajudante, ManifestacaoRealizacao::CONFIRMA, $momento);
        $apurar->manifestar($solicitacao->fresh(), $segundo, ManifestacaoRealizacao::CONFIRMA, $momento);

        $panorama = (new ApurarMetricas())->panorama();

        $this->assertSame(2.0, $panorama['profundidade_circulacao']);
    }

    public function test_participantes_com_favor_mede_a_oferta(): void
    {
        $ofertante = $this->usuario('Marta Vilela');
        $this->usuario('Joel Batista');

        $this->favorDe($ofertante);

        $panorama = (new ApurarMetricas())->panorama();

        $this->assertSame(2, $panorama['participantes']);
        $this->assertSame(1, $panorama['com_favor']);
    }

    public function test_coorte_isola_a_turma_de_cada_mes(): void
    {
        // Ancorado no dia 5 do mês, e não em `now()->subMonths(2)`: rodando
        // no dia 31, subtrair dois meses cai em "31 de junho", que não existe,
        // e o Carbon transborda para 1º de julho — a pessoa apareceria na
        // coorte seguinte. O código de produção escapa disso porque normaliza
        // com `startOfMonth()` antes de subtrair.
        $antigo = $this->usuario('Marta Vilela');
        $antigo->forceFill([
            'created_at' => Carbon::now()->startOfMonth()->subMonths(2)->addDays(4),
        ])->save();

        $this->usuario('Joel Batista');

        $coortes = (new ApurarMetricas())->coortes(3);

        $this->assertCount(3, $coortes);
        $this->assertSame(1, $coortes[0]['integrantes']);
        $this->assertSame(0, $coortes[1]['integrantes']);
        $this->assertSame(1, $coortes[2]['integrantes']);
    }

    public function test_metricas_sem_dados_devolvem_vazio_e_nao_zero(): void
    {
        $panorama = (new ApurarMetricas())->panorama();

        // Nulo e zero dizem coisas diferentes: "ainda não sabemos" não é
        // "medimos e deu zero". Um painel que confunde os dois mente.
        $this->assertNull($panorama['taxa_aceite']);
        $this->assertNull($panorama['profundidade_circulacao']);
        $this->assertNull($panorama['reciprocidade']);
        $this->assertSame(0, $panorama['participantes']);
    }

    public function test_falta_de_palavra_nao_se_mistura_com_falta_de_gente(): void
    {
        $proficiencia = $this->proficiencia('Elétrica residencial');
        TermoPopular::create([
            'proficiencia_id' => $proficiencia->id,
            'termo' => 'chuveiro',
        ]);

        $usuario = $this->usuario('Joel Batista');

        // Reconhece a proficiência, mas ninguém oferece: falta gente.
        (new BuscarPorNecessidade())->executar('trocar a resistência do chuveiro', $usuario);

        // Não reconhece nada: falta palavra.
        (new BuscarPorNecessidade())->executar('afinar violino', $usuario);

        $apurador = new ApurarCobertura();

        $vocabulario = $apurador->vocabularioFaltante();
        $cobertura = $apurador->coberturaFaltante();

        $this->assertCount(1, $vocabulario);
        $this->assertSame('afinar violino', $vocabulario[0]['texto']);

        $this->assertCount(1, $cobertura);
        $this->assertSame('Elétrica residencial', $cobertura[0]['proficiencia']);
    }

    public function test_as_paginas_do_painel_renderizam_para_a_operacao(): void
    {
        $operador = Operador::create([
            'nome' => 'Operação',
            'email' => uniqid('op').'@ajude.me',
            'senha' => 'segredo123',
        ]);

        // Com dados, para que as tabelas e os cálculos sejam exercitados —
        // uma página vazia não prova que a que tem conteúdo funciona.
        $this->ciclo($this->usuario('Marta Vilela'), $this->usuario('Joel Batista'));

        $this->actingAs($operador, 'operacao')
            ->get('/operacao/metricas')
            ->assertOk()
            ->assertSee('Reciprocidade')
            ->assertSee('Coortes mensais');

        $this->actingAs($operador, 'operacao')
            ->get('/operacao/cobertura')
            ->assertOk()
            ->assertSee('Falta vocabulário')
            ->assertSee('Falta quem ofereça');
    }

    public function test_o_painel_de_metricas_exige_ser_da_operacao(): void
    {
        $this->get('/operacao/metricas')->assertRedirect();

        // Usuário comum não entra: a Operação é outro ator, não um usuário
        // com permissão (05 §3.1).
        $this->actingAs($this->usuario('Joel Batista'))
            ->get('/operacao/metricas')
            ->assertRedirect();
    }

    public function test_busca_atendida_nao_aparece_em_nenhuma_das_listas(): void
    {
        $proficiencia = $this->proficiencia('Elétrica residencial');
        TermoPopular::create([
            'proficiencia_id' => $proficiencia->id,
            'termo' => 'chuveiro',
        ]);

        $ajudante = $this->usuario('Marta Vilela');
        $favor = $this->favorDe($ajudante, $proficiencia);
        (new EmitirTickets())->executar($favor, 1);

        (new BuscarPorNecessidade())->executar(
            'trocar a resistência do chuveiro',
            $this->usuario('Joel Batista')
        );

        $apurador = new ApurarCobertura();

        $this->assertCount(0, $apurador->vocabularioFaltante());
        $this->assertCount(0, $apurador->coberturaFaltante());
    }
}
