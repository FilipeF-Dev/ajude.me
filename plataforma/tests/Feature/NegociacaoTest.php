<?php

namespace Tests\Feature;

use App\Dominio\Negociacao\AceitarProposta;
use App\Dominio\Negociacao\Composicao;
use App\Dominio\Negociacao\EncerrarProposta;
use App\Dominio\Negociacao\EnviarProposta;
use App\Dominio\Negociacao\RegistrarMensagem;
use App\Dominio\Negociacao\RevisarProposta;
use App\Dominio\Ticket\EmitirTickets;
use App\Models\Categoria;
use App\Models\Favor;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Proposta;
use App\Models\Subcategoria;
use App\Models\Ticket;
use App\Models\TransferenciaTicket;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

/**
 * As invariantes do contexto Negociação (05 §5.3).
 *
 * O que se protege aqui é a promessa de que ninguém perde um ticket sem
 * receber o combinado: I13 a I17.
 */
class NegociacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // O prazo de resposta da proposta é parâmetro, não constante (P6):
        // sem os parâmetros semeados, o envio recusa em vez de inventar valor.
        $this->seed(ParametrosNegocioSeeder::class);
    }

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

    /**
     * Emite tickets de um favor novo do próprio ajudante. Cada chamada cria
     * o catálogo de que precisa: os testes não compartilham estado.
     *
     * @return array<int, Ticket>
     */
    private function ticketsDe(Usuario $ajudante, int $quantidade = 1, bool $trocavel = true): array
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
            'usuario_id' => $ajudante->id,
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
            'validade_ticket_dias' => 365,
            'maximo_circulacao' => 5,
            'trocavel' => $trocavel,
        ]);

        $favor->ativar();

        return (new EmitirTickets())->executar($favor->fresh(), $quantidade);
    }

    private function enviar(Usuario $remetente, Usuario $destinatario, array $solicitados, array $contrapartida = []): Proposta
    {
        return (new EnviarProposta())->executar(
            $remetente,
            $destinatario,
            Composicao::de($solicitados, $contrapartida),
        );
    }

    public function test_o_envio_bloqueia_todos_os_tickets_dos_dois_lados(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');

        [$doBruno] = $this->ticketsDe($bruno);
        [$daAna] = $this->ticketsDe($ana);

        $proposta = $this->enviar($ana, $bruno, [$doBruno], [$daAna]);

        // RF-NEG-05 — o bloqueio é a própria mudança de estado do ticket.
        $this->assertSame(Ticket::EM_NEGOCIACAO, $doBruno->fresh()->estado);
        $this->assertSame(Ticket::EM_NEGOCIACAO, $daAna->fresh()->estado);
        $this->assertSame(Proposta::ENVIADA, $proposta->estado);
        $this->assertSame(1, $proposta->versao_atual);
    }

    public function test_o_prazo_da_proposta_vem_do_parametro_vigente(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');
        [$doBruno] = $this->ticketsDe($bruno);

        $proposta = $this->enviar($ana, $bruno, [$doBruno]);

        // RF-NEG-13 com o valor decidido na Fase 0: 7 dias.
        $this->assertSame(
            7,
            (int) $proposta->enviada_em->diffInDays($proposta->expira_em)
        );
    }

    public function test_um_ticket_nao_participa_de_duas_propostas(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');
        $clara = $this->usuario('Clara Vidal');

        [$doBruno] = $this->ticketsDe($bruno);

        $this->enviar($ana, $bruno, [$doBruno]);

        // I14 — a segunda proposta não encontra o ticket disponível.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/em_negociacao/');

        $this->enviar($clara, $bruno, [$doBruno]);
    }

    public function test_ticket_nao_trocavel_nao_compoe_proposta(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');

        [$doBruno] = $this->ticketsDe($bruno);
        [$daAna] = $this->ticketsDe($ana, trocavel: false);

        // I13 — o ajudante decidiu que aquele favor não circula.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não admite troca/');

        $this->enviar($ana, $bruno, [$doBruno], [$daAna]);
    }

    public function test_a_proposta_so_move_ticket_da_carteira_certa(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');

        [$daAna] = $this->ticketsDe($ana);

        // Solicitar da carteira do destinatário é o contrato do lado
        // "solicitado"; pedir o próprio ticket de volta não é troca.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não está na carteira de Bruno Lisboa/');

        $this->enviar($ana, $bruno, [$daAna]);
    }

    public function test_o_aceite_transfere_os_dois_lados_de_uma_vez(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');

        [$doBruno] = $this->ticketsDe($bruno);
        [$daAna, $outroDaAna] = $this->ticketsDe($ana, 2);

        $proposta = $this->enviar($ana, $bruno, [$doBruno], [$daAna, $outroDaAna]);

        (new AceitarProposta())->executar($proposta, $bruno, $proposta->versao_atual);

        // I16 — todos mudam de carteira, ou nenhum muda.
        $this->assertSame($ana->id, $doBruno->fresh()->proprietario_id);
        $this->assertSame($bruno->id, $daAna->fresh()->proprietario_id);
        $this->assertSame($bruno->id, $outroDaAna->fresh()->proprietario_id);

        // 05 §6.1 — aceite devolve o ticket a DISPONIVEL na carteira nova.
        $this->assertSame(Ticket::DISPONIVEL, $doBruno->fresh()->estado);

        $this->assertSame(Proposta::ACEITA, $proposta->fresh()->estado);
        $this->assertSame(1, $proposta->fresh()->versao_aceita);
    }

    public function test_o_aceite_registra_a_cadeia_de_propriedade(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');
        [$doBruno] = $this->ticketsDe($bruno);

        $proposta = $this->enviar($ana, $bruno, [$doBruno]);
        (new AceitarProposta())->executar($proposta, $bruno, 1);

        // I8 — emissão e transferência, nessa ordem, sem buraco.
        $cadeia = $doBruno->fresh()->transferencias()->get();

        $this->assertCount(2, $cadeia);
        $this->assertTrue($cadeia->first()->ehEmissao());
        $this->assertSame(TransferenciaTicket::MOTIVO_PROPOSTA_ACEITA, $cadeia->last()->motivo);
        $this->assertSame($ana->id, $cadeia->last()->para_usuario_id);
    }

    public function test_a_revisao_invalida_o_aceite_da_versao_anterior(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');

        [$doBruno] = $this->ticketsDe($bruno);
        [$daAna, $outroDaAna] = $this->ticketsDe($ana, 2);

        $proposta = $this->enviar($ana, $bruno, [$doBruno], [$daAna]);

        // Bruno quer os dois tickets da Ana, não um.
        (new RevisarProposta())->executar(
            $proposta,
            $bruno,
            Composicao::de([$doBruno], [$daAna, $outroDaAna]),
        );

        $this->assertSame(2, $proposta->fresh()->versao_atual);
        $this->assertSame(Proposta::EM_NEGOCIACAO, $proposta->fresh()->estado);
        $this->assertSame(Ticket::EM_NEGOCIACAO, $outroDaAna->fresh()->estado);

        // I15 e RF-NEG-09 — o aceite da versão 1 não vale mais.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/A oferta foi revisada/');

        (new AceitarProposta())->executar($proposta->fresh(), $ana, 1);
    }

    public function test_quem_revisou_nao_aceita_a_propria_revisao(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');

        [$doBruno] = $this->ticketsDe($bruno);
        [$daAna] = $this->ticketsDe($ana);

        $proposta = $this->enviar($ana, $bruno, [$doBruno], [$daAna]);

        (new RevisarProposta())->executar($proposta, $bruno, Composicao::de([$doBruno]));

        // RF-NEG-09 — depois de revisar, a decisão é da outra parte.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não está aguardando resposta sua/');

        (new AceitarProposta())->executar($proposta->fresh(), $bruno, 2);
    }

    public function test_a_revisao_libera_o_ticket_retirado_da_oferta(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');

        [$doBruno] = $this->ticketsDe($bruno);
        [$daAna] = $this->ticketsDe($ana);

        $proposta = $this->enviar($ana, $bruno, [$doBruno], [$daAna]);

        (new RevisarProposta())->executar($proposta, $bruno, Composicao::de([$doBruno]));

        // Prender ticket que ninguém mais negocia é confisco silencioso.
        $this->assertSame(Ticket::DISPONIVEL, $daAna->fresh()->estado);
    }

    public function test_a_recusa_devolve_os_tickets_a_circulacao(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');

        [$doBruno] = $this->ticketsDe($bruno);
        [$daAna] = $this->ticketsDe($ana);

        $proposta = $this->enviar($ana, $bruno, [$doBruno], [$daAna]);

        (new EncerrarProposta())->recusar($proposta, $bruno);

        // RF-NEG-11
        $this->assertSame(Proposta::RECUSADA, $proposta->fresh()->estado);
        $this->assertSame(Ticket::DISPONIVEL, $doBruno->fresh()->estado);
        $this->assertSame(Ticket::DISPONIVEL, $daAna->fresh()->estado);
    }

    public function test_somente_o_remetente_retira_a_proposta(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');
        [$doBruno] = $this->ticketsDe($bruno);

        $proposta = $this->enviar($ana, $bruno, [$doBruno]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Somente quem enviou/');

        (new EncerrarProposta())->retirar($proposta, $bruno);
    }

    public function test_a_proposta_vencida_libera_os_tickets(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');
        [$doBruno] = $this->ticketsDe($bruno);

        $proposta = $this->enviar($ana, $bruno, [$doBruno]);

        $depoisDoPrazo = Carbon::now()->addDays(8);

        $this->assertTrue($proposta->estaVencida($depoisDoPrazo));

        (new EncerrarProposta())->expirar($proposta, $depoisDoPrazo);

        $this->assertSame(Proposta::EXPIRADA, $proposta->fresh()->estado);
        $this->assertSame(Ticket::DISPONIVEL, $doBruno->fresh()->estado);
    }

    public function test_o_aceite_fora_do_prazo_e_recusado(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');
        [$doBruno] = $this->ticketsDe($bruno);

        $proposta = $this->enviar($ana, $bruno, [$doBruno]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/prazo de resposta desta proposta já venceu/');

        (new AceitarProposta())->executar($proposta, $bruno, 1, Carbon::now()->addDays(8));
    }

    public function test_conta_nao_ativa_nao_negocia(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');
        [$doBruno] = $this->ticketsDe($bruno);

        $ana->forceFill(['estado_conta' => Usuario::ESTADO_ENCERRANDO])->save();

        // RF-NEG-16 e RF-CAD-13 — quem está saindo não inicia nada novo.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não está ativa/');

        $this->enviar($ana->fresh(), $bruno, [$doBruno]);
    }

    public function test_proposta_encerrada_nao_recebe_mensagem(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');
        [$doBruno] = $this->ticketsDe($bruno);

        $proposta = $this->enviar($ana, $bruno, [$doBruno]);

        (new RegistrarMensagem())->executar($proposta, $bruno, 'Consegue na quarta?');
        $this->assertCount(1, $proposta->fresh()->mensagens);

        (new EncerrarProposta())->recusar($proposta->fresh(), $bruno);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não recebe mais mensagens/');

        (new RegistrarMensagem())->executar($proposta->fresh(), $ana, 'E na quinta?');
    }

    public function test_a_proposta_precisa_solicitar_algo(): void
    {
        $ana = $this->usuario('Ana Prado');
        [$daAna] = $this->ticketsDe($ana);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/precisa solicitar ao menos um ticket/');

        Composicao::de([], [$daAna]);
    }

    public function test_o_segundo_aceite_do_mesmo_ticket_falha_dizendo_qual(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');
        $clara = $this->usuario('Clara Vidal');

        [$doBruno] = $this->ticketsDe($bruno);
        [$daAna] = $this->ticketsDe($ana);
        [$daClara] = $this->ticketsDe($clara);

        // 05 §7.1 — Bruno recebe duas propostas pelo mesmo ticket dele.
        // I14 impede que as duas fiquem ativas, e é a segunda a falhar.
        $primeira = $this->enviar($ana, $bruno, [$doBruno], [$daAna]);

        try {
            $this->enviar($clara, $bruno, [$doBruno], [$daClara]);
            $this->fail('A segunda proposta pelo mesmo ticket deveria ter falhado.');
        } catch (LogicException $erro) {
            // A recusa diz qual ticket ficou indisponível: quem propôs
            // precisa saber o que remontar.
            $this->assertStringContainsString($doBruno->codigo, $erro->getMessage());
        }

        // A primeira segue de pé, e o ticket da Clara não ficou preso.
        $this->assertSame(Proposta::ENVIADA, $primeira->fresh()->estado);
        $this->assertSame(Ticket::DISPONIVEL, $daClara->fresh()->estado);
    }
}
