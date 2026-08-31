<?php

namespace Tests\Feature;

use App\Dominio\Identidade\GerirConvites;
use App\Filament\Resources\Convites\Pages\ListConvites;
use App\Livewire\Cadastrar;
use App\Models\Categoria;
use App\Models\Convite;
use App\Models\Operador;
use App\Models\ParametroNegocio;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\RegistroAuditoria;
use App\Models\Subcategoria;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

/**
 * Convites de acesso da base restrita (RF-ADM-08, 06 §5.2).
 *
 * O convite é pré-requisito do piloto fechado, e serve a dois propósitos que
 * se reforçam: segurança, porque o convívio regula onde a curadoria não
 * alcança; e liquidez, porque convidar concentra em vez de espalhar.
 */
class ConvitesTest extends TestCase
{
    use RefreshDatabase;

    private Operador $operador;

    private Proficiencia $proficiencia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametrosNegocioSeeder::class);

        $this->operador = Operador::create([
            'nome' => 'Operação',
            'email' => 'operacao@ajude.me',
            'senha' => 'segredo123',
        ]);

        $categoria = Categoria::create(['nome' => 'Casa e reparos']);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'nome' => 'Reparos']);
        $this->proficiencia = Proficiencia::create([
            'subcategoria_id' => $subcategoria->id,
            'nome' => 'Elétrica residencial',
        ]);
    }

    private function convite(): Convite
    {
        return app(GerirConvites::class)->emitir($this->operador)->first();
    }

    /** @return array<string, mixed> */
    private function dadosDeCadastro(string $codigo = ''): array
    {
        return [
            'codigoConvite' => $codigo,
            'nome' => 'Ana Prado',
            'email' => 'ana@exemplo.br',
            'telefone' => '11988420000',
            'cpf' => '529.982.247-25',
            'senha' => 'segredo123',
            'senha_confirmation' => 'segredo123',
            'proficienciaId' => $this->proficiencia->id,
            'nivel' => ProficienciaUsuario::NIVEL_INTERMEDIARIO,
        ];
    }

    private function cadastrarCom(array $dados)
    {
        $tela = Livewire::test(Cadastrar::class);

        foreach ($dados as $campo => $valor) {
            $tela->set($campo, $valor);
        }

        return $tela->call('cadastrar');
    }

    // ------------------------------------------------------------- Emissão

    public function test_o_codigo_evita_caracteres_que_se_confundem(): void
    {
        $codigos = app(GerirConvites::class)->emitir($this->operador, 20)->pluck('codigo');

        foreach ($codigos as $codigo) {
            $this->assertStringStartsWith('AJUD-', $codigo);
            // Um convite mal digitado é um convite perdido.
            $this->assertDoesNotMatchRegularExpression('/[OIL0125SZ]/', substr($codigo, 5));
        }

        $this->assertCount(20, $codigos->unique());
    }

    public function test_a_emissao_em_lote_entra_na_trilha(): void
    {
        app(GerirConvites::class)->emitir($this->operador, 5, 'Grupo do bairro');

        $this->assertSame(5, Convite::count());

        // RF-ADM-10 — quem abriu a porta, e quando.
        $registro = RegistroAuditoria::where('acao', 'convites_emitidos')->first();

        $this->assertSame($this->operador->id, $registro->operador_id);
        $this->assertStringContainsString('Grupo do bairro', $registro->motivo);
    }

    public function test_recusa_lote_fora_do_intervalo(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/entre 1 e 100/');

        app(GerirConvites::class)->emitir($this->operador, 500);
    }

    // ------------------------------------------------------------- Cadastro

    public function test_o_cadastro_exige_convite_na_base_restrita(): void
    {
        $this->cadastrarCom($this->dadosDeCadastro())
            ->assertHasErrors(['codigoConvite']);

        $this->assertSame(0, Usuario::count());
    }

    public function test_o_cadastro_com_convite_valido_cria_a_conta(): void
    {
        $convite = $this->convite();

        $this->cadastrarCom($this->dadosDeCadastro($convite->codigo))
            ->assertRedirect(route('inicio'));

        $usuario = Usuario::firstOrFail();

        $this->assertAuthenticatedAs($usuario);
        $this->assertSame($usuario->id, $convite->fresh()->usado_por_id);
        $this->assertNotNull($convite->fresh()->usado_em);
    }

    public function test_o_codigo_e_aceito_em_qualquer_grafia(): void
    {
        $convite = $this->convite();

        // Vai ser digitado à mão, e ninguém acerta o hífen e a caixa.
        $desfigurado = strtolower(str_replace('-', ' ', $convite->codigo));

        $this->cadastrarCom($this->dadosDeCadastro($desfigurado))
            ->assertRedirect(route('inicio'));

        $this->assertNotNull($convite->fresh()->usado_em);
    }

    public function test_um_convite_serve_a_uma_conta_so(): void
    {
        $convite = $this->convite();

        $this->cadastrarCom($this->dadosDeCadastro($convite->codigo));

        // Um código circulando num grupo de mensagens abriria a base que o
        // convite existe para fechar.
        $segundo = $this->dadosDeCadastro($convite->codigo);
        $segundo['email'] = 'outra@exemplo.br';
        $segundo['cpf'] = '153.509.460-56';

        $this->cadastrarCom($segundo)->assertHasErrors(['codigoConvite']);

        $this->assertSame(1, Usuario::count());
    }

    public function test_convite_cancelado_nao_serve(): void
    {
        $convite = $this->convite();

        app(GerirConvites::class)->revogar($convite, $this->operador, 'Entregue por engano.');

        $this->cadastrarCom($this->dadosDeCadastro($convite->codigo))
            ->assertHasErrors(['codigoConvite']);
    }

    public function test_convite_vencido_nao_serve(): void
    {
        $convite = app(GerirConvites::class)
            ->emitir($this->operador, 1, expiraEm: now()->subDay())
            ->first();

        $this->cadastrarCom($this->dadosDeCadastro($convite->codigo))
            ->assertHasErrors(['codigoConvite']);
    }

    public function test_o_cadastro_exige_declarar_uma_proficiencia(): void
    {
        $dados = $this->dadosDeCadastro($this->convite()->codigo);
        $dados['proficienciaId'] = null;

        // 06 R2 — convite condicionado à declaração de ao menos uma
        // proficiência. É a mitigação do risco de rede assimétrica.
        $this->cadastrarCom($dados)->assertHasErrors(['proficienciaId']);

        $this->assertSame(0, Usuario::count());
    }

    public function test_a_proficiencia_declarada_no_cadastro_e_registrada(): void
    {
        $this->cadastrarCom($this->dadosDeCadastro($this->convite()->codigo));

        $declarada = ProficienciaUsuario::firstOrFail();

        $this->assertSame($this->proficiencia->id, $declarada->proficiencia_id);
        $this->assertSame(ProficienciaUsuario::NIVEL_INTERMEDIARIO, $declarada->nivel);
    }

    public function test_cadastro_que_falha_nao_gasta_o_convite(): void
    {
        $convite = $this->convite();
        $dados = $this->dadosDeCadastro($convite->codigo);
        $dados['cpf'] = '111.111.111-11';

        $this->cadastrarCom($dados)->assertHasErrors(['cpf']);

        // O convite continua valendo: quem errou o CPF tenta de novo.
        $this->assertTrue($convite->fresh()->podeSerUsado());
        $this->assertSame(0, Usuario::count());
    }

    public function test_com_a_base_aberta_o_convite_deixa_de_ser_exigido(): void
    {
        // 06 §8 — abrir o cadastro é decisão de fase, e se faz por parâmetro.
        ParametroNegocio::definir(ParametroNegocio::CADASTRO_POR_CONVITE, '0');

        $this->cadastrarCom($this->dadosDeCadastro())
            ->assertRedirect(route('inicio'));

        $this->assertSame(1, Usuario::count());
    }

    // --------------------------------------------------------------- Painel

    public function test_a_operacao_emite_convites_pelo_painel(): void
    {
        Livewire::actingAs($this->operador, 'operacao')
            ->test(ListConvites::class)
            ->callAction('emitir', [
                'quantidade' => 3,
                'destinatario' => 'Associação de moradores',
                'observacao' => null,
                'expira_em' => null,
            ]);

        $this->assertSame(3, Convite::count());
    }

    public function test_convite_usado_nao_se_cancela(): void
    {
        $convite = $this->convite();
        $this->cadastrarCom($this->dadosDeCadastro($convite->codigo));

        // A conta existe; fingir que cancelar o convite a desfaz seria pior
        // do que não permitir.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/use a suspensão/');

        app(GerirConvites::class)->revogar($convite->fresh(), $this->operador, 'Arrependi.');
    }
}
