<?php

namespace Tests\Feature;

use App\Dominio\Consumo\ApurarRealizacao;
use App\Dominio\Consumo\RegistrarComparecimento;
use App\Dominio\Consumo\ResponderSolicitacao;
use App\Dominio\Consumo\SolicitarFavor;
use App\Livewire\Atendimento;
use App\Livewire\Avaliar;
use App\Livewire\Cadastrar;
use App\Livewire\CancelarAtendimento;
use App\Livewire\Carteira;
use App\Livewire\Entrar;
use App\Livewire\Inicio;
use App\Models\ManifestacaoRealizacao;
use App\Models\Solicitacao;
use Illuminate\Support\Carbon;
use App\Models\Categoria;
use App\Models\Favor;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Subcategoria;
use App\Models\TermoPopular;
use App\Models\Usuario;
use App\Dominio\Ticket\EmitirTickets;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A camada web (decisão A1, 05 §15).
 *
 * Cobre o que a interface promete e que o domínio sozinho não garante: quem
 * entra, o que aparece, e o que **não** aparece.
 */
class CamadaWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametrosNegocioSeeder::class);
    }

    private function usuario(string $nome = 'Joel Batista'): Usuario
    {
        return Usuario::create([
            'nome' => $nome,
            'email' => uniqid('pessoa').'@exemplo.br',
            'telefone' => '11988420000',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'senha' => 'segredo123',
        ]);
    }

    private function favorComTicket(Usuario $ajudante, array $termos = ['chuveiro']): Favor
    {
        $categoria = Categoria::create(['nome' => 'Casa e reparos '.uniqid()]);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'nome' => 'Reparos']);
        $proficiencia = Proficiencia::create([
            'subcategoria_id' => $subcategoria->id,
            'nome' => 'Elétrica residencial',
        ]);

        foreach ($termos as $termo) {
            TermoPopular::create(['proficiencia_id' => $proficiencia->id, 'termo' => $termo]);
        }

        $declarada = ProficienciaUsuario::create([
            'usuario_id' => $ajudante->id,
            'proficiencia_id' => $proficiencia->id,
            'nivel' => ProficienciaUsuario::NIVEL_ESPECIALISTA,
            'declarada_em' => now(),
        ]);

        $favor = Favor::create([
            'proficiencia_usuario_id' => $declarada->id,
            'titulo' => 'Troco resistência de chuveiro',
            'descricao' => 'Levo as ferramentas; a peça é por sua conta.',
            'tempo_estimado_minutos' => 60,
            'modalidade' => Favor::MODALIDADE_REMOTO,
            'area_atendimento_km' => null,
            'antecedencia_minima_dias' => 2,
            'validade_ticket_dias' => 180,
            'maximo_circulacao' => 3,
            'trocavel' => true,
        ]);

        $favor->ativar();
        (new EmitirTickets())->executar($favor->fresh(), 2);

        return $favor->fresh();
    }

    public function test_quem_nao_entrou_vai_para_a_tela_de_entrada(): void
    {
        foreach (['/', '/carteira', '/perfil', '/conversas', '/oferecer'] as $rota) {
            $this->get($rota)->assertRedirect(route('entrar'));
        }
    }

    public function test_o_visitante_ve_a_tela_de_entrada(): void
    {
        $this->get('/entrar')->assertOk()->assertSee('Ajude.me');
    }

    public function test_a_entrada_recusa_senha_errada_sem_dizer_se_o_email_existe(): void
    {
        $usuario = $this->usuario();

        Livewire::test(Entrar::class)
            ->set('email', $usuario->email)
            ->set('senha', 'errada')
            ->call('entrar')
            // A mensagem é a mesma para e-mail inexistente: dizer o contrário
            // transformaria a tela numa lista de quem está cadastrado.
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_a_entrada_com_credenciais_corretas_leva_ao_inicio(): void
    {
        $usuario = $this->usuario();

        Livewire::test(Entrar::class)
            ->set('email', $usuario->email)
            ->set('senha', 'segredo123')
            ->call('entrar')
            ->assertRedirect(route('inicio'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_o_cadastro_recusa_cpf_invalido(): void
    {
        Livewire::test(Cadastrar::class)
            ->set('nome', 'Ana Prado')
            ->set('email', 'ana@exemplo.br')
            ->set('telefone', '11988420000')
            // RF-CAD-10 — o CPF é imutável depois. Barrar o erro de digitação
            // aqui evita uma conta impossível de corrigir.
            ->set('cpf', '111.111.111-11')
            ->set('senha', 'segredo123')
            ->set('senha_confirmation', 'segredo123')
            ->call('cadastrar')
            ->assertHasErrors(['cpf']);

        $this->assertGuest();
    }

    public function test_o_feed_mostra_favores_de_outras_pessoas(): void
    {
        $ajudante = $this->usuario('Marta Vilela');
        $this->favorComTicket($ajudante);

        Livewire::actingAs($this->usuario())
            ->test(Inicio::class)
            ->assertSee('Troco resistência de chuveiro');
    }

    public function test_o_feed_nao_mostra_a_propria_oferta(): void
    {
        $ajudante = $this->usuario('Marta Vilela');
        $this->favorComTicket($ajudante);

        Livewire::actingAs($ajudante)
            ->test(Inicio::class)
            ->assertDontSee('Troco resistência de chuveiro');
    }

    public function test_a_busca_por_necessidade_mostra_a_interpretacao(): void
    {
        $ajudante = $this->usuario('Marta Vilela');
        $this->favorComTicket($ajudante);

        Livewire::actingAs($this->usuario())
            ->test(Inicio::class)
            ->set('necessidade', 'o chuveiro parou de esquentar')
            ->call('buscar')
            // RF-BUS-18 — a interpretação aparece antes dos resultados.
            ->assertSee('Entendi que você procura')
            ->assertSee('Elétrica residencial')
            ->assertSee('Troco resistência de chuveiro');
    }

    public function test_busca_sem_resposta_oferece_a_fila_de_aviso(): void
    {
        $this->usuario('Marta Vilela'); // existe a proficiência, ninguém oferece
        $ajudante = $this->usuario('Outra Pessoa');
        $favor = $this->favorComTicket($ajudante);
        $favor->desativar();

        Livewire::actingAs($this->usuario())
            ->test(Inicio::class)
            ->set('necessidade', 'chuveiro queimado')
            ->call('buscar')
            // RF-BUS-21 — busca sem resposta não é resultado vazio.
            ->assertSee('Ainda não há ninguém oferecendo isso por aqui')
            ->assertSee('Avise-me sobre');
    }

    public function test_a_carteira_lista_os_tickets_por_estado(): void
    {
        $ajudante = $this->usuario('Marta Vilela');
        $favor = $this->favorComTicket($ajudante);
        $solicitante = $this->usuario();

        $favor->fresh();
        \App\Models\Ticket::where('favor_id', $favor->id)
            ->first()
            ->transferirPara($solicitante, 'proposta_aceita');

        Livewire::actingAs($solicitante)
            ->test(Carteira::class)
            ->assertSee('Troco resistência de chuveiro')
            ->assertSee('Disponíveis · 1');
    }

    public function test_a_carteira_nao_apresenta_saldo(): void
    {
        $solicitante = $this->usuario();

        $html = Livewire::actingAs($solicitante)->test(Carteira::class)->html();

        // P8 — não existe saldo, crédito nem valor acumulável. A ausência é a
        // garantia; um "total" na tela seria o primeiro passo para a moeda.
        foreach (['saldo', 'crédito', 'R$', 'total a receber'] as $proibido) {
            $this->assertStringNotContainsStringIgnoringCase($proibido, $html);
        }
    }

    public function test_o_manifesto_do_aplicativo_e_servido(): void
    {
        $this->get('/manifesto.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8')
            ->assertSee('standalone');
    }

    public function test_o_manifesto_traz_os_icones_png_que_o_chrome_exige(): void
    {
        $manifesto = $this->get('/manifesto.webmanifest')->json();

        // Sem PNG de 192 e 512 o Chrome não oferece a instalação — e a
        // decisão A1 (05 §15.3) depende de a instalação funcionar.
        $tamanhos = collect($manifesto['icons'])
            ->where('type', 'image/png')
            ->pluck('sizes');

        $this->assertTrue($tamanhos->contains('192x192'));
        $this->assertTrue($tamanhos->contains('512x512'));

        foreach ($manifesto['icons'] as $icone) {
            $caminho = public_path(parse_url($icone['src'], PHP_URL_PATH));
            $this->assertFileExists($caminho);
        }
    }

    // ------------------------------------------------ Telas do atendimento

    /** Deixa uma solicitação agendada entre duas pessoas. */
    private function atendimentoAgendado(): Solicitacao
    {
        $ajudante = $this->usuario('Marta Vilela');
        $solicitante = $this->usuario('Joel Batista');

        $favor = $this->favorComTicket($ajudante);

        \App\Models\Ticket::where('favor_id', $favor->id)
            ->first()
            ->transferirPara($solicitante, 'proposta_aceita');

        $ticket = \App\Models\Ticket::where('proprietario_id', $solicitante->id)->first();

        $solicitacao = (new SolicitarFavor())->executar(
            $ticket,
            $solicitante,
            now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );

        return (new ResponderSolicitacao())->agendar($solicitacao, $ajudante);
    }

    public function test_o_contato_so_aparece_depois_do_agendamento(): void
    {
        $solicitacao = $this->atendimentoAgendado();

        // RF-SOL-07 e 02 §18.1 — telefone e endereço são liberados no
        // agendamento, e só às partes.
        Livewire::actingAs($solicitacao->solicitante)
            ->test(Atendimento::class, ['solicitacao' => $solicitacao])
            ->assertSee('Contato liberado')
            ->assertSee('Ligar para Marta Vilela');
    }

    public function test_quem_nao_e_parte_nao_ve_o_atendimento(): void
    {
        $solicitacao = $this->atendimentoAgendado();

        $this->actingAs($this->usuario('Ana Prado'))
            ->get(route('atendimento', $solicitacao))
            ->assertForbidden();
    }

    public function test_a_emergencia_so_aparece_entre_o_inicio_e_o_fim(): void
    {
        $solicitacao = $this->atendimentoAgendado();
        $registro = new RegistrarComparecimento();

        $tela = Livewire::actingAs($solicitacao->solicitante)
            ->test(Atendimento::class, ['solicitacao' => $solicitacao]);

        // RF-SEG-08 — a janela do acionamento é a do atendimento em curso.
        $tela->assertDontSee('Ligar para a emergência');

        $registro->inicio($solicitacao, $solicitacao->solicitante);

        Livewire::actingAs($solicitacao->solicitante)
            ->test(Atendimento::class, ['solicitacao' => $solicitacao->fresh()])
            ->assertSee('Ligar para a emergência');

        $registro->fim($solicitacao, $solicitacao->solicitante);

        Livewire::actingAs($solicitacao->solicitante)
            ->test(Atendimento::class, ['solicitacao' => $solicitacao->fresh()])
            ->assertDontSee('Ligar para a emergência');
    }

    public function test_o_cancelamento_avisa_da_ocorrencia_antes_de_confirmar(): void
    {
        $solicitacao = $this->atendimentoAgendado();

        // RF-CAN-16 — quem cancela precisa saber o que vai acontecer antes de
        // decidir. Aqui é o ajudante, que gera ocorrência em qualquer prazo.
        Livewire::actingAs($solicitacao->ajudante)
            ->test(CancelarAtendimento::class, ['solicitacao' => $solicitacao])
            ->call('abrir')
            ->assertSee('registra uma ocorrência no seu histórico');
    }

    public function test_a_avaliacao_avisa_da_imutabilidade_antes_do_envio(): void
    {
        $solicitacao = $this->realizado();

        Livewire::actingAs($solicitacao->solicitante)
            ->test(Avaliar::class, ['solicitacao' => $solicitacao])
            // 02 §11.1 — dito antes, não depois.
            ->assertSee('não pode ser alterada')
            // RF-AVA-03 — quem recebeu avalia estes três critérios.
            ->assertSee('Comunicação')
            ->assertSee('Qualidade')
            ->assertSee('Satisfação');
    }

    public function test_a_avaliacao_do_ajudante_exige_motivo_de_qualidade(): void
    {
        $solicitacao = $this->realizado();

        Livewire::actingAs($solicitacao->solicitante)
            ->test(Avaliar::class, ['solicitacao' => $solicitacao])
            ->set('notas', ['comunicacao' => 6, 'qualidade' => 3, 'satisfacao' => 5])
            ->set('motivos', [])
            ->call('enviar')
            // RF-AVA-05
            ->assertHasErrors('avaliacao');
    }

    public function test_a_avaliacao_completa_e_registrada(): void
    {
        $solicitacao = $this->realizado();

        Livewire::actingAs($solicitacao->solicitante)
            ->test(Avaliar::class, ['solicitacao' => $solicitacao])
            ->set('notas', ['comunicacao' => 6, 'qualidade' => 7, 'satisfacao' => 6])
            ->set('motivos', ['acabamento'])
            ->call('enviar')
            ->assertRedirect(route('carteira'));

        $this->assertSame(1, $solicitacao->avaliacoes()->count());
    }

    /** Leva um atendimento até `REALIZADA`, quando a avaliação é liberada. */
    private function realizado(): Solicitacao
    {
        $solicitacao = $this->atendimentoAgendado();
        $apurar = new ApurarRealizacao();

        $solicitacao = $apurar->abrirJanela(
            $solicitacao,
            $solicitacao->fim_previsto->copy()->addMinute(),
        );

        $momento = $solicitacao->fim_previsto->copy()->addHour();

        $apurar->manifestar($solicitacao, $solicitacao->ajudante, ManifestacaoRealizacao::CONFIRMA, $momento);

        return $apurar->manifestar(
            $solicitacao->fresh(),
            $solicitacao->solicitante,
            ManifestacaoRealizacao::CONFIRMA,
            $momento,
        );
    }
}
