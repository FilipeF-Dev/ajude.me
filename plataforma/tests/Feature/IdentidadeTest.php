<?php

namespace Tests\Feature;

use App\Models\ContatoConfianca;
use App\Models\Endereco;
use App\Models\Usuario;
use App\Models\VerificacaoContato;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

/**
 * O contexto Identidade (05 §4.1).
 *
 * O cadastro por convite já é coberto por ConvitesTest. Aqui ficam as regras
 * da própria conta: o que não pode mudar, o que a conta pode fazer em cada
 * estado, e o que é derivado em vez de armazenado.
 *
 * NÃO existe teste de encerramento de conta porque **não existe encerramento
 * de conta**: `RF-CAD-11` a `RF-CAD-16` estão no modelo (`podeOfertar`,
 * `deveAtender`, o estado `ENCERRANDO`) mas nenhuma ação da aplicação os
 * aciona. O que dá para proteger hoje são as regras do modelo, e é o que
 * este arquivo faz.
 */
class IdentidadeTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(array $atributos = []): Usuario
    {
        return Usuario::create(array_merge([
            'nome' => 'Marta Vilela',
            'email' => uniqid('pessoa').'@exemplo.br',
            'telefone' => '11988420000',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'senha' => 'segredo123',
        ], $atributos));
    }

    // ---------------------------------------------------------------- CPF

    public function test_o_cpf_nao_muda_depois_do_cadastro(): void
    {
        $usuario = $this->usuario(['cpf' => '39053344705']);

        // RF-CAD-10. A guarda vive no modelo porque um CHECK de banco não
        // distingue inserção de atualização.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não pode ser alterado/');

        $usuario->cpf = '52998224725';
    }

    public function test_o_cpf_e_guardado_so_com_digitos(): void
    {
        $usuario = $this->usuario(['cpf' => '390.533.447-05']);

        $this->assertSame('39053344705', $usuario->fresh()->cpf);
    }

    public function test_reatribuir_o_mesmo_cpf_formatado_nao_e_alteracao(): void
    {
        $usuario = $this->usuario(['cpf' => '39053344705']);

        // Normaliza antes de comparar: reenviar o mesmo número com pontuação
        // é o que um formulário faz o tempo todo, e não é mudança nenhuma.
        $usuario->cpf = '390.533.447-05';

        $this->assertSame('39053344705', $usuario->cpf);
    }

    public function test_o_banco_recusa_cpf_que_nao_seja_onze_digitos(): void
    {
        $this->expectException(QueryException::class);

        Usuario::create([
            'nome' => 'Joel Batista',
            'email' => uniqid('pessoa').'@exemplo.br',
            'telefone' => '11988420000',
            'senha' => 'segredo123',
            'cpf' => '123',
        ]);
    }

    public function test_a_unicidade_do_cpf_alcanca_conta_desativada(): void
    {
        $usuario = $this->usuario(['cpf' => '39053344705']);
        $usuario->forceFill(['estado_conta' => Usuario::ESTADO_DESATIVADA])->save();

        // RF-CAD-03 — uma pessoa, um perfil. Sair e voltar com outro cadastro
        // apagaria o histórico de confiança, que é o oposto do que ele serve.
        $this->expectException(QueryException::class);

        $this->usuario(['cpf' => '39053344705']);
    }

    public function test_o_telefone_e_guardado_so_com_digitos(): void
    {
        $usuario = $this->usuario(['telefone' => '(11) 9 8842-1170']);

        $this->assertSame('11988421170', $usuario->fresh()->telefone);
    }

    // ------------------------------------------------- estados da conta

    public function test_a_conta_nasce_ativa_no_objeto_e_nao_so_no_banco(): void
    {
        $usuario = $this->usuario();

        // O default da coluna não preenche o atributo do modelo recém-criado.
        // Sem espelhá-lo em `$attributes`, quem cadastra e age em seguida é
        // reprovado por uma conta que está ativa. Já apareceu duas vezes.
        $this->assertSame(Usuario::ESTADO_ATIVA, $usuario->estado_conta);
        $this->assertTrue($usuario->podeOfertar());
        $this->assertTrue($usuario->podeNegociar());
    }

    /**
     * RF-CAD-13 e RF-CAD-14 — quem está encerrando não começa nada novo, mas
     * continua obrigado a atender o que já emitiu. O direito de quem tem o
     * ticket não depende da vontade de quem saiu (02 §2.2).
     *
     * @return array<string, array{0: string, 1: bool, 2: bool}>
     */
    public static function estadosDaConta(): array
    {
        return [
            //                              ofertar  atender
            'ativa' => [Usuario::ESTADO_ATIVA, true, true],
            'encerrando' => [Usuario::ESTADO_ENCERRANDO, false, true],
            'desativada' => [Usuario::ESTADO_DESATIVADA, false, false],
            'suspensa' => [Usuario::ESTADO_SUSPENSA, false, false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('estadosDaConta')]
    public function test_cada_estado_define_o_que_a_conta_pode(
        string $estado,
        bool $podeOfertar,
        bool $deveAtender,
    ): void {
        $usuario = $this->usuario();
        $usuario->forceFill(['estado_conta' => $estado])->save();

        $usuario = $usuario->fresh();

        $this->assertSame($podeOfertar, $usuario->podeOfertar());
        $this->assertSame($podeOfertar, $usuario->podeNegociar());
        $this->assertSame($deveAtender, $usuario->deveAtender());
    }

    public function test_o_banco_recusa_estado_de_conta_inventado(): void
    {
        $usuario = $this->usuario();

        $this->expectException(QueryException::class);

        $usuario->forceFill(['estado_conta' => 'ferias'])->save();
    }

    // -------------------------------------------- verificação de contato

    public function test_o_selo_exige_email_e_telefone_verificados(): void
    {
        $usuario = $this->usuario();

        $this->assertFalse($usuario->contatoVerificado());

        VerificacaoContato::create([
            'usuario_id' => $usuario->id,
            'tipo' => VerificacaoContato::TIPO_EMAIL,
            'valor' => $usuario->email,
        ])->forceFill(['verificado_em' => Carbon::now()])->save();

        // Só um dos dois: o selo continua fora. RF-CAD-06 exige os dois.
        $this->assertFalse($usuario->fresh()->contatoVerificado());

        VerificacaoContato::create([
            'usuario_id' => $usuario->id,
            'tipo' => VerificacaoContato::TIPO_TELEFONE,
            'valor' => $usuario->telefone,
        ])->forceFill(['verificado_em' => Carbon::now()])->save();

        $this->assertTrue($usuario->fresh()->contatoVerificado());
    }

    public function test_verificacao_registrada_mas_nao_concluida_nao_vale(): void
    {
        $usuario = $this->usuario();

        foreach ([VerificacaoContato::TIPO_EMAIL, VerificacaoContato::TIPO_TELEFONE] as $tipo) {
            VerificacaoContato::create([
                'usuario_id' => $usuario->id,
                'tipo' => $tipo,
                'valor' => 'algo',
                'codigo' => '123456',
            ]);
        }

        // Código enviado não é contato verificado.
        $this->assertFalse($usuario->fresh()->contatoVerificado());
    }

    public function test_ha_no_maximo_uma_verificacao_por_tipo(): void
    {
        $usuario = $this->usuario();

        VerificacaoContato::create([
            'usuario_id' => $usuario->id,
            'tipo' => VerificacaoContato::TIPO_EMAIL,
            'valor' => $usuario->email,
        ]);

        $this->expectException(QueryException::class);

        VerificacaoContato::create([
            'usuario_id' => $usuario->id,
            'tipo' => VerificacaoContato::TIPO_EMAIL,
            'valor' => 'outro@exemplo.br',
        ]);
    }

    public function test_o_banco_recusa_tipo_de_verificacao_inventado(): void
    {
        $usuario = $this->usuario();

        $this->expectException(QueryException::class);

        VerificacaoContato::create([
            'usuario_id' => $usuario->id,
            'tipo' => 'pombo',
            'valor' => 'algo',
        ]);
    }

    // ------------------------------------------------------- privacidade

    public function test_senha_e_guardada_cifrada_e_nunca_serializada(): void
    {
        $usuario = $this->usuario(['senha' => 'segredo123']);

        $this->assertNotSame('segredo123', $usuario->senha);
        $this->assertTrue(Hash::check('segredo123', $usuario->senha));
        $this->assertArrayNotHasKey('senha', $usuario->toArray());
    }

    public function test_o_cpf_nunca_sai_na_serializacao(): void
    {
        // O CPF serve para impedir duplicidade e identificar, não para
        // circular. Nenhuma resposta deve carregá-lo por descuido.
        $this->assertArrayNotHasKey('cpf', $this->usuario()->toArray());
    }

    public function test_o_endereco_exposto_e_so_a_regiao(): void
    {
        $usuario = $this->usuario();

        $endereco = Endereco::create([
            'usuario_id' => $usuario->id,
            'cep' => '02345678',
            'logradouro' => 'Rua das Acácias',
            'numero' => '214',
            'complemento' => 'fundos',
            'bairro' => 'Jardim das Oliveiras',
            'municipio' => 'São Paulo',
            'uf' => 'SP',
        ]);

        // P7 — antes de um agendamento, ninguém vê onde a pessoa mora.
        $this->assertSame('Jardim das Oliveiras, São Paulo', $endereco->regiaoAproximada());
        $this->assertStringNotContainsString('Acácias', $endereco->regiaoAproximada());
        $this->assertStringNotContainsString('214', $endereco->regiaoAproximada());
    }

    public function test_o_usuario_tem_um_endereco_so(): void
    {
        $usuario = $this->usuario();

        $dados = [
            'usuario_id' => $usuario->id,
            'cep' => '02345678',
            'logradouro' => 'Rua das Acácias',
            'numero' => '214',
            'bairro' => 'Jardim das Oliveiras',
            'municipio' => 'São Paulo',
            'uf' => 'SP',
        ];

        Endereco::create($dados);

        $this->expectException(QueryException::class);

        Endereco::create($dados);
    }

    // ------------------------------------------------ contato de confiança

    public function test_o_contato_de_confianca_nasce_precisando_de_apresentacao(): void
    {
        $contato = ContatoConfianca::create([
            'usuario_id' => $this->usuario()->id,
            'nome' => 'Ana Paula',
            'telefone' => '11977770000',
            'permissao_declarada_em' => Carbon::now(),
        ]);

        // A pessoa não é usuária e nunca aceitou termo algum (05 §14.3): o
        // primeiro aviso precisa explicar o que é isto e como sair.
        $this->assertTrue($contato->precisaDeApresentacao());
        $this->assertTrue($contato->podeReceberAviso());
    }

    public function test_a_recusa_do_contato_de_confianca_desliga_o_aviso(): void
    {
        $contato = ContatoConfianca::create([
            'usuario_id' => $this->usuario()->id,
            'nome' => 'Ana Paula',
            'telefone' => '11977770000',
            'permissao_declarada_em' => Carbon::now(),
        ]);

        $contato->forceFill([
            'avisado_em' => Carbon::now(),
            'recusado_em' => Carbon::now(),
        ])->save();

        $this->assertFalse($contato->fresh()->podeReceberAviso());
    }

    // ---------------------------------------------------------- carteira

    public function test_a_carteira_e_lista_de_direitos_e_nao_saldo(): void
    {
        $usuario = $this->usuario();

        $this->assertCount(0, $usuario->carteira);

        // P8 — a garantia é estrutural: não existe campo de saldo para ler.
        $colunas = array_keys($usuario->fresh()->getAttributes());

        foreach (['saldo', 'creditos', 'credito', 'valor', 'pontos'] as $proibido) {
            $this->assertNotContains($proibido, $colunas);
        }
    }
}
