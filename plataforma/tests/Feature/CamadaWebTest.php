<?php

namespace Tests\Feature;

use App\Livewire\Cadastrar;
use App\Livewire\Carteira;
use App\Livewire\Entrar;
use App\Livewire\Inicio;
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
}
