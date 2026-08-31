<?php

namespace Tests\Feature;

use App\Dominio\Identidade\RedefinirSenha;
use App\Dominio\Identidade\SolicitarRecuperacaoSenha;
use App\Dominio\Saida\Canais\CanalDeMentira;
use App\Dominio\Saida\Canais\CanalRegistrado;
use App\Dominio\Saida\CanalDeSaida;
use App\Dominio\Saida\Despachar;
use App\Livewire\EscolherSenha;
use App\Livewire\EsqueciSenha;
use App\Models\Envio;
use App\Models\ParametroNegocio;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

/**
 * Recuperação de senha (RF-CAD-08).
 *
 * O que estes testes mais protegem não é o caminho feliz: é o **silêncio**.
 * Um formulário que responde diferente para e-mail que existe e e-mail que
 * não existe é consulta de cadastro disfarçada (NF-SEG-05), e quem varre
 * e-mails agradece.
 */
class RecuperacaoSenhaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametrosNegocioSeeder::class);

        $this->usuario = Usuario::create([
            'nome' => 'Marta Vilela',
            'email' => 'marta@exemplo.br',
            'telefone' => '11988420000',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'senha' => 'segredo123',
        ]);
    }

    private function solicitar(?CanalDeSaida $canal = null): SolicitarRecuperacaoSenha
    {
        return new SolicitarRecuperacaoSenha(new Despachar($canal ?? new CanalRegistrado()));
    }

    private function tokenDaMensagem(): string
    {
        preg_match('#/senha-nova/([A-Za-z0-9]+)#', Envio::latest('id')->sole()->corpo, $achado);

        return $achado[1];
    }

    // --------------------------------------------------------- o silêncio

    public function test_email_desconhecido_nao_gera_mensagem_nem_erro(): void
    {
        $this->solicitar()->executar('ninguem@exemplo.br');

        // Sem exceção e sem mensagem: por fora, indistinguível de um pedido
        // que deu certo.
        $this->assertSame(0, Envio::count());
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function test_conta_desativada_tambem_fica_em_silencio(): void
    {
        $this->usuario->forceFill(['estado_conta' => Usuario::ESTADO_DESATIVADA])->save();

        $this->solicitar()->executar($this->usuario->email);

        $this->assertSame(0, Envio::count());
    }

    public function test_conta_encerrando_ainda_recupera_a_senha(): void
    {
        // Quem está encerrando continua obrigado a atender o que emitiu
        // (RF-CAD-14). Sem acesso à conta, não consegue.
        $this->usuario->forceFill(['estado_conta' => Usuario::ESTADO_ENCERRANDO])->save();

        $this->solicitar()->executar($this->usuario->email);

        $this->assertSame(1, Envio::count());
    }

    public function test_pedido_repetido_cedo_demais_nao_manda_de_novo_nem_reclama(): void
    {
        $solicitar = $this->solicitar();

        $solicitar->executar($this->usuario->email);
        $solicitar->executar($this->usuario->email);

        // Avisar "espere um minuto" contaria que a conta existe. O preço de
        // não avisar é o pedido silencioso; vale a pena.
        $this->assertSame(1, Envio::count());
    }

    public function test_passado_o_intervalo_o_pedido_vale_de_novo(): void
    {
        $solicitar = $this->solicitar();
        $agora = Carbon::now();

        $solicitar->executar($this->usuario->email, $agora);
        $solicitar->executar($this->usuario->email, $agora->copy()->addMinutes(5));

        $this->assertSame(2, Envio::count());
        $this->assertSame(1, DB::table('password_reset_tokens')->count());
    }

    // ------------------------------------------------------------ o token

    public function test_o_token_nao_fica_em_texto_puro_no_banco(): void
    {
        $this->solicitar()->executar($this->usuario->email);

        $token = $this->tokenDaMensagem();
        $guardado = DB::table('password_reset_tokens')->value('token');

        // Quem tiver o token entra na conta.
        $this->assertNotSame($token, $guardado);
        $this->assertTrue(Hash::check($token, $guardado));
    }

    public function test_o_link_certo_troca_a_senha(): void
    {
        $this->solicitar()->executar($this->usuario->email);

        (new RedefinirSenha())->executar(
            $this->usuario->email,
            $this->tokenDaMensagem(),
            'outrasenha456',
        );

        $this->assertTrue(Hash::check('outrasenha456', $this->usuario->fresh()->senha));
    }

    public function test_trocar_a_senha_derruba_as_sessoes_abertas(): void
    {
        $antes = $this->usuario->fresh()->remember_token;
        $this->solicitar()->executar($this->usuario->email);

        (new RedefinirSenha())->executar(
            $this->usuario->email,
            $this->tokenDaMensagem(),
            'outrasenha456',
        );

        // Se a conta foi tomada, é isto que expulsa quem a tomou.
        $this->assertNotSame($antes, $this->usuario->fresh()->remember_token);
    }

    public function test_o_link_serve_uma_vez_so(): void
    {
        $this->solicitar()->executar($this->usuario->email);
        $token = $this->tokenDaMensagem();

        (new RedefinirSenha())->executar($this->usuario->email, $token, 'outrasenha456');

        $this->expectException(LogicException::class);

        (new RedefinirSenha())->executar($this->usuario->email, $token, 'maisoutra789');
    }

    public function test_link_vencido_nao_serve(): void
    {
        $agora = Carbon::now();
        $validade = ParametroNegocio::inteiroEm(ParametroNegocio::RECUPERACAO_VALIDADE_MINUTOS);

        $this->solicitar()->executar($this->usuario->email, $agora);
        $token = $this->tokenDaMensagem();

        try {
            (new RedefinirSenha())->executar(
                $this->usuario->email,
                $token,
                'outrasenha456',
                $agora->copy()->addMinutes($validade + 1),
            );
            $this->fail('Era para recusar o link vencido.');
        } catch (LogicException) {
            // Credencial morta não fica guardada.
        }

        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    /**
     * A recusa é sempre a mesma frase. Distinguir "token errado" de "não
     * existe" contaria a quem testa links o que ele precisa para continuar.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function linksRuins(): array
    {
        return [
            'token errado' => ['marta@exemplo.br', 'token-que-nao-e-o-certo'],
            'e-mail sem pedido' => ['ninguem@exemplo.br', 'qualquer-token'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('linksRuins')]
    public function test_toda_recusa_diz_a_mesma_coisa(string $email, string $token): void
    {
        $this->solicitar()->executar($this->usuario->email);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Link inválido ou vencido. Peça a recuperação outra vez.');

        (new RedefinirSenha())->executar($email, $token, 'outrasenha456');
    }

    // ------------------------------------------------------------- telas

    public function test_a_tela_responde_igual_para_email_que_existe_e_que_nao_existe(): void
    {
        foreach (['marta@exemplo.br', 'ninguem@exemplo.br'] as $email) {
            Livewire::test(EsqueciSenha::class)
                ->set('email', $email)
                ->call('pedir')
                ->assertHasNoErrors()
                ->assertSet('pedido', true)
                ->assertSee('Se houver conta com esse e-mail');
        }
    }

    public function test_a_tela_recusa_email_malformado_sem_ir_ao_dominio(): void
    {
        Livewire::test(EsqueciSenha::class)
            ->set('email', 'nao-e-email')
            ->call('pedir')
            ->assertHasErrors(['email' => 'email']);

        $this->assertSame(0, Envio::count());
    }

    public function test_a_tela_de_senha_nova_exige_confirmacao_igual(): void
    {
        Livewire::test(EscolherSenha::class, ['token' => 'x', 'email' => $this->usuario->email])
            ->set('senha', 'outrasenha456')
            ->set('confirmacao', 'diferente456')
            ->call('redefinir')
            ->assertHasErrors(['confirmacao' => 'same']);
    }

    public function test_a_tela_de_senha_nova_troca_e_manda_entrar(): void
    {
        $this->solicitar()->executar($this->usuario->email);

        Livewire::test(EscolherSenha::class, [
            'token' => $this->tokenDaMensagem(),
            'email' => $this->usuario->email,
        ])
            ->set('senha', 'outrasenha456')
            ->set('confirmacao', 'outrasenha456')
            ->call('redefinir')
            ->assertHasNoErrors()
            ->assertRedirect(route('entrar'));

        $this->assertTrue(Hash::check('outrasenha456', $this->usuario->fresh()->senha));
    }

    public function test_as_telas_de_recuperacao_sao_publicas(): void
    {
        $this->get(route('esqueci-senha'))->assertOk();
        $this->get(route('redefinir-senha', ['token' => 'abc']))->assertOk();

        // E quem já está dentro não precisa delas.
        $this->actingAs($this->usuario)->get(route('esqueci-senha'))->assertRedirect();
    }

    public function test_a_tela_de_entrar_oferece_o_caminho(): void
    {
        $this->get(route('entrar'))
            ->assertOk()
            ->assertSee('Esqueci a senha');
    }

    public function test_com_provedor_a_mensagem_e_entregue(): void
    {
        $canal = new CanalDeMentira();

        $this->solicitar($canal)->executar($this->usuario->email);

        $this->assertSame(Envio::ENTREGUE, Envio::sole()->estado);
        $this->assertTrue($canal->entregouPara('marta@exemplo.br'));
    }
}
