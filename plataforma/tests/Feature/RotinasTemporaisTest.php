<?php

namespace Tests\Feature;

use App\Dominio\Consumo\ResponderSolicitacao;
use App\Dominio\Consumo\SolicitarFavor;
use App\Dominio\Negociacao\Composicao;
use App\Dominio\Negociacao\EnviarProposta;
use App\Dominio\Rotinas\RotinasTemporais;
use App\Dominio\Ticket\EmitirTickets;
use App\Models\Categoria;
use App\Models\Favor;
use App\Models\Notificacao;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Proposta;
use App\Models\Solicitacao;
use App\Models\Subcategoria;
use App\Models\Ticket;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * O que o tempo faz sozinho (05 §15.2).
 *
 * Sem estas rotinas, os prazos do produto seriam decoração: a janela de
 * contestação nunca abriria, a confirmação tácita nunca aconteceria e o
 * ticket vencido ficaria vencido para sempre sem nunca expirar.
 */
class RotinasTemporaisTest extends TestCase
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

    private function favorDe(Usuario $ajudante, int $validadeDias = 365): Favor
    {
        $categoria = Categoria::create(['nome' => 'Casa e reparos '.uniqid()]);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'nome' => 'Reparos']);
        $proficiencia = Proficiencia::create([
            'subcategoria_id' => $subcategoria->id,
            'nome' => 'Elétrica residencial',
        ]);

        $proficienciaUsuario = ProficienciaUsuario::create([
            'usuario_id' => $ajudante->id,
            'proficiencia_id' => $proficiencia->id,
            'nivel' => ProficienciaUsuario::NIVEL_ESPECIALISTA,
            'declarada_em' => now(),
        ]);

        $favor = Favor::create([
            'proficiencia_usuario_id' => $proficienciaUsuario->id,
            'titulo' => 'Reparos elétricos',
            'descricao' => 'Troco resistência de chuveiro, tomada e disjuntor.',
            'tempo_estimado_minutos' => 60,
            'modalidade' => Favor::MODALIDADE_REMOTO,
            'area_atendimento_km' => null,
            'antecedencia_minima_dias' => 2,
            'validade_ticket_dias' => $validadeDias,
            'maximo_circulacao' => 5,
            'trocavel' => true,
        ]);

        $favor->ativar();

        return $favor->fresh();
    }

    private function ticketDoSolicitante(int $validadeDias = 365): Ticket
    {
        [$ticket] = (new EmitirTickets())->executar($this->favorDe($this->ajudante, $validadeDias), 1);
        $ticket->transferirPara($this->solicitante, 'proposta_aceita');

        return $ticket->fresh();
    }

    public function test_a_proposta_vencida_e_encerrada_sozinha(): void
    {
        [$doAjudante] = (new EmitirTickets())->executar($this->favorDe($this->ajudante), 1);

        $proposta = (new EnviarProposta())->executar(
            $this->solicitante,
            $this->ajudante,
            Composicao::de([$doAjudante]),
        );

        $resultado = (new RotinasTemporais())->executar(Carbon::now()->addDays(8));

        // RF-NEG-13 — esgotado o prazo, os tickets voltam a circular.
        $this->assertSame(1, $resultado['propostas_expiradas']);
        $this->assertSame(Proposta::EXPIRADA, $proposta->fresh()->estado);
        $this->assertSame(Ticket::DISPONIVEL, $doAjudante->fresh()->estado);
    }

    public function test_o_ticket_que_vence_na_negociacao_derruba_a_proposta(): void
    {
        // Validade de 3 dias contra prazo de resposta de 7: o ticket vence
        // primeiro, e é isso que este caso precisa isolar.
        [$doAjudante] = (new EmitirTickets())->executar($this->favorDe($this->ajudante, 3), 1);

        $proposta = (new EnviarProposta())->executar(
            $this->solicitante,
            $this->ajudante,
            Composicao::de([$doAjudante]),
        );

        // Dia 4: o ticket venceu, mas a proposta ainda está no prazo.
        $resultado = (new RotinasTemporais())->executar(Carbon::now()->addDays(4));

        $this->assertSame(0, $resultado['propostas_expiradas']);

        // 02 §5.4 — não se transfere direito vencido.
        $this->assertSame(1, $resultado['propostas_por_ticket_vencido']);
        $this->assertSame(Proposta::EXPIRADA, $proposta->fresh()->estado);
        $this->assertSame(Ticket::EXPIRADO, $doAjudante->fresh()->estado);
    }

    public function test_o_ticket_reservado_nao_expira_na_rotina(): void
    {
        $ticket = $this->ticketDoSolicitante(30);

        $solicitacao = (new SolicitarFavor())->executar(
            $ticket,
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        // Dia 31: a validade do ticket passou há um dia.
        $resultado = (new RotinasTemporais())->executar(Carbon::now()->addDays(31));

        // I6 — o ajudante não inutiliza um ticket deixando a data passar.
        // A rotina não decide isso: pergunta ao ticket, que já sabe.
        $this->assertSame(0, $resultado['tickets_expirados']);

        // O ticket seguiu o caminho normal em vez de morrer pelo relógio: o
        // atendimento aconteceu, ninguém contestou, e o direito foi exercido.
        $this->assertSame(Ticket::CONSUMIDO, $ticket->fresh()->estado);
    }

    public function test_a_janela_de_contestacao_abre_sozinha(): void
    {
        $solicitacao = (new SolicitarFavor())->executar(
            $this->ticketDoSolicitante(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        $resultado = (new RotinasTemporais())->executar(
            $solicitacao->fim_previsto->copy()->addMinute()
        );

        // RF-SOL-08 e RF-NOT-12
        $this->assertSame(1, $resultado['janelas_abertas']);
        $this->assertSame(Solicitacao::AGUARDANDO_CONFIRMACAO, $solicitacao->fresh()->estado);

        $this->assertSame(
            2,
            Notificacao::where('tipo', Notificacao::CONFIRMACAO_PENDENTE)->count()
        );
    }

    public function test_o_silencio_encerra_o_favor_sozinho(): void
    {
        $ticket = $this->ticketDoSolicitante();

        $solicitacao = (new SolicitarFavor())->executar(
            $ticket,
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        $rotinas = new RotinasTemporais();
        $rotinas->executar($solicitacao->fim_previsto->copy()->addMinute());

        // 72 horas depois, ninguém contestou.
        $resultado = $rotinas->executar($solicitacao->fim_previsto->copy()->addHours(73));

        // RF-SOL-10 — um ciclo aberto para sempre prenderia o ticket e
        // travaria a reputação dos dois.
        $this->assertSame(1, $resultado['confirmacoes_tacitas']);
        $this->assertSame(Solicitacao::REALIZADA, $solicitacao->fresh()->estado);
        $this->assertSame(Ticket::CONSUMIDO, $ticket->fresh()->estado);
    }

    public function test_a_confirmacao_tacita_nao_e_antecipada(): void
    {
        $solicitacao = (new SolicitarFavor())->executar(
            $this->ticketDoSolicitante(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        $rotinas = new RotinasTemporais();
        $rotinas->executar($solicitacao->fim_previsto->copy()->addMinute());

        // 05 §7.5 — a janela ainda corre; a contestação de agora prevaleceria.
        $resultado = $rotinas->executar($solicitacao->fim_previsto->copy()->addHours(70));

        $this->assertSame(0, $resultado['confirmacoes_tacitas']);
        $this->assertSame(Solicitacao::AGUARDANDO_CONFIRMACAO, $solicitacao->fresh()->estado);
    }

    public function test_avisa_a_avaliacao_pendente_antes_de_restringir(): void
    {
        $solicitacao = (new SolicitarFavor())->executar(
            $this->ticketDoSolicitante(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        $rotinas = new RotinasTemporais();
        $rotinas->executar($solicitacao->fim_previsto->copy()->addMinute());
        $rotinas->executar($solicitacao->fim_previsto->copy()->addHours(73));

        // Dentro do prazo: cobrança, não restrição (RF-AVA-12, RF-NOT-13).
        $rotinas->executar($solicitacao->fim_previsto->copy()->addDays(4));

        $this->assertSame(2, Notificacao::where('tipo', Notificacao::AVALIACAO_PENDENTE)->count());
        $this->assertSame(0, Notificacao::where('tipo', Notificacao::RESTRICAO_POR_AVALIACAO)->count());

        // Vencido o prazo: a restrição é comunicada (RF-NOT-14).
        $rotinas->executar($solicitacao->fim_previsto->copy()->addDays(12));

        $this->assertSame(2, Notificacao::where('tipo', Notificacao::RESTRICAO_POR_AVALIACAO)->count());
    }

    public function test_a_rotina_nao_repete_o_mesmo_aviso_a_cada_execucao(): void
    {
        $solicitacao = (new SolicitarFavor())->executar(
            $this->ticketDoSolicitante(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        $rotinas = new RotinasTemporais();
        $rotinas->executar($solicitacao->fim_previsto->copy()->addMinute());
        $rotinas->executar($solicitacao->fim_previsto->copy()->addHours(73));

        $momento = $solicitacao->fim_previsto->copy()->addDays(4);

        $rotinas->executar($momento);
        $rotinas->executar($momento->copy()->addMinutes(10));
        $rotinas->executar($momento->copy()->addMinutes(20));

        // A rotina reavalia o mesmo estado a cada execução; sem cuidado, um
        // lembrete viraria um lembrete a cada dez minutos.
        $this->assertSame(2, Notificacao::where('tipo', Notificacao::AVALIACAO_PENDENTE)->count());
    }

    public function test_a_rotina_e_segura_com_o_sistema_vazio(): void
    {
        $resultado = (new RotinasTemporais())->executar();

        $this->assertSame(0, array_sum($resultado));
    }
}
