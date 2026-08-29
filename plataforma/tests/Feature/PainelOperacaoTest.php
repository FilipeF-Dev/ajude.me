<?php

namespace Tests\Feature;

use App\Dominio\Consumo\ApurarRealizacao;
use App\Dominio\Consumo\ResponderSolicitacao;
use App\Dominio\Consumo\SolicitarFavor;
use App\Dominio\Moderacao\RegistrarDenuncia;
use App\Dominio\Ticket\EmitirTickets;
use App\Filament\Resources\Chamados\Pages\ListChamados;
use App\Filament\Resources\Chamados\Pages\VerChamado;
use App\Filament\Resources\Denuncias\Pages\ListDenuncias;
use App\Filament\Resources\Ocorrencias\Pages\ListOcorrencias;
use App\Filament\Resources\RegistroAuditorias\Pages\ListRegistroAuditorias;
use App\Filament\Resources\TermoCiencias\Pages\ListTermoCiencias;
use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Denuncia;
use App\Models\Favor;
use App\Models\ManifestacaoRealizacao;
use App\Models\Ocorrencia;
use App\Models\Operador;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\RegistroAuditoria;
use App\Models\Solicitacao;
use App\Models\Subcategoria;
use App\Models\TermoCiencia;
use App\Models\Ticket;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * O painel da Operação (04 §17).
 *
 * > O módulo administrativo não é acessório. Sem curadoria, fila de chamados
 * > e trilha de auditoria, o piloto não pode ser operado (06 §5.2).
 *
 * O que se protege aqui é a fronteira: a Operação decide, e o que ela não
 * pode fazer é tão importante quanto o que pode.
 */
class PainelOperacaoTest extends TestCase
{
    use RefreshDatabase;

    private Operador $operador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametrosNegocioSeeder::class);

        $this->operador = Operador::create([
            'nome' => 'Operação',
            'email' => 'operacao@ajude.me',
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

    /** Leva um atendimento até a contestação, que é o que abre o chamado. */
    private function chamadoAberto(): Chamado
    {
        $ajudante = $this->usuario('Marta Vilela');
        $solicitante = $this->usuario('Joel Batista');

        $categoria = Categoria::create(['nome' => 'Casa e reparos '.uniqid()]);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'nome' => 'Reparos']);
        $proficiencia = Proficiencia::create([
            'subcategoria_id' => $subcategoria->id,
            'nome' => 'Elétrica residencial',
        ]);

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
            'maximo_circulacao' => 2,
            'trocavel' => true,
        ]);
        $favor->ativar();

        [$ticket] = (new EmitirTickets())->executar($favor->fresh(), 1);
        $ticket->transferirPara($solicitante, 'proposta_aceita');

        $solicitacao = (new SolicitarFavor())->executar(
            $ticket->fresh(),
            $solicitante,
            now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $ajudante);

        $apurar = new ApurarRealizacao();
        $solicitacao = $apurar->abrirJanela($solicitacao, $solicitacao->fim_previsto->copy()->addMinute());

        $apurar->manifestar(
            $solicitacao,
            $solicitante,
            ManifestacaoRealizacao::CONTESTA,
            $solicitacao->fim_previsto->copy()->addHour(),
        );

        return Chamado::where('solicitacao_id', $solicitacao->id)->firstOrFail();
    }

    public function test_o_painel_exige_operador(): void
    {
        // Operador não é Usuario: são atores distintos, em guards separados.
        $this->get('/operacao/chamados')->assertRedirect();

        $this->actingAs($this->usuario('Joel Batista'))
            ->get('/operacao/chamados')
            ->assertRedirect();
    }

    public function test_a_fila_de_chamados_abre(): void
    {
        $chamado = $this->chamadoAberto();

        Livewire::actingAs($this->operador, 'operacao')
            ->test(ListChamados::class)
            ->assertCanSeeTableRecords([$chamado])
            ->assertSee('Marta Vilela');
    }

    public function test_a_apuracao_mostra_o_que_a_operacao_precisa_ver(): void
    {
        $chamado = $this->chamadoAberto();

        // RF-ADM-12 — manifestações, comparecimento e histórico de ocorrências.
        Livewire::actingAs($this->operador, 'operacao')
            ->test(VerChamado::class, ['record' => $chamado->id])
            ->assertSee('O que cada parte diz')
            ->assertSee('Registro de comparecimento')
            ->assertSee('Histórico de ocorrências das partes');
    }

    public function test_a_decisao_exige_fundamento(): void
    {
        $chamado = $this->chamadoAberto();

        // RF-ADM-13 e RF-SOL-19 — sem fundamento não há o que comunicar.
        Livewire::actingAs($this->operador, 'operacao')
            ->test(VerChamado::class, ['record' => $chamado->id])
            ->callAction('decidir', ['decisao' => Chamado::INCONCLUSIVO, 'fundamento' => ''])
            ->assertHasActionErrors(['fundamento']);

        $this->assertTrue($chamado->fresh()->estaAberto());
    }

    public function test_a_decisao_aplica_os_efeitos_e_entra_na_trilha(): void
    {
        $chamado = $this->chamadoAberto();
        $solicitacao = $chamado->solicitacao;

        Livewire::actingAs($this->operador, 'operacao')
            ->test(VerChamado::class, ['record' => $chamado->id])
            ->callAction('decidir', [
                'decisao' => Chamado::NAO_REALIZADO,
                'fundamento' => 'Não há registro de comparecimento do ajudante.',
            ]);

        // RF-ADM-14 — o sistema aplica os efeitos da decisão.
        $this->assertSame(Chamado::DECIDIDO, $chamado->fresh()->estado);
        $this->assertSame(Solicitacao::NAO_REALIZADA, $solicitacao->fresh()->estado);
        $this->assertSame(Ticket::DISPONIVEL, $solicitacao->ticket->fresh()->estado);

        // RF-SOL-17 — ocorrência contra o ajudante.
        $this->assertSame(
            Ocorrencia::FAVOR_NAO_REALIZADO,
            Ocorrencia::where('contra_usuario_id', $solicitacao->ajudante_id)->first()?->tipo,
        );

        // RF-ADM-10 — a auditoria é a contrapartida do poder da Operação.
        $this->assertSame(
            $this->operador->id,
            RegistroAuditoria::where('alvo_tipo', Chamado::class)->first()?->operador_id,
        );
    }

    public function test_o_chamado_nao_e_criado_nem_editado_pela_operacao(): void
    {
        // RF-SOL-12 — chamado nasce de contestação, e a decisão é ato com
        // fundamento, não edição de campo.
        $this->assertFalse(\App\Filament\Resources\Chamados\ChamadoResource::canCreate());
        $this->assertFalse(\App\Filament\Resources\Chamados\ChamadoResource::canEdit($this->chamadoAberto()));
    }

    public function test_a_fila_de_denuncias_abre_e_analisa(): void
    {
        $autor = $this->usuario('Joel Batista');
        $alvo = $this->usuario('Marta Vilela');

        $denuncia = (new RegistrarDenuncia())->contraUsuario(
            $autor,
            $alvo,
            'Pediu dinheiro por fora para realizar o favor.',
        );

        Livewire::actingAs($this->operador, 'operacao')
            ->test(ListDenuncias::class)
            ->assertCanSeeTableRecords([$denuncia])
            ->callTableAction('analisar', $denuncia, [
                'estado' => Denuncia::PROCEDENTE,
                'desfecho' => 'Relato consistente com outras denúncias.',
            ]);

        $this->assertSame(Denuncia::PROCEDENTE, $denuncia->fresh()->estado);
        $this->assertSame($this->operador->id, $denuncia->fresh()->analisada_por_id);
    }

    public function test_a_operacao_publica_o_termo_e_a_versao_anterior_encerra(): void
    {
        $tela = Livewire::actingAs($this->operador, 'operacao')->test(ListTermoCiencias::class);

        $tela->callAction('publicar', ['texto' => 'Primeira redação do termo.']);
        $tela->callAction('publicar', ['texto' => 'Segunda redação, revisada.']);

        // RF-SEG-04 — o texto de uma versão publicada nunca muda.
        $primeira = TermoCiencia::where('versao', 1)->first();

        $this->assertNotNull($primeira->encerrado_em);
        $this->assertSame('Primeira redação do termo.', $primeira->texto);
        $this->assertSame(2, TermoCiencia::vigente()->versao);
    }

    public function test_as_filas_de_consulta_abrem(): void
    {
        $this->chamadoAberto();

        Livewire::actingAs($this->operador, 'operacao')
            ->test(ListOcorrencias::class)
            ->assertOk();

        Livewire::actingAs($this->operador, 'operacao')
            ->test(ListRegistroAuditorias::class)
            ->assertOk();
    }

    public function test_a_trilha_de_auditoria_nao_e_editavel_nem_removivel(): void
    {
        $registro = RegistroAuditoria::registrar($this->operador, 'teste', $this->operador, 'motivo');

        // Uma trilha que a própria Operação pudesse editar não auditaria nada.
        $recurso = \App\Filament\Resources\RegistroAuditorias\RegistroAuditoriaResource::class;

        $this->assertFalse($recurso::canCreate());
        $this->assertFalse($recurso::canEdit($registro));
        $this->assertFalse($recurso::canDelete($registro));
    }
}
