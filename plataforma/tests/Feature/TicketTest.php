<?php

namespace Tests\Feature;

use App\Dominio\Ticket\EmitirTickets;
use App\Models\Categoria;
use App\Models\Favor;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Subcategoria;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

/**
 * As invariantes do contexto Ticket (05 §5.1).
 *
 * O ticket é o agregado central: sua integridade tem precedência sobre
 * qualquer conveniência do sistema (P1).
 */
class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $nome = 'Rafael Menezes'): Usuario
    {
        return Usuario::create([
            'nome' => $nome,
            'email' => uniqid('pessoa').'@exemplo.br',
            'telefone' => '11988420000',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'senha' => 'segredo123',
        ]);
    }

    private function favorAtivo(int $maximoCirculacao = 3, int $validadeDias = 365): Favor
    {
        $categoria = Categoria::create(['nome' => 'Casa e reparos '.uniqid()]);
        $subcategoria = Subcategoria::create([
            'categoria_id' => $categoria->id,
            'nome' => 'Elétrica',
        ]);
        $proficiencia = Proficiencia::create([
            'subcategoria_id' => $subcategoria->id,
            'nome' => 'Elétrica residencial',
        ]);

        $proficienciaUsuario = ProficienciaUsuario::create([
            'usuario_id' => $this->usuario()->id,
            'proficiencia_id' => $proficiencia->id,
            'nivel' => ProficienciaUsuario::NIVEL_ESPECIALISTA,
            'declarada_em' => now(),
        ]);

        $favor = Favor::create([
            'proficiencia_usuario_id' => $proficienciaUsuario->id,
            'titulo' => 'Instalações e reparos elétricos',
            'descricao' => 'Troco resistência de chuveiro, tomada e disjuntor.',
            'tempo_estimado_minutos' => 40,
            'modalidade' => Favor::MODALIDADE_PRESENCIAL,
            'area_atendimento_km' => 3,
            'antecedencia_minima_dias' => 2,
            'validade_ticket_dias' => $validadeDias,
            'maximo_circulacao' => $maximoCirculacao,
            'trocavel' => true,
        ]);

        $favor->ativar();

        return $favor->fresh();
    }

    private function emitir(Favor $favor, int $quantidade): array
    {
        return (new EmitirTickets())->executar($favor, $quantidade);
    }

    public function test_a_emissao_congela_a_condicao_publicada(): void
    {
        $favor = $this->favorAtivo();
        $this->assertFalse($favor->estaCongelado());

        $this->emitir($favor, 1);

        $this->assertTrue($favor->fresh()->estaCongelado());
    }

    public function test_o_ticket_nasce_na_carteira_de_quem_emitiu(): void
    {
        $favor = $this->favorAtivo();
        [$ticket] = $this->emitir($favor, 1);

        $ajudante = $favor->proficienciaUsuario->usuario;

        $this->assertSame($ajudante->id, $ticket->proprietario_id);
        $this->assertSame(Ticket::DISPONIVEL, $ticket->estado);

        // I8 — a emissão vem de lugar nenhum.
        $primeira = $ticket->transferencias()->first();
        $this->assertTrue($primeira->ehEmissao());
        $this->assertSame($ajudante->id, $primeira->para_usuario_id);
    }

    public function test_recusa_emitir_alem_do_limite_de_circulacao(): void
    {
        $favor = $this->favorAtivo(maximoCirculacao: 3);
        $this->emitir($favor, 3);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Vagas disponíveis: 0/');

        $this->emitir($favor->fresh(), 1);
    }

    public function test_ticket_consumido_libera_vaga_para_nova_emissao(): void
    {
        $favor = $this->favorAtivo(maximoCirculacao: 2);
        [$primeiro] = $this->emitir($favor, 2);

        // O limite é de circulação simultânea, não vitalício (02 §5.1).
        $primeiro->transicionar(Ticket::RESERVADO, 'solicitacao_agendada');
        $primeiro->transicionar(Ticket::CONSUMIDO, 'realizacao_confirmada');

        $novos = $this->emitir($favor->fresh(), 1);

        $this->assertCount(1, $novos);
        $this->assertSame(3, Ticket::where('favor_id', $favor->id)->count());
    }

    public function test_recusa_transicao_fora_da_maquina_de_estados(): void
    {
        $favor = $this->favorAtivo();
        [$ticket] = $this->emitir($favor, 1);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Transição inválida/');

        // Não se consome um ticket que nunca foi reservado.
        $ticket->transicionar(Ticket::CONSUMIDO, 'atalho_indevido');
    }

    public function test_toda_transicao_deixa_rastro(): void
    {
        $favor = $this->favorAtivo();
        [$ticket] = $this->emitir($favor, 1);

        $ticket->transicionar(Ticket::RESERVADO, 'solicitacao_agendada');

        $eventos = $ticket->transicoes()->pluck('evento')->all();

        // P2 — nem o nascimento fica sem registro.
        $this->assertSame(['emissao', 'solicitacao_agendada'], $eventos);
    }

    public function test_ticket_reservado_nao_expira(): void
    {
        $favor = $this->favorAtivo(validadeDias: 30);
        [$ticket] = $this->emitir($favor, 1);

        $depoisDaValidade = Carbon::now()->addDays(31);

        $this->assertTrue($ticket->deveExpirar($depoisDaValidade));

        // I6 — o ajudante não inutiliza um ticket deixando a data passar.
        $ticket->transicionar(Ticket::RESERVADO, 'solicitacao_agendada');

        $this->assertFalse($ticket->fresh()->deveExpirar($depoisDaValidade));
    }

    public function test_ticket_em_analise_tem_a_validade_suspensa(): void
    {
        $favor = $this->favorAtivo(validadeDias: 30);
        [$ticket] = $this->emitir($favor, 1);

        $ticket->transicionar(Ticket::RESERVADO, 'solicitacao_agendada');
        $ticket->transicionar(Ticket::EM_ANALISE, 'realizacao_contestada');

        $ticket = $ticket->fresh();

        // I7 — a demora da plataforma não extingue direito em disputa.
        $this->assertNotNull($ticket->validade_suspensa_em);
        $this->assertFalse($ticket->deveExpirar(Carbon::now()->addDays(90)));
    }

    public function test_a_transferencia_registra_a_cadeia_de_propriedade(): void
    {
        $favor = $this->favorAtivo();
        [$ticket] = $this->emitir($favor, 1);

        $solicitante = $this->usuario('Cleide Ramos');
        $ticket->transferirPara($solicitante, 'proposta_aceita');

        $cadeia = $ticket->fresh()->transferencias()->get();

        $this->assertCount(2, $cadeia);
        $this->assertSame($solicitante->id, $ticket->fresh()->proprietario_id);
        $this->assertSame($solicitante->id, $cadeia->last()->para_usuario_id);
    }

    public function test_favor_desativado_nao_emite_mesmo_com_vaga(): void
    {
        $favor = $this->favorAtivo(maximoCirculacao: 5);
        $this->emitir($favor, 1);

        $favor->fresh()->desativar();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não pode emitir tickets/');

        $this->emitir($favor->fresh(), 1);
    }

    public function test_recusa_validade_acima_do_teto_vigente(): void
    {
        $this->seed(\Database\Seeders\ParametrosNegocioSeeder::class);

        $favor = $this->favorAtivo(validadeDias: 400);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/excede o teto vigente de 365 dias/');

        $this->emitir($favor, 1);
    }
}
