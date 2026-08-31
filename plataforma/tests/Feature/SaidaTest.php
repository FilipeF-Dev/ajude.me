<?php

namespace Tests\Feature;

use App\Dominio\Saida\Canais\CanalDeMentira;
use App\Dominio\Saida\Canais\CanalRegistrado;
use App\Dominio\Saida\CanalDeSaida;
use App\Dominio\Saida\Despachar;
use App\Dominio\Saida\Mensagem;
use App\Models\Envio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

/**
 * A camada de saída (05 §10, §15.1).
 *
 * Existe antes do provedor para que verificação de contato e recuperação de
 * senha possam ser construídas e testadas agora, e a contratação vire uma
 * linha de ambiente em vez de uma reescrita.
 *
 * O que estes testes protegem, acima de tudo: **`registrado` nunca vira
 * `entregue` sozinho.** Um canal de mentira que se declarasse entregue faria
 * o painel afirmar que alguém recebeu um código que nunca saiu daqui.
 */
class SaidaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): Usuario
    {
        return Usuario::create([
            'nome' => 'Marta Vilela',
            'email' => uniqid('pessoa').'@exemplo.br',
            'telefone' => '11988420000',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'senha' => 'segredo123',
        ]);
    }

    private function despachante(?CanalDeSaida $canal = null): Despachar
    {
        return new Despachar($canal ?? new CanalRegistrado());
    }

    // ------------------------------------------------- o canal de mentira

    public function test_o_canal_sem_provedor_registra_e_nao_declara_entrega(): void
    {
        $envio = $this->despachante()->executar(
            Mensagem::sms('11988420000', 'verificacao_contato', 'Seu código é 418293.')
        );

        $this->assertSame(Envio::REGISTRADO, $envio->estado);
        $this->assertNull($envio->entregue_em);
        $this->assertFalse($envio->saiuDaPlataforma());
    }

    public function test_o_que_ficou_registrado_aparece_como_nao_entregue(): void
    {
        $this->despachante()->executar(
            Mensagem::sms('11988420000', 'verificacao_contato', 'Seu código é 418293.')
        );

        // É por aqui que a Operação enxerga, durante o piloto, que nada saiu.
        $this->assertSame(1, Envio::naoEntregues()->count());
        $this->assertSame(0, Envio::where('estado', Envio::ENTREGUE)->count());
    }

    // ------------------------------------------------------ provedor real

    public function test_com_provedor_a_mensagem_e_marcada_entregue(): void
    {
        $canal = new CanalDeMentira();

        $envio = $this->despachante($canal)->executar(
            Mensagem::email('marta@exemplo.br', 'recuperacao_senha', 'Recuperar acesso', 'Link.')
        );

        $this->assertSame(Envio::ENTREGUE, $envio->estado);
        $this->assertNotNull($envio->entregue_em);
        $this->assertTrue($canal->entregouPara('marta@exemplo.br'));
    }

    public function test_falha_de_entrega_nao_derruba_quem_chamou(): void
    {
        $canal = new CanalDeMentira(falhaSempre: true, motivoDaFalha: 'Operadora recusou.');

        // Ninguém deve perder um cadastro porque o SMS não saiu.
        $envio = $this->despachante($canal)->executar(
            Mensagem::sms('11988420000', 'verificacao_contato', 'Seu código é 418293.')
        );

        $this->assertSame(Envio::FALHOU, $envio->estado);
        $this->assertSame('Operadora recusou.', $envio->erro);
        $this->assertSame(1, $envio->tentativas);
    }

    public function test_falha_fica_visivel_para_ser_escalada(): void
    {
        $canal = new CanalDeMentira(falhaSempre: true);

        $this->despachante($canal)->executar(
            Mensagem::sms('11977770000', 'aviso_contato_confianca', 'Atendimento marcado.')
        );

        // NF-DIS-03 — falha do canal do contato de confiança é incidente,
        // não linha de log que ninguém lê.
        $this->assertSame(1, Envio::falhados()->count());
    }

    // ------------------------------------------------------ idempotência

    public function test_a_mesma_mensagem_nao_sai_duas_vezes(): void
    {
        $canal = new CanalDeMentira();
        $despachante = $this->despachante($canal);

        $mensagem = fn () => Mensagem::sms('11988420000', 'verificacao_contato', 'Seu código é 418293.');

        $primeiro = $despachante->executar($mensagem());
        $segundo = $despachante->executar($mensagem());

        // NF-DIS-04. Vale para o retentar de uma fila e para o duplo clique.
        $this->assertSame($primeiro->id, $segundo->id);
        $this->assertSame(1, Envio::count());
        $this->assertCount(1, $canal->entregues);
    }

    public function test_codigo_novo_e_mensagem_nova(): void
    {
        $despachante = $this->despachante(new CanalDeMentira());

        $despachante->executar(Mensagem::sms('11988420000', 'verificacao_contato', 'Seu código é 418293.'));
        $despachante->executar(Mensagem::sms('11988420000', 'verificacao_contato', 'Seu código é 771044.'));

        // Reenviar não é o mesmo que pedir outro código.
        $this->assertSame(2, Envio::count());
    }

    public function test_chave_explicita_manda_mais_que_o_conteudo(): void
    {
        $despachante = $this->despachante(new CanalDeMentira());

        $despachante->executar(Mensagem::sms(
            '11988420000', 'aviso_contato_confianca', 'Primeiro texto.',
            chaveIdempotencia: 'aviso-solicitacao-42',
        ));
        $despachante->executar(Mensagem::sms(
            '11988420000', 'aviso_contato_confianca', 'Texto reescrito depois.',
            chaveIdempotencia: 'aviso-solicitacao-42',
        ));

        // Quando o chamador sabe que é o mesmo fato, o texto pode mudar sem
        // virar mensagem nova.
        $this->assertSame(1, Envio::count());
    }

    // ------------------------------------------------------ a mensagem

    public function test_a_mensagem_guarda_a_que_se_refere(): void
    {
        $usuario = $this->usuario();

        $envio = $this->despachante()->executar(
            Mensagem::email($usuario->email, 'verificacao_contato', 'Confirme seu e-mail', 'Código.', $usuario)
        );

        $this->assertSame(Usuario::class, $envio->referencia_tipo);
        $this->assertSame($usuario->id, $envio->referencia_id);
    }

    public function test_sms_nao_tem_assunto(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/SMS não tem assunto/');

        new Mensagem(Envio::SMS, '11988420000', 'teste', 'corpo', assunto: 'Oi');
    }

    public function test_mensagem_sem_destino_e_recusada(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/sem destino/');

        Mensagem::sms('   ', 'teste', 'corpo');
    }

    public function test_canal_desconhecido_e_recusado(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Canal desconhecido/');

        new Mensagem('pombo-correio', 'algum lugar', 'teste', 'corpo');
    }

    // ------------------------------------------------------ configuração

    public function test_o_canal_vem_da_configuracao(): void
    {
        // Sem provedor contratado, o padrão é o que não entrega.
        $this->assertInstanceOf(CanalRegistrado::class, app(CanalDeSaida::class));
    }

    public function test_configuracao_invalida_falha_alto(): void
    {
        config(['saida.canal' => 'fumaca']);
        app()->forgetInstance(CanalDeSaida::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/não está configurado/');

        app(CanalDeSaida::class);
    }

    public function test_o_despacho_registra_o_momento_do_evento(): void
    {
        // Sem microssegundos de propósito: a coluna guarda até o segundo, e
        // comparar o que não é armazenado testaria o Carbon, não o despacho.
        $momento = Carbon::now()->subHours(3)->startOfSecond();

        $envio = $this->despachante(new CanalDeMentira())->executar(
            Mensagem::sms('11988420000', 'verificacao_contato', 'Seu código é 418293.'),
            $momento,
        );

        // O momento é sempre parâmetro: rotina que roda depois não deve
        // gravar a hora em que rodou.
        $this->assertTrue($envio->despachado_em->equalTo($momento));
        $this->assertTrue($envio->entregue_em->equalTo($momento));
    }
}
