<?php

namespace Tests\Feature;

use App\Dominio\Consumo\ApurarRealizacao;
use App\Dominio\Consumo\ResponderSolicitacao;
use App\Dominio\Consumo\SolicitarFavor;
use App\Dominio\Negociacao\Composicao;
use App\Dominio\Negociacao\EnviarProposta;
use App\Dominio\Reputacao\AvaliacaoPendente;
use App\Dominio\Reputacao\ConsultarReputacao;
use App\Dominio\Reputacao\RegistrarAvaliacao;
use App\Dominio\Ticket\EmitirTickets;
use App\Models\Avaliacao;
use App\Models\Categoria;
use App\Models\Favor;
use App\Models\ManifestacaoRealizacao;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Solicitacao;
use App\Models\Subcategoria;
use App\Models\Ticket;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

/**
 * O contexto Reputação (02 §11, 05 §5.5).
 *
 * É a fatia que fecha o ciclo: sem as duas avaliações, a solicitação não
 * encerra e o ticket não chega a `ENCERRADO` (I21).
 */
class ReputacaoTest extends TestCase
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

    private function favorDe(Usuario $ajudante, string $proficienciaNome = 'Elétrica residencial'): Favor
    {
        $categoria = Categoria::create(['nome' => 'Casa e reparos '.uniqid()]);
        $subcategoria = Subcategoria::create([
            'categoria_id' => $categoria->id,
            'nome' => 'Reparos',
        ]);
        $proficiencia = Proficiencia::create([
            'subcategoria_id' => $subcategoria->id,
            'nome' => $proficienciaNome,
        ]);

        $proficienciaUsuario = ProficienciaUsuario::create([
            'usuario_id' => $ajudante->id,
            'proficiencia_id' => $proficiencia->id,
            'nivel' => ProficienciaUsuario::NIVEL_ESPECIALISTA,
            'declarada_em' => now(),
        ]);

        $favor = Favor::create([
            'proficiencia_usuario_id' => $proficienciaUsuario->id,
            'titulo' => 'Reparos em geral',
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

        return $favor->fresh();
    }

    /**
     * Leva um favor até `REALIZADA`, que é quando a avaliação é liberada.
     */
    private function favorRealizado(?Favor $favor = null, ?Carbon $quando = null): Solicitacao
    {
        $favor ??= $this->favorDe($this->ajudante);

        [$ticket] = (new EmitirTickets())->executar($favor, 1);
        $ticket->transferirPara($this->solicitante, 'proposta_aceita');

        $inicio = $quando ?? Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0);

        $solicitacao = (new SolicitarFavor())->executar($ticket->fresh(), $this->solicitante, $inicio);
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        $apurar = new ApurarRealizacao();
        $solicitacao = $apurar->abrirJanela($solicitacao, $solicitacao->fim_previsto->copy()->addMinute());

        $momento = $solicitacao->fim_previsto->copy()->addHour();
        $apurar->manifestar($solicitacao, $this->ajudante, ManifestacaoRealizacao::CONFIRMA, $momento);

        return $apurar->manifestar(
            $solicitacao->fresh(),
            $this->solicitante,
            ManifestacaoRealizacao::CONFIRMA,
            $momento,
        );
    }

    /** @return array<string, int> */
    private function notasDoAjudante(int $nota = 6): array
    {
        return [
            Avaliacao::COMUNICACAO => $nota,
            Avaliacao::QUALIDADE => $nota,
            Avaliacao::SATISFACAO => $nota,
        ];
    }

    /** @return array<string, int> */
    private function notasDoSolicitante(int $nota = 5): array
    {
        return [
            Avaliacao::CORDIALIDADE => $nota,
            Avaliacao::COMUNICACAO => $nota,
            Avaliacao::CONDICOES => $nota,
        ];
    }

    private function avaliarAjudante(Solicitacao $solicitacao, int $nota = 6): Avaliacao
    {
        return (new RegistrarAvaliacao())->executar(
            $solicitacao,
            $this->solicitante,
            $this->notasDoAjudante($nota),
            ['acabamento'],
        );
    }

    private function avaliarSolicitante(Solicitacao $solicitacao, int $nota = 5): Avaliacao
    {
        return (new RegistrarAvaliacao())->executar(
            $solicitacao,
            $this->ajudante,
            $this->notasDoSolicitante($nota),
        );
    }

    // ---------------------------------------------------------- Obrigatoriedade

    public function test_a_avaliacao_so_e_liberada_com_o_favor_realizado(): void
    {
        [$ticket] = (new EmitirTickets())->executar($this->favorDe($this->ajudante), 1);
        $ticket->transferirPara($this->solicitante, 'proposta_aceita');

        $solicitacao = (new SolicitarFavor())->executar(
            $ticket->fresh(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );

        // RF-AVA-01 e I20 — avaliar experiência que não aconteceu distorceria
        // a reputação dos dois.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/só é liberada quando o favor/');

        $this->avaliarAjudante($solicitacao);
    }

    public function test_o_ciclo_encerra_com_as_duas_avaliacoes(): void
    {
        $solicitacao = $this->favorRealizado();
        $ticket = $solicitacao->ticket;

        $this->avaliarAjudante($solicitacao);

        // Uma só não fecha nada: o ciclo continua aberto (RF-AVA-10).
        $this->assertSame(Solicitacao::REALIZADA, $solicitacao->fresh()->estado);
        $this->assertSame(Ticket::CONSUMIDO, $ticket->fresh()->estado);

        $this->avaliarSolicitante($solicitacao->fresh());

        // I21 e 05 §6.1
        $this->assertSame(Solicitacao::ENCERRADA, $solicitacao->fresh()->estado);
        $this->assertSame(Ticket::ENCERRADO, $ticket->fresh()->estado);
    }

    public function test_ninguem_avalia_o_mesmo_favor_duas_vezes(): void
    {
        $solicitacao = $this->favorRealizado();
        $this->avaliarAjudante($solicitacao);

        // I24 — a segunda tentativa não é correção; a avaliação é imutável.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/já avaliou este favor/');

        $this->avaliarAjudante($solicitacao->fresh());
    }

    public function test_quem_nao_e_parte_nao_avalia(): void
    {
        $solicitacao = $this->favorRealizado();
        $estranho = $this->usuario('Ana Prado');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não participa da solicitação/');

        (new RegistrarAvaliacao())->executar(
            $solicitacao,
            $estranho,
            $this->notasDoAjudante(),
            ['acabamento'],
        );
    }

    // ------------------------------------------------------ Critérios e notas

    public function test_cada_papel_tem_seus_proprios_criterios(): void
    {
        $solicitacao = $this->favorRealizado();

        $doAjudante = $this->avaliarAjudante($solicitacao);
        $doSolicitante = $this->avaliarSolicitante($solicitacao->fresh());

        // RF-AVA-03 e RF-AVA-04
        $this->assertEqualsCanonicalizing(
            [Avaliacao::COMUNICACAO, Avaliacao::QUALIDADE, Avaliacao::SATISFACAO],
            $doAjudante->notas->pluck('criterio')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [Avaliacao::CORDIALIDADE, Avaliacao::COMUNICACAO, Avaliacao::CONDICOES],
            $doSolicitante->notas->pluck('criterio')->all(),
        );

        $this->assertSame(Solicitacao::PARTE_AJUDANTE, $doAjudante->papel_avaliado);
        $this->assertSame($this->ajudante->id, $doAjudante->avaliado_id);
    }

    public function test_as_notas_por_criterio_sao_obrigatorias(): void
    {
        $solicitacao = $this->favorRealizado();

        // RF-AVA-06 — faltar critério deixaria a média comparando pessoas
        // avaliadas em dimensões diferentes.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Faltam notas nos critérios/');

        (new RegistrarAvaliacao())->executar(
            $solicitacao,
            $this->solicitante,
            [Avaliacao::COMUNICACAO => 6],
            ['acabamento'],
        );
    }

    public function test_recusa_criterio_que_nao_se_aplica_ao_papel(): void
    {
        $solicitacao = $this->favorRealizado();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não se aplicam a este papel/');

        (new RegistrarAvaliacao())->executar(
            $solicitacao,
            $this->solicitante,
            $this->notasDoAjudante() + [Avaliacao::CORDIALIDADE => 7],
            ['acabamento'],
        );
    }

    public function test_a_nota_respeita_a_escala_de_zero_a_sete(): void
    {
        $solicitacao = $this->favorRealizado();

        // I25 e RF-AVA-02
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/inteiro de 0 a 7/');

        (new RegistrarAvaliacao())->executar(
            $solicitacao,
            $this->solicitante,
            $this->notasDoAjudante(8),
            ['acabamento'],
        );
    }

    public function test_a_nota_zero_e_valida(): void
    {
        $solicitacao = $this->favorRealizado();

        // O intervalo é fechado: 0 é avaliação, não ausência de avaliação.
        $avaliacao = $this->avaliarAjudante($solicitacao, 0);

        $this->assertSame(0, $avaliacao->fresh()->nota(Avaliacao::QUALIDADE));
    }

    public function test_qualidade_exige_motivo_pre_determinado(): void
    {
        $solicitacao = $this->favorRealizado();

        // RF-AVA-05 — apontar a dimensão é o que reduz acusação genérica.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/exige ao menos um motivo/');

        (new RegistrarAvaliacao())->executar(
            $solicitacao,
            $this->solicitante,
            $this->notasDoAjudante(),
        );
    }

    public function test_recusa_motivo_fora_da_lista(): void
    {
        $solicitacao = $this->favorRealizado();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Motivos fora da lista/');

        (new RegistrarAvaliacao())->executar(
            $solicitacao,
            $this->solicitante,
            $this->notasDoAjudante(),
            ['nao_gostei_da_cara'],
        );
    }

    public function test_o_comentario_e_opcional(): void
    {
        $solicitacao = $this->favorRealizado();

        $avaliacao = (new RegistrarAvaliacao())->executar(
            $solicitacao,
            $this->solicitante,
            $this->notasDoAjudante(),
            ['acabamento'],
            '   ',
        );

        // RF-AVA-06 — texto em branco é ausência de comentário, não comentário.
        $this->assertNull($avaliacao->comentario);
    }

    // ------------------------------------------------------------ Imutabilidade

    public function test_a_avaliacao_e_imutavel_no_proprio_banco(): void
    {
        $solicitacao = $this->favorRealizado();
        $avaliacao = $this->avaliarAjudante($solicitacao);

        // I23 — a garantia vive no armazenamento, não na convenção da
        // aplicação: continua valendo para qualquer caminho de escrita.
        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/imutável/');

        \Illuminate\Support\Facades\DB::update(
            'UPDATE avaliacoes SET comentario = ? WHERE id = ?',
            ['Mudei de ideia.', $avaliacao->id]
        );
    }

    public function test_a_nota_nao_pode_ser_apagada(): void
    {
        $solicitacao = $this->favorRealizado();
        $avaliacao = $this->avaliarAjudante($solicitacao);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/imutável/');

        \Illuminate\Support\Facades\DB::delete(
            'DELETE FROM notas_criterio WHERE avaliacao_id = ?',
            [$avaliacao->id]
        );
    }

    public function test_a_avaliacao_nao_altera_o_nivel_declarado(): void
    {
        $favor = $this->favorDe($this->ajudante);
        $nivelAntes = $favor->proficienciaUsuario->nivel;

        $solicitacao = $this->favorRealizado($favor);
        $this->avaliarAjudante($solicitacao, 0);
        $this->avaliarSolicitante($solicitacao->fresh(), 0);

        // I27 e RF-AVA-13 — invariante de ausência. Proficiência é o que a
        // pessoa declara; reputação é o que os outros dizem.
        $this->assertSame($nivelAntes, $favor->proficienciaUsuario->fresh()->nivel);
    }

    // --------------------------------------------------------------- Reputação

    public function test_a_reputacao_e_separada_por_papel(): void
    {
        $solicitacao = $this->favorRealizado();
        $this->avaliarAjudante($solicitacao, 7);
        $this->avaliarSolicitante($solicitacao->fresh(), 3);

        $consulta = new ConsultarReputacao();

        // RF-REP-01 e RF-REP-02 — a mesma pessoa acumula reputações distintas
        // nos dois papéis (02 §1).
        $this->assertSame(7.0, $consulta->mediaGeral($this->ajudante, Solicitacao::PARTE_AJUDANTE));
        $this->assertNull($consulta->mediaGeral($this->ajudante, Solicitacao::PARTE_SOLICITANTE));
        $this->assertSame(3.0, $consulta->mediaGeral($this->solicitante, Solicitacao::PARTE_SOLICITANTE));
    }

    public function test_a_media_por_criterio_e_apresentada_separadamente(): void
    {
        $solicitacao = $this->favorRealizado();

        (new RegistrarAvaliacao())->executar(
            $solicitacao,
            $this->solicitante,
            [
                Avaliacao::COMUNICACAO => 7,
                Avaliacao::QUALIDADE => 4,
                Avaliacao::SATISFACAO => 6,
            ],
            ['acabamento', 'tempo_de_execucao'],
        );

        // RF-REP-03 — uma nota só esconderia onde a experiência foi boa e
        // onde não foi.
        $medias = (new ConsultarReputacao())->mediasPorCriterio(
            $this->ajudante,
            Solicitacao::PARTE_AJUDANTE,
        );

        $this->assertSame(7.0, $medias[Avaliacao::COMUNICACAO]);
        $this->assertSame(4.0, $medias[Avaliacao::QUALIDADE]);
    }

    public function test_a_reputacao_e_segmentada_por_proficiencia(): void
    {
        $eletrica = $this->favorDe($this->ajudante, 'Elétrica residencial');
        $this->avaliarAjudante($this->favorRealizado($eletrica), 7);

        $encanamento = $this->favorDe($this->ajudante, 'Encanamento');
        $this->avaliarAjudante(
            $this->favorRealizado($encanamento, Carbon::now()->addDays(20)->next(Carbon::WEDNESDAY)->setTime(10, 0)),
            3,
        );

        // RF-REP-04 e 02 §11.2 — experiências de proficiências diferentes são
        // avaliadas separadamente, ainda que o ajudante seja o mesmo.
        $porProficiencia = collect((new ConsultarReputacao())->porProficiencia($this->ajudante))
            ->keyBy('proficiencia');

        $this->assertSame(7.0, $porProficiencia['Elétrica residencial']['media']);
        $this->assertSame(3.0, $porProficiencia['Encanamento']['media']);

        // RF-REP-07 — o nível declarado aparece ao lado da reputação obtida.
        $this->assertSame(
            ProficienciaUsuario::NIVEL_ESPECIALISTA,
            $porProficiencia['Elétrica residencial']['nivel'],
        );
    }

    public function test_o_perfil_indica_quando_faltam_avaliacoes(): void
    {
        $solicitacao = $this->favorRealizado();
        $this->avaliarAjudante($solicitacao);

        // RF-REP-08 — média sobre uma avaliação só é opinião com aparência de
        // estatística.
        $consulta = new ConsultarReputacao();

        $this->assertFalse(
            $consulta->temAvaliacoesSuficientes($this->ajudante, Solicitacao::PARTE_AJUDANTE)
        );
        $this->assertNotNull(
            $consulta->mediaGeral($this->ajudante, Solicitacao::PARTE_AJUDANTE)
        );
    }

    public function test_conta_favores_prestados_e_recebidos(): void
    {
        $solicitacao = $this->favorRealizado();
        $this->avaliarAjudante($solicitacao);
        $this->avaliarSolicitante($solicitacao->fresh());

        $consulta = new ConsultarReputacao();

        // RF-REP-05
        $this->assertSame(1, $consulta->favoresPrestados($this->ajudante));
        $this->assertSame(1, $consulta->favoresRecebidos($this->solicitante));
        $this->assertSame(0, $consulta->favoresPrestados($this->solicitante));
    }

    // ------------------------------------------------------ Avaliação pendente

    public function test_a_avaliacao_pendente_dentro_do_prazo_nao_restringe(): void
    {
        $solicitacao = $this->favorRealizado();
        $pendente = new AvaliacaoPendente();

        $liberadaEm = $solicitacao->realizadaEm();

        // A pendência é de cada parte sobre a mesma solicitação: são duas
        // avaliações devidas, não duas solicitações.
        $this->assertCount(1, $pendente->de($this->solicitante));
        $this->assertCount(1, $pendente->de($this->ajudante));

        // RF-AVA-12 — dentro do prazo, o caso é de aviso, não de restrição.
        $this->assertFalse(
            $pendente->restringeNovasPropostas($this->solicitante, $liberadaEm->copy()->addDays(3))
        );
        $this->assertCount(1, $pendente->aAvisar($this->solicitante, $liberadaEm->copy()->addDays(3)));
    }

    public function test_a_avaliacao_vencida_impede_enviar_nova_proposta(): void
    {
        $solicitacao = $this->favorRealizado();
        $vencido = $solicitacao->realizadaEm()->copy()->addDays(8);

        // O prazo decidido na Fase 0 é de 7 dias.
        $this->assertTrue(
            (new AvaliacaoPendente())->restringeNovasPropostas($this->solicitante, $vencido)
        );

        Carbon::setTestNow($vencido);

        try {
            $terceiro = $this->usuario('Ana Prado');
            [$doTerceiro] = (new EmitirTickets())->executar($this->favorDe($terceiro), 1);

            // RF-NEG-15 e RF-AVA-11
            $this->expectException(LogicException::class);
            $this->expectExceptionMessageMatches('/avaliação pendente além do prazo/');

            (new EnviarProposta())->executar(
                $this->solicitante,
                $terceiro,
                Composicao::de([$doTerceiro]),
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_avaliar_devolve_a_possibilidade_de_propor(): void
    {
        $solicitacao = $this->favorRealizado();
        $vencido = $solicitacao->realizadaEm()->copy()->addDays(8);

        $this->avaliarAjudante($solicitacao);

        // 02 §11.4 — o objetivo é destravar o ciclo, não punir: a restrição
        // cai sozinha assim que a avaliação é registrada.
        $this->assertFalse(
            (new AvaliacaoPendente())->restringeNovasPropostas($this->solicitante, $vencido)
        );
    }
}
