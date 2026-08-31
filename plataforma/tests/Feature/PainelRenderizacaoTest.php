<?php

namespace Tests\Feature;

use App\Dominio\Consumo\ApurarRealizacao;
use App\Dominio\Consumo\ResponderSolicitacao;
use App\Dominio\Consumo\SolicitarFavor;
use App\Dominio\Ticket\EmitirTickets;
use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Favor;
use App\Models\ManifestacaoRealizacao;
use App\Models\Operador;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Subcategoria;
use App\Models\TermoPopular;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Renderização HTTP de todas as páginas do painel da Operação.
 *
 * Existe por causa de um defeito real: `Operador` implementava
 * `getFilamentName()` sem declarar o contrato `HasName`, e o painel inteiro
 * quebrava ao montar o menu do usuário. Nenhum teste pegou, porque os testes
 * do painel exercitam **componentes Livewire isolados** — e o layout, que é
 * onde a falha morava, não é montado nesse caminho.
 *
 * Testar componente e testar página são coisas diferentes. Este arquivo cobre
 * a segunda: uma requisição HTTP de verdade, com layout, menu e navegação.
 * Falha de contrato, de view ausente ou de propriedade nula no cabeçalho
 * aparecem aqui e em nenhum outro lugar.
 */
class PainelRenderizacaoTest extends TestCase
{
    use RefreshDatabase;

    private Operador $operador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametrosNegocioSeeder::class);

        $this->operador = Operador::create([
            'nome' => 'Operação',
            'email' => uniqid('op').'@ajude.me',
            'senha' => 'segredo123',
        ]);
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

    private function proficiencia(): Proficiencia
    {
        $categoria = Categoria::create(['nome' => 'Casa e reparos '.uniqid()]);
        $subcategoria = Subcategoria::create([
            'categoria_id' => $categoria->id,
            'nome' => 'Elétrica '.uniqid(),
        ]);

        return Proficiencia::create([
            'subcategoria_id' => $subcategoria->id,
            'nome' => 'Elétrica residencial',
        ]);
    }

    /** Leva uma solicitação até a contestação, que é o que abre o chamado. */
    private function chamado(): Chamado
    {
        $ajudante = $this->usuario('Marta Vilela');
        $solicitante = $this->usuario('Joel Batista');

        $proficienciaUsuario = ProficienciaUsuario::create([
            'usuario_id' => $ajudante->id,
            'proficiencia_id' => $this->proficiencia()->id,
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
            'validade_ticket_dias' => 365,
            'maximo_circulacao' => 5,
            'trocavel' => true,
        ]);
        $favor->ativar();

        [$ticket] = (new EmitirTickets())->executar($favor->fresh(), 1);
        $ticket->transferirPara($solicitante, 'proposta_aceita');

        $solicitacao = (new SolicitarFavor())->executar(
            $ticket->fresh(),
            $solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $ajudante);

        $apurar = new ApurarRealizacao();
        $solicitacao = $apurar->abrirJanela(
            $solicitacao,
            $solicitacao->fim_previsto->copy()->addMinute(),
        );
        $solicitacao = $apurar->manifestar(
            $solicitacao,
            $solicitante,
            ManifestacaoRealizacao::CONTESTA,
            $solicitacao->fim_previsto->copy()->addHour(),
        );

        return Chamado::where('solicitacao_id', $solicitacao->id)->firstOrFail();
    }

    /** @return array<int, array<int, string>> */
    public static function paginasDeLista(): array
    {
        return [
            'painel' => ['/operacao'],
            'categorias' => ['/operacao/categorias'],
            'proficiências' => ['/operacao/proficiencias'],
            'termos populares' => ['/operacao/termo-populars'],
            'chamados' => ['/operacao/chamados'],
            'denúncias' => ['/operacao/denuncias'],
            'ocorrências' => ['/operacao/ocorrencias'],
            'convites' => ['/operacao/convites'],
            'termos de ciência' => ['/operacao/termo-ciencias'],
            'trilha de auditoria' => ['/operacao/registro-auditorias'],
            'métricas' => ['/operacao/metricas'],
            'cobertura' => ['/operacao/cobertura'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paginasDeLista')]
    public function test_toda_pagina_de_lista_renderiza(string $rota): void
    {
        $this->actingAs($this->operador, 'operacao')
            ->get($rota)
            ->assertOk();
    }

    /** @return array<int, array<int, string>> */
    public static function paginasDeCriacao(): array
    {
        return [
            'categoria' => ['/operacao/categorias/create'],
            'proficiência' => ['/operacao/proficiencias/create'],
            'termo popular' => ['/operacao/termo-populars/create'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paginasDeCriacao')]
    public function test_toda_pagina_de_criacao_renderiza(string $rota): void
    {
        $this->actingAs($this->operador, 'operacao')
            ->get($rota)
            ->assertOk();
    }

    public function test_as_paginas_de_edicao_renderizam(): void
    {
        $proficiencia = $this->proficiencia();
        $termo = TermoPopular::create([
            'proficiencia_id' => $proficiencia->id,
            'termo' => 'chuveiro',
        ]);

        $emOperacao = fn (string $rota) => $this->actingAs($this->operador, 'operacao')
            ->get($rota)
            ->assertOk();

        $emOperacao("/operacao/categorias/{$proficiencia->subcategoria->categoria_id}/edit");
        $emOperacao("/operacao/proficiencias/{$proficiencia->id}/edit");
        $emOperacao("/operacao/termo-populars/{$termo->id}/edit");
    }

    public function test_a_pagina_do_chamado_renderiza_com_dados_reais(): void
    {
        $chamado = $this->chamado();

        $this->actingAs($this->operador, 'operacao')
            ->get("/operacao/chamados/{$chamado->id}")
            ->assertOk();
    }

    /**
     * O painel decide, não fabrica.
     *
     * Chamado nasce de contestação, ocorrência é consequência de um ato e a
     * trilha de auditoria não se edita. A ausência destas rotas é a regra
     * expressa em roteamento — se um dia aparecerem, algo mudou sem querer.
     *
     * @return array<int, array<int, string>>
     */
    public static function rotasQueNaoDevemExistir(): array
    {
        return [
            'criar chamado' => ['/operacao/chamados/create'],
            'criar denúncia' => ['/operacao/denuncias/create'],
            'criar ocorrência' => ['/operacao/ocorrencias/create'],
            'criar registro de auditoria' => ['/operacao/registro-auditorias/create'],
            'editar registro de auditoria' => ['/operacao/registro-auditorias/1/edit'],
            'editar ocorrência' => ['/operacao/ocorrencias/1/edit'],
            // Identificador que não é número: chegava ao Postgres como texto
            // e derrubava a página com erro de tipo. Ver AppServiceProvider.
            'chamado com identificador inválido' => ['/operacao/chamados/abc'],
            'categoria com identificador inválido' => ['/operacao/categorias/xyz/edit'],
        ];
    }

    /**
     * A asserção é "não renderiza", e não "devolve 404", porque esta
     * aplicação nunca devolve 404: `Route::fallback()` manda qualquer URL
     * desconhecida para a home — escolha deliberada, já que errar o endereço
     * e cair numa página de erro é pior para o público do produto.
     *
     * E `/operacao/chamados/create` nem chega ao fallback: casa com
     * `chamados/{record}` tratando "create" como identificador, não acha o
     * registro e o Filament redireciona. Caminhos diferentes, mesmo desfecho —
     * o que importa é que página de criação nenhuma aparece.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('rotasQueNaoDevemExistir')]
    public function test_o_painel_decide_mas_nao_fabrica(string $rota): void
    {
        $resposta = $this->actingAs($this->operador, 'operacao')->get($rota);

        $this->assertContains(
            $resposta->status(),
            [302, 403, 404],
            "A rota {$rota} respondeu {$resposta->status()}: era para recusar ou redirecionar, nunca renderizar."
        );
    }

    public function test_operador_desativado_perde_o_painel(): void
    {
        $this->operador->forceFill(['desativado_em' => Carbon::now()])->save();

        $this->actingAs($this->operador, 'operacao')
            ->get('/operacao')
            ->assertForbidden();
    }

    public function test_o_nome_do_operador_aparece_no_painel(): void
    {
        // Guarda contra a regressão exata que originou este arquivo: sem o
        // contrato `HasName`, o Filament busca o atributo `name`, que não
        // existe em `operadores`, e a página inteira morre.
        $this->actingAs($this->operador, 'operacao')
            ->get('/operacao')
            ->assertOk()
            ->assertSee($this->operador->nome);
    }
}
