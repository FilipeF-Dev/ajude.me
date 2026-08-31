<?php

namespace Tests\Feature;

use App\Dominio\Identidade\ConfirmarVerificacao;
use App\Dominio\Identidade\SolicitarVerificacao;
use App\Dominio\Saida\Canais\CanalDeMentira;
use App\Dominio\Saida\Canais\CanalRegistrado;
use App\Dominio\Saida\CanalDeSaida;
use App\Dominio\Saida\Despachar;
use App\Models\Envio;
use App\Models\ParametroNegocio;
use App\Models\Usuario;
use App\Models\VerificacaoContato;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

/**
 * Verificação de contato (RF-CAD-04 a RF-CAD-06).
 *
 * O código é credencial de uso único: guardado cifrado, com validade curta e
 * limite de erros. E o selo é derivado — nunca armazenado.
 */
class VerificacaoContatoTest extends TestCase
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

    private function solicitar(?CanalDeSaida $canal = null): SolicitarVerificacao
    {
        return new SolicitarVerificacao(new Despachar($canal ?? new CanalRegistrado()));
    }

    /** O código só é legível pela mensagem — no banco ele está cifrado. */
    private function codigoDe(Envio $envio): string
    {
        preg_match('/(\d{6})/', $envio->corpo, $achado);

        return $achado[1];
    }

    // ------------------------------------------------------- emissão

    public function test_pedir_codigo_gera_verificacao_e_mensagem(): void
    {
        $verificacao = $this->solicitar()->executar(
            $this->usuario,
            VerificacaoContato::TIPO_TELEFONE,
        );

        $this->assertFalse($verificacao->estaVerificado());
        $this->assertSame('11988420000', $verificacao->valor);

        $envio = Envio::sole();
        $this->assertSame(Envio::SMS, $envio->canal);
        $this->assertSame('11988420000', $envio->destino);
        $this->assertMatchesRegularExpression('/\d{6}/', $envio->corpo);
    }

    public function test_o_codigo_nao_fica_em_texto_puro_no_banco(): void
    {
        $this->solicitar()->executar($this->usuario, VerificacaoContato::TIPO_EMAIL);

        $codigo = $this->codigoDe(Envio::sole());
        $guardado = VerificacaoContato::sole()->codigo;

        // Credencial de uso único: quem a tiver confirma contato alheio.
        $this->assertNotSame($codigo, $guardado);
        $this->assertGreaterThan(20, strlen((string) $guardado));
    }

    public function test_sem_provedor_o_codigo_nao_sai_da_plataforma(): void
    {
        $this->solicitar()->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE);

        // É assim que se completa um cadastro hoje — e o painel diz, sem
        // rodeio, que a mensagem não chegou a ninguém.
        $this->assertSame(Envio::REGISTRADO, Envio::sole()->estado);
    }

    public function test_com_provedor_a_mensagem_e_entregue(): void
    {
        $canal = new CanalDeMentira();

        $this->solicitar($canal)->executar($this->usuario, VerificacaoContato::TIPO_EMAIL);

        $this->assertSame(Envio::ENTREGUE, Envio::sole()->estado);
        $this->assertTrue($canal->entregouPara('marta@exemplo.br'));
    }

    public function test_pedir_codigo_de_novo_cedo_demais_e_recusado(): void
    {
        $solicitar = $this->solicitar();
        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE);

        // Cada pedido custa uma mensagem, e mensagem custa dinheiro. É o
        // gatilho de abuso mais barato que existe (NF-SEG-06).
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Espere \d+ segundos/');

        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE);
    }

    public function test_passado_o_intervalo_o_reenvio_e_permitido(): void
    {
        $solicitar = $this->solicitar();
        $agora = Carbon::now();

        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE, $agora);
        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE, $agora->copy()->addMinutes(2));

        $this->assertSame(2, Envio::count());
        $this->assertSame(1, VerificacaoContato::count());
    }

    public function test_contato_ja_verificado_nao_pede_codigo(): void
    {
        $canal = new CanalDeMentira();
        $solicitar = $this->solicitar($canal);
        $agora = Carbon::now();

        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_EMAIL, $agora);
        (new ConfirmarVerificacao())->executar(
            $this->usuario,
            VerificacaoContato::TIPO_EMAIL,
            $this->codigoDe(Envio::sole()),
            $agora,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/já está verificado/');

        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_EMAIL, $agora->copy()->addHour());
    }

    public function test_tipo_de_contato_inventado_e_recusado(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Tipo de verificação desconhecido/');

        $this->solicitar()->executar($this->usuario, 'pombo');
    }

    // ---------------------------------------------------- confirmação

    public function test_o_codigo_certo_verifica_o_contato(): void
    {
        $agora = Carbon::now();
        $this->solicitar()->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE, $agora);

        $verificacao = (new ConfirmarVerificacao())->executar(
            $this->usuario,
            VerificacaoContato::TIPO_TELEFONE,
            $this->codigoDe(Envio::sole()),
            $agora,
        );

        $this->assertTrue($verificacao->estaVerificado());

        // Credencial usada não precisa continuar guardada.
        $this->assertNull($verificacao->codigo);
        $this->assertNull($verificacao->codigo_expira_em);
    }

    public function test_o_codigo_errado_conta_tentativa_e_a_contagem_persiste(): void
    {
        $this->solicitar()->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE);

        try {
            (new ConfirmarVerificacao())->executar(
                $this->usuario,
                VerificacaoContato::TIPO_TELEFONE,
                '000000',
            );
            $this->fail('Era para recusar o código errado.');
        } catch (LogicException) {
            // A transação devolve o desfecho e a exceção sobe depois: se ela
            // subisse de dentro, o incremento seria desfeito no rollback e o
            // limite não limitaria nada.
        }

        $this->assertSame(1, VerificacaoContato::sole()->tentativas);
    }

    public function test_erros_demais_travam_a_verificacao(): void
    {
        $maximo = ParametroNegocio::inteiroEm(ParametroNegocio::VERIFICACAO_TENTATIVAS_MAXIMAS);
        $confirmar = new ConfirmarVerificacao();

        $this->solicitar()->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE);
        $certo = $this->codigoDe(Envio::sole());

        for ($i = 0; $i < $maximo; $i++) {
            try {
                $confirmar->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE, '000000');
            } catch (LogicException) {
                // esperado
            }
        }

        // Seis dígitos são adivinháveis sem limite de erros. Nem o código
        // certo destrava depois disso: tem de pedir outro.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Muitas tentativas/');

        $confirmar->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE, $certo);
    }

    public function test_pedir_codigo_novo_zera_as_tentativas(): void
    {
        $solicitar = $this->solicitar();
        $confirmar = new ConfirmarVerificacao();
        $agora = Carbon::now();

        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE, $agora);

        try {
            $confirmar->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE, '000000', $agora);
        } catch (LogicException) {
            // esperado
        }

        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE, $agora->copy()->addMinutes(2));

        // Código novo, contagem nova — senão um erro antigo travaria a
        // verificação seguinte sem motivo.
        $this->assertSame(0, VerificacaoContato::sole()->tentativas);
    }

    public function test_codigo_vencido_nao_serve(): void
    {
        $agora = Carbon::now();
        $validade = ParametroNegocio::inteiroEm(ParametroNegocio::VERIFICACAO_VALIDADE_MINUTOS);

        $this->solicitar()->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE, $agora);
        $codigo = $this->codigoDe(Envio::sole());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/venceu/');

        (new ConfirmarVerificacao())->executar(
            $this->usuario,
            VerificacaoContato::TIPO_TELEFONE,
            $codigo,
            $agora->copy()->addMinutes($validade + 1),
        );
    }

    public function test_confirmar_sem_ter_pedido_e_recusado(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Peça um código antes/');

        (new ConfirmarVerificacao())->executar(
            $this->usuario,
            VerificacaoContato::TIPO_EMAIL,
            '123456',
        );
    }

    public function test_o_codigo_de_um_contato_nao_serve_para_o_outro(): void
    {
        $agora = Carbon::now();
        $solicitar = $this->solicitar();

        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_EMAIL, $agora);
        $codigoDoEmail = $this->codigoDe(Envio::sole());

        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE, $agora);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/incorreto/');

        (new ConfirmarVerificacao())->executar(
            $this->usuario,
            VerificacaoContato::TIPO_TELEFONE,
            $codigoDoEmail,
            $agora,
        );
    }

    // ------------------------------------------------------------ selo

    public function test_o_selo_so_aparece_com_os_dois_contatos_verificados(): void
    {
        $agora = Carbon::now();
        $solicitar = $this->solicitar();
        $confirmar = new ConfirmarVerificacao();

        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_EMAIL, $agora);
        $confirmar->executar(
            $this->usuario,
            VerificacaoContato::TIPO_EMAIL,
            $this->codigoDe(Envio::latest('id')->sole()),
            $agora,
        );

        $this->assertFalse($this->usuario->fresh()->contatoVerificado());

        $solicitar->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE, $agora);
        $confirmar->executar(
            $this->usuario,
            VerificacaoContato::TIPO_TELEFONE,
            $this->codigoDe(Envio::latest('id')->first()),
            $agora,
        );

        // RF-CAD-06 — e é "Contato verificado", não "Identidade verificada":
        // sem KYC documental, o selo mais forte seria mentira (03 D1).
        $this->assertTrue($this->usuario->fresh()->contatoVerificado());
    }

    // ------------------------------------------------------------ tela

    public function test_a_tela_pede_e_confirma_o_codigo(): void
    {
        \Livewire\Livewire::actingAs($this->usuario)
            ->test(\App\Livewire\VerificarContato::class)
            ->set('tipo', VerificacaoContato::TIPO_TELEFONE)
            ->call('pedirCodigo')
            ->assertHasNoErrors();

        $codigo = $this->codigoDe(Envio::latest('id')->first());

        \Livewire\Livewire::actingAs($this->usuario->fresh())
            ->test(\App\Livewire\VerificarContato::class)
            ->set('tipo', VerificacaoContato::TIPO_TELEFONE)
            ->set('codigo', $codigo)
            ->call('confirmar')
            ->assertHasNoErrors();

        $this->assertTrue(
            VerificacaoContato::sole()->estaVerificado()
        );
    }

    public function test_a_tela_traduz_a_recusa_em_erro_de_formulario(): void
    {
        $this->solicitar()->executar($this->usuario, VerificacaoContato::TIPO_TELEFONE);

        // A tela não reimplementa regra: chama a ação e mostra o que ela diz.
        \Livewire\Livewire::actingAs($this->usuario->fresh())
            ->test(\App\Livewire\VerificarContato::class)
            ->set('tipo', VerificacaoContato::TIPO_TELEFONE)
            ->set('codigo', '000000')
            ->call('confirmar')
            ->assertHasErrors('codigo');
    }

    public function test_a_tela_recusa_codigo_com_tamanho_errado_sem_ir_ao_dominio(): void
    {
        \Livewire\Livewire::actingAs($this->usuario)
            ->test(\App\Livewire\VerificarContato::class)
            ->set('codigo', '123')
            ->call('confirmar')
            ->assertHasErrors(['codigo' => 'digits']);
    }

    public function test_o_perfil_oferece_confirmar_e_depois_mostra_o_selo(): void
    {
        $this->get(route('perfil'))->assertRedirect();

        $this->actingAs($this->usuario)
            ->get(route('perfil'))
            ->assertOk()
            ->assertSee('Confirmar contato')
            ->assertDontSee('Contato verificado');

        $agora = Carbon::now();
        $solicitar = $this->solicitar();
        $confirmar = new ConfirmarVerificacao();

        foreach ([VerificacaoContato::TIPO_EMAIL, VerificacaoContato::TIPO_TELEFONE] as $tipo) {
            $solicitar->executar($this->usuario, $tipo, $agora);
            $confirmar->executar($this->usuario, $tipo, $this->codigoDe(Envio::latest('id')->first()), $agora);
        }

        $this->actingAs($this->usuario->fresh())
            ->get(route('perfil'))
            ->assertOk()
            ->assertSee('Contato verificado');
    }
}
