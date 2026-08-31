<?php

namespace Tests\Feature;

use App\Dominio\Consumo\ResponderSolicitacao;
use App\Dominio\Consumo\SolicitarFavor;
use App\Dominio\Rotinas\RotinasTemporais;
use App\Dominio\Saida\Canais\CanalDeMentira;
use App\Dominio\Saida\CanalDeSaida;
use App\Dominio\Seguranca\AvisarContatoDeConfianca;
use App\Dominio\Seguranca\GerirTermoDeCiencia;
use App\Dominio\Ticket\EmitirTickets;
use App\Models\Operador;
use App\Models\Categoria;
use App\Models\ContatoConfianca;
use App\Models\Endereco;
use App\Models\Envio;
use App\Models\Favor;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\RegistroComparecimento;
use App\Models\Solicitacao;
use App\Models\Subcategoria;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Avisos ao contato de confiança (RF-SEG-02, RF-SEG-10, RF-NOT-08, RF-NOT-11).
 *
 * É o único mecanismo que age fora da plataforma em situação de risco, e o
 * único endereçado a alguém que não tem conta.
 */
class AvisoContatoConfiancaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $ajudante;

    private Usuario $solicitante;

    private CanalDeMentira $canal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametrosNegocioSeeder::class);

        // Canal que finge entregar, para o teste ver o que sairia.
        $this->canal = new CanalDeMentira();
        $this->app->instance(CanalDeSaida::class, $this->canal);

        $this->ajudante = $this->usuario('Marta Vilela');
        $this->solicitante = $this->usuario('Joel Batista');

        // Favor presencial exige o termo vigente aceito pelos dois lados.
        (new GerirTermoDeCiencia())->publicar(
            'Atendimento presencial envolve risco. Leia com atenção.',
            Operador::create([
                'nome' => 'Operação',
                'email' => uniqid('op').'@ajude.me',
                'senha' => 'segredo123',
            ]),
        );
    }

    private function usuario(string $nome): Usuario
    {
        $usuario = Usuario::create([
            'nome' => $nome,
            'email' => uniqid('pessoa').'@exemplo.br',
            'telefone' => '1198842'.random_int(1000, 9999),
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'senha' => 'segredo123',
        ]);

        Endereco::create([
            'usuario_id' => $usuario->id,
            'cep' => '02345678',
            'logradouro' => 'Rua das Acácias',
            'numero' => (string) random_int(10, 999),
            'bairro' => 'Jardim das Oliveiras',
            'municipio' => 'São Paulo',
            'uf' => 'SP',
        ]);

        // Favor presencial confere a área de atendimento e exige coordenada
        // nas duas contas. As duas ficam no mesmo quarteirão.
        DB::statement(
            'UPDATE enderecos SET coordenada = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
             WHERE usuario_id = ?',
            [-46.6333, -23.5505, $usuario->id],
        );

        return $usuario->fresh();
    }

    private function contatoDe(Usuario $pessoa, string $nome = 'Ana Paula'): ContatoConfianca
    {
        return ContatoConfianca::create([
            'usuario_id' => $pessoa->id,
            'nome' => $nome,
            'telefone' => '1197777'.random_int(1000, 9999),
            'permissao_declarada_em' => now(),
        ]);
    }

    private function favor(string $modalidade = Favor::MODALIDADE_PRESENCIAL): Favor
    {
        $categoria = Categoria::create(['nome' => 'Casa '.uniqid()]);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'nome' => 'Elétrica '.uniqid()]);
        $proficiencia = Proficiencia::create(['subcategoria_id' => $subcategoria->id, 'nome' => 'Elétrica residencial']);

        $proficienciaUsuario = ProficienciaUsuario::create([
            'usuario_id' => $this->ajudante->id,
            'proficiencia_id' => $proficiencia->id,
            'nivel' => ProficienciaUsuario::NIVEL_ESPECIALISTA,
            'declarada_em' => now(),
        ]);

        $favor = Favor::create([
            'proficiencia_usuario_id' => $proficienciaUsuario->id,
            'titulo' => 'Instalações e reparos elétricos',
            'descricao' => 'Troco resistência de chuveiro.',
            'tempo_estimado_minutos' => 60,
            'modalidade' => $modalidade,
            'area_atendimento_km' => $modalidade === Favor::MODALIDADE_PRESENCIAL ? 5 : null,
            'antecedencia_minima_dias' => 2,
            'validade_ticket_dias' => 365,
            'maximo_circulacao' => 5,
            'trocavel' => true,
        ]);
        $favor->ativar();

        return $favor->fresh();
    }

    /**
     * @param  int  $hora  distingue agendamentos: o ajudante não pode ter dois
     *                     atendimentos sobrepostos (RF-SOL-23)
     */
    private function agendar(?Favor $favor = null, ?Carbon $momento = null, int $hora = 10): Solicitacao
    {
        $favor ??= $this->favor();
        $presencial = $favor->modalidade === Favor::MODALIDADE_PRESENCIAL;

        [$ticket] = (new EmitirTickets())->executar($favor, 1);
        $ticket->transferirPara($this->solicitante, 'proposta_aceita');

        $solicitacao = (new SolicitarFavor())->executar(
            $ticket->fresh(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime($hora, 0),
            $momento,
            cienteDosRiscos: $presencial,
        );

        return (new ResponderSolicitacao())->agendar(
            $solicitacao,
            $this->ajudante,
            $momento,
            cienteDosRiscos: $presencial,
        );
    }

    private function avisos(): array
    {
        return Envio::where('tipo', 'aviso_contato_confianca')->get()->all();
    }

    // -------------------------------------------------------- agendamento

    public function test_o_agendamento_presencial_avisa_os_dois_contatos(): void
    {
        $this->contatoDe($this->ajudante, 'Ana Paula');
        $this->contatoDe($this->solicitante, 'Beto Sousa');

        $this->agendar();

        // Cada lado tem alguém de fora sabendo onde ele vai estar.
        $this->assertCount(2, $this->avisos());
    }

    public function test_o_aviso_diz_com_quem_onde_e_quando(): void
    {
        $this->contatoDe($this->ajudante);

        $solicitacao = $this->agendar();
        $corpo = Envio::where('tipo', 'aviso_contato_confianca')->sole()->corpo;

        $this->assertStringContainsString('Marta Vilela', $corpo);
        $this->assertStringContainsString('Joel Batista', $corpo);
        $this->assertStringContainsString('Rua das Acácias', $corpo);
        $this->assertStringContainsString($solicitacao->inicio_previsto->format('d/m'), $corpo);
    }

    public function test_favor_remoto_nao_avisa_ninguem(): void
    {
        $this->contatoDe($this->ajudante);
        $this->contatoDe($this->solicitante);

        $this->agendar($this->favor(Favor::MODALIDADE_REMOTO));

        // Não há encontro físico: avisar seria ruído.
        $this->assertCount(0, $this->avisos());
    }

    public function test_quem_nao_indicou_contato_nao_gera_aviso(): void
    {
        $this->contatoDe($this->ajudante);

        $this->agendar();

        $this->assertCount(1, $this->avisos());
    }

    public function test_o_agendamento_acontece_mesmo_sem_contato_algum(): void
    {
        $solicitacao = $this->agendar();

        // O aviso é proteção, não requisito: sem ele o atendimento segue.
        $this->assertSame(Solicitacao::AGENDADA, $solicitacao->estado);
        $this->assertCount(0, $this->avisos());
    }

    // ------------------------------------------------------- apresentação

    public function test_a_primeira_mensagem_se_apresenta_e_diz_como_sair(): void
    {
        $contato = $this->contatoDe($this->ajudante);
        $this->assertTrue($contato->precisaDeApresentacao());

        $this->agendar();

        $corpo = Envio::where('tipo', 'aviso_contato_confianca')->sole()->corpo;

        // A pessoa não tem conta e nunca aceitou termo algum: sem isso,
        // recebe endereço de estranho de um número desconhecido.
        $this->assertStringContainsString('indicou você como contato de confiança', $corpo);
        $this->assertStringContainsString('responda SAIR', $corpo);

        $this->assertNotNull($contato->fresh()->avisado_em);
    }

    public function test_a_segunda_mensagem_nao_repete_a_apresentacao(): void
    {
        $this->contatoDe($this->ajudante);

        $this->agendar(hora: 10);
        $this->agendar(hora: 15);

        $avisos = Envio::where('tipo', 'aviso_contato_confianca')->orderBy('id')->get();

        $this->assertCount(2, $avisos);
        $this->assertStringContainsString('responda SAIR', $avisos->first()->corpo);
        $this->assertStringNotContainsString('responda SAIR', $avisos->last()->corpo);
    }

    public function test_quem_recusou_nao_recebe_mais_nada(): void
    {
        $contato = $this->contatoDe($this->ajudante);
        $contato->forceFill([
            'avisado_em' => now(),
            'recusado_em' => now(),
        ])->save();

        $this->agendar();

        // Consentimento que se ignora quando convém não é consentimento.
        $this->assertCount(0, $this->avisos());
    }

    // --------------------------------------------------- check-out ausente

    public function test_check_in_sem_check_out_avisa_o_contato(): void
    {
        $this->contatoDe($this->ajudante);
        $solicitacao = $this->agendar();

        RegistroComparecimento::create([
            'solicitacao_id' => $solicitacao->id,
            'parte' => Solicitacao::PARTE_AJUDANTE,
            'tipo' => RegistroComparecimento::INICIO,
            'momento' => $solicitacao->inicio_previsto,
        ]);

        $depois = $solicitacao->fim_previsto->copy()->addMinutes(90);
        $resultado = (new RotinasTemporais())->executar($depois);

        // Registro de saída que ninguém observa não protege ninguém.
        $this->assertSame(1, $resultado['avisos_de_checkout']);
        $this->assertStringContainsString(
            'ainda não foi encerrado',
            Envio::where('tipo', 'aviso_contato_confianca')->latest('id')->first()->corpo,
        );
    }

    public function test_quem_fez_check_out_nao_gera_aviso(): void
    {
        $this->contatoDe($this->ajudante);
        $solicitacao = $this->agendar();

        foreach ([RegistroComparecimento::INICIO, RegistroComparecimento::FIM] as $tipo) {
            RegistroComparecimento::create([
                'solicitacao_id' => $solicitacao->id,
                'parte' => Solicitacao::PARTE_AJUDANTE,
                'tipo' => $tipo,
                'momento' => $solicitacao->inicio_previsto,
            ]);
        }

        $resultado = (new RotinasTemporais())->executar($solicitacao->fim_previsto->copy()->addMinutes(90));

        $this->assertSame(0, $resultado['avisos_de_checkout']);
    }

    public function test_quem_nunca_fez_check_in_nao_gera_aviso(): void
    {
        $this->contatoDe($this->ajudante);
        $solicitacao = $this->agendar();

        // Quem não entrou provavelmente não foi, e isso é outro problema.
        $resultado = (new RotinasTemporais())->executar($solicitacao->fim_previsto->copy()->addMinutes(90));

        $this->assertSame(0, $resultado['avisos_de_checkout']);
    }

    public function test_a_tolerancia_e_respeitada(): void
    {
        $this->contatoDe($this->ajudante);
        $solicitacao = $this->agendar();

        RegistroComparecimento::create([
            'solicitacao_id' => $solicitacao->id,
            'parte' => Solicitacao::PARTE_AJUDANTE,
            'tipo' => RegistroComparecimento::INICIO,
            'momento' => $solicitacao->inicio_previsto,
        ]);

        // Dez minutos depois do previsto ainda é atraso comum.
        $cedo = (new RotinasTemporais())->executar($solicitacao->fim_previsto->copy()->addMinutes(10));

        $this->assertSame(0, $cedo['avisos_de_checkout']);
    }

    public function test_o_aviso_de_checkout_nao_se_repete_a_cada_rodada(): void
    {
        $this->contatoDe($this->ajudante);
        $solicitacao = $this->agendar();

        RegistroComparecimento::create([
            'solicitacao_id' => $solicitacao->id,
            'parte' => Solicitacao::PARTE_AJUDANTE,
            'tipo' => RegistroComparecimento::INICIO,
            'momento' => $solicitacao->inicio_previsto,
        ]);

        $rotinas = new RotinasTemporais();
        $depois = $solicitacao->fim_previsto->copy()->addMinutes(90);

        $rotinas->executar($depois);
        $rotinas->executar($depois->copy()->addMinutes(10));

        // As rotinas rodam a cada dez minutos: sem idempotência, o contato
        // seria bombardeado até alguém lembrar de encerrar.
        $this->assertCount(2, $this->avisos());
    }

    // -------------------------------------------------------------- falha

    public function test_falha_de_envio_nao_derruba_o_agendamento(): void
    {
        $this->app->instance(CanalDeSaida::class, new CanalDeMentira(falhaSempre: true));
        $this->contatoDe($this->ajudante);

        $solicitacao = $this->agendar();

        $this->assertSame(Solicitacao::AGENDADA, $solicitacao->estado);

        // NF-DIS-03 — falha do canal de segurança fica visível para escalar,
        // e não engolida em log.
        $this->assertSame(1, Envio::falhados()->count());
    }

    public function test_o_canal_que_nao_envia_deixa_o_aviso_registrado(): void
    {
        $this->app->instance(CanalDeSaida::class, new \App\Dominio\Saida\Canais\CanalRegistrado());
        $this->contatoDe($this->ajudante);

        $this->agendar();

        // Sem provedor contratado, o aviso de segurança não chega a ninguém —
        // e o painel diz isso, em vez de fingir entrega.
        $this->assertSame(Envio::REGISTRADO, Envio::where('tipo', 'aviso_contato_confianca')->sole()->estado);
    }
}
