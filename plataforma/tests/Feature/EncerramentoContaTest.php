<?php

namespace Tests\Feature;

use App\Dominio\Consumo\ApurarRealizacao;
use App\Dominio\Consumo\ResponderSolicitacao;
use App\Dominio\Consumo\SolicitarFavor;
use App\Dominio\Identidade\EncerrarConta;
use App\Dominio\Rotinas\RotinasTemporais;
use App\Dominio\Ticket\EmitirTickets;
use App\Livewire\Perfil;
use App\Models\Categoria;
use App\Models\Favor;
use App\Models\ManifestacaoRealizacao;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Subcategoria;
use App\Models\Ticket;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

/**
 * Encerramento de conta (RF-CAD-11 a RF-CAD-16, 02 §2.2).
 *
 * A regra que estes testes protegem é uma só, vista de vários ângulos:
 * **ticket emitido não desaparece porque quem o emitiu quis sair.**
 */
class EncerramentoContaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $ajudante;

    private Usuario $solicitante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametrosNegocioSeeder::class);

        $this->ajudante = $this->usuario('Marta Vilela');
        $this->solicitante = $this->usuario('Joel Batista');
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

    private function favorDe(Usuario $ajudante, int $validadeDias = 365): Favor
    {
        $categoria = Categoria::create(['nome' => 'Casa e reparos '.uniqid()]);
        $subcategoria = Subcategoria::create([
            'categoria_id' => $categoria->id,
            'nome' => 'Elétrica '.uniqid(),
        ]);
        $proficiencia = Proficiencia::create([
            'subcategoria_id' => $subcategoria->id,
            'nome' => 'Elétrica residencial',
        ]);

        $proficienciaUsuario = ProficienciaUsuario::create([
            'usuario_id' => $ajudante->id,
            'proficiencia_id' => $proficiencia->id,
            'nivel' => ProficienciaUsuario::NIVEL_ESPECIALISTA,
            'declarada_em' => now(),
        ]);

        $favor = Favor::create([
            'proficiencia_usuario_id' => $proficienciaUsuario->id,
            'titulo' => 'Instalações e reparos elétricos',
            'descricao' => 'Troco resistência de chuveiro, tomada e disjuntor.',
            'tempo_estimado_minutos' => 60,
            'modalidade' => Favor::MODALIDADE_REMOTO,
            'area_atendimento_km' => null,
            'antecedencia_minima_dias' => 2,
            'validade_ticket_dias' => $validadeDias,
            'maximo_circulacao' => 5,
            'trocavel' => true,
        ]);
        $favor->ativar();

        return $favor->fresh();
    }

    private function encerrar(): EncerrarConta
    {
        return new EncerrarConta();
    }

    // ------------------------------------------------- quem não deve nada

    public function test_conta_sem_ticket_circulando_sai_na_hora(): void
    {
        $usuario = $this->encerrar()->solicitar($this->ajudante);

        // RF-CAD-16.
        $this->assertSame(Usuario::ESTADO_DESATIVADA, $usuario->estado_conta);
        $this->assertNotNull($usuario->encerrada_em);
    }

    public function test_favor_sem_ticket_emitido_nao_prende_a_conta(): void
    {
        $this->favorDe($this->ajudante);

        $usuario = $this->encerrar()->solicitar($this->ajudante);

        $this->assertSame(Usuario::ESTADO_DESATIVADA, $usuario->estado_conta);
    }

    // ------------------------------------------------------ quem deve

    public function test_ticket_circulando_deixa_a_conta_encerrando(): void
    {
        $favor = $this->favorDe($this->ajudante);
        (new EmitirTickets())->executar($favor, 1);

        $usuario = $this->encerrar()->solicitar($this->ajudante);

        // RF-CAD-12. Sem dinheiro não há compensação: a proteção ao portador
        // é o prazo, e ela é integral (02 §2.2).
        $this->assertSame(Usuario::ESTADO_ENCERRANDO, $usuario->estado_conta);
        $this->assertNull($usuario->encerrada_em);
        $this->assertNotNull($usuario->encerramento_solicitado_em);
    }

    public function test_encerrar_desativa_os_favores_mas_nao_os_tickets(): void
    {
        $favor = $this->favorDe($this->ajudante);
        [$ticket] = (new EmitirTickets())->executar($favor, 1);

        $this->encerrar()->solicitar($this->ajudante);

        // RF-FAV-12 — desativar o favor tira do feed e não invalida o que já
        // foi emitido.
        $this->assertSame(Favor::ESTADO_DESATIVADO, $favor->fresh()->estado);
        $this->assertSame(Ticket::DISPONIVEL, $ticket->fresh()->estado);
    }

    public function test_conta_encerrando_nao_oferta_mas_ainda_atende(): void
    {
        $favor = $this->favorDe($this->ajudante);
        (new EmitirTickets())->executar($favor, 1);

        $this->encerrar()->solicitar($this->ajudante);
        $usuario = $this->ajudante->fresh();

        // RF-CAD-13 e RF-CAD-14: o direito de quem tem o ticket não depende
        // da vontade de quem saiu.
        $this->assertFalse($usuario->podeOfertar());
        $this->assertFalse($usuario->podeNegociar());
        $this->assertTrue($usuario->deveAtender());
    }

    public function test_conta_encerrando_nao_emite_mais_ticket(): void
    {
        $favor = $this->favorDe($this->ajudante);
        (new EmitirTickets())->executar($favor, 1);

        $this->encerrar()->solicitar($this->ajudante);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não pode emitir tickets/');

        (new EmitirTickets())->executar($favor->fresh(), 1);
    }

    public function test_o_ticket_de_quem_esta_saindo_ainda_e_solicitavel(): void
    {
        $favor = $this->favorDe($this->ajudante);
        [$ticket] = (new EmitirTickets())->executar($favor, 1);
        $ticket->transferirPara($this->solicitante, 'proposta_aceita');

        $this->encerrar()->solicitar($this->ajudante);

        $solicitacao = (new SolicitarFavor())->executar(
            $ticket->fresh(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );

        // É o que a proteção por prazo significa na prática.
        $this->assertNotNull($solicitacao->id);
    }

    // ------------------------------------------------- o que só o tempo faz

    public function test_a_conta_encerra_sozinha_quando_o_ultimo_ticket_e_consumido(): void
    {
        $favor = $this->favorDe($this->ajudante);
        [$ticket] = (new EmitirTickets())->executar($favor, 1);
        $ticket->transferirPara($this->solicitante, 'proposta_aceita');

        $this->encerrar()->solicitar($this->ajudante);

        $solicitacao = (new SolicitarFavor())->executar(
            $ticket->fresh(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        $apurar = new ApurarRealizacao();
        $solicitacao = $apurar->abrirJanela($solicitacao, $solicitacao->fim_previsto->copy()->addMinute());
        $quando = $solicitacao->fim_previsto->copy()->addHour();
        $apurar->manifestar($solicitacao, $this->ajudante, ManifestacaoRealizacao::CONFIRMA, $quando);
        $apurar->manifestar($solicitacao->fresh(), $this->solicitante, ManifestacaoRealizacao::CONFIRMA, $quando);

        $resultado = (new RotinasTemporais())->executar($quando);

        // RF-CAD-15.
        $this->assertSame(1, $resultado['contas_encerradas']);
        $this->assertSame(Usuario::ESTADO_DESATIVADA, $this->ajudante->fresh()->estado_conta);
    }

    public function test_a_conta_encerra_sozinha_quando_o_ultimo_ticket_vence(): void
    {
        $favor = $this->favorDe($this->ajudante, validadeDias: 30);
        (new EmitirTickets())->executar($favor, 1);

        $this->encerrar()->solicitar($this->ajudante);

        // Vencimento não avisa ninguém: sem a rotina, a conta ficaria presa
        // até alguém tocar nela por acaso.
        $resultado = (new RotinasTemporais())->executar(Carbon::now()->addDays(31));

        $this->assertSame(1, $resultado['tickets_expirados']);
        $this->assertSame(1, $resultado['contas_encerradas']);
        $this->assertSame(Usuario::ESTADO_DESATIVADA, $this->ajudante->fresh()->estado_conta);
    }

    public function test_a_rotina_nao_encerra_quem_ainda_deve(): void
    {
        $favor = $this->favorDe($this->ajudante);
        (new EmitirTickets())->executar($favor, 1);

        $this->encerrar()->solicitar($this->ajudante);

        $resultado = (new RotinasTemporais())->executar();

        $this->assertSame(0, $resultado['contas_encerradas']);
        $this->assertSame(Usuario::ESTADO_ENCERRANDO, $this->ajudante->fresh()->estado_conta);
    }

    // ------------------------------------------------------------ recusas

    public function test_nao_se_encerra_duas_vezes(): void
    {
        $encerrar = $this->encerrar();
        $encerrar->solicitar($this->ajudante);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/já está sendo encerrada/');

        $encerrar->solicitar($this->ajudante->fresh());
    }

    public function test_conta_suspensa_nao_se_encerra_a_pedido(): void
    {
        $this->ajudante->forceFill(['estado_conta' => Usuario::ESTADO_SUSPENSA])->save();

        // Sair enquanto se responde por algo apagaria a análise em curso.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Fale com a Operação/');

        $this->encerrar()->solicitar($this->ajudante->fresh());
    }

    public function test_o_cpf_continua_ocupado_depois_de_encerrar(): void
    {
        $cpf = $this->ajudante->cpf;
        $this->encerrar()->solicitar($this->ajudante);

        // RF-CAD-03 — senão bastaria encerrar e recadastrar para apagar o
        // histórico de confiança.
        $this->expectException(\Illuminate\Database\QueryException::class);

        Usuario::create([
            'nome' => 'Outra pessoa',
            'email' => uniqid('pessoa').'@exemplo.br',
            'telefone' => '11988420000',
            'cpf' => $cpf,
            'senha' => 'segredo123',
        ]);
    }

    // -------------------------------------------------------------- tela

    public function test_o_perfil_diz_quantos_favores_a_pessoa_ainda_deve(): void
    {
        $favor = $this->favorDe($this->ajudante);
        (new EmitirTickets())->executar($favor, 2);

        Livewire::actingAs($this->ajudante)
            ->test(Perfil::class)
            ->call('pedirEncerramento')
            ->assertSee('2 favores')
            // Trecho contíguo: o parágrafo quebra linha no Blade, e
            // `assertSee` compara substring exata do HTML.
            ->assertSee('continua precisando atender esses pedidos');
    }

    public function test_quem_nao_deve_nada_ve_o_aviso_certo(): void
    {
        Livewire::actingAs($this->ajudante)
            ->test(Perfil::class)
            ->call('pedirEncerramento')
            ->assertSee('Você não deve nenhum favor');
    }

    public function test_a_tela_encerra_e_desconecta(): void
    {
        Livewire::actingAs($this->ajudante)
            ->test(Perfil::class)
            ->call('pedirEncerramento')
            ->call('encerrarConta')
            ->assertRedirect(route('entrar'));

        $this->assertSame(Usuario::ESTADO_DESATIVADA, $this->ajudante->fresh()->estado_conta);
    }
}
