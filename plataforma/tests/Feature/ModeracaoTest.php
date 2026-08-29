<?php

namespace Tests\Feature;

use App\Dominio\Consumo\ApurarRealizacao;
use App\Dominio\Consumo\CancelarSolicitacao;
use App\Dominio\Consumo\ResponderSolicitacao;
use App\Dominio\Consumo\SolicitarFavor;
use App\Dominio\Moderacao\AbrirChamado;
use App\Dominio\Moderacao\AplicarSuspensao;
use App\Dominio\Moderacao\DecidirChamado;
use App\Dominio\Moderacao\GerirBloqueio;
use App\Dominio\Moderacao\RegistrarDenuncia;
use App\Dominio\Moderacao\RegistrarOcorrencia;
use App\Dominio\Negociacao\Composicao;
use App\Dominio\Negociacao\EnviarProposta;
use App\Dominio\Seguranca\GerirTermoDeCiencia;
use App\Dominio\Ticket\EmitirTickets;
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
use App\Models\Ticket;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

/**
 * O contexto Moderação (05 §4.7) e a segurança do atendimento presencial.
 *
 * É o contexto que decide. Ticket e Consumo executam o que ele decidiu —
 * nunca o contrário.
 */
class ModeracaoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $ajudante;

    private Usuario $solicitante;

    private Operador $operador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametrosNegocioSeeder::class);

        $this->ajudante = $this->usuario('Marta Vilela');
        $this->solicitante = $this->usuario('Joel Batista');
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

    private function favorDe(Usuario $ajudante): Favor
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

    private function ticketDoSolicitante(): Ticket
    {
        [$ticket] = (new EmitirTickets())->executar($this->favorDe($this->ajudante), 1);
        $ticket->transferirPara($this->solicitante, 'proposta_aceita');

        return $ticket->fresh();
    }

    /** Leva a solicitação até a contestação, que é o que abre o chamado. */
    private function contestada(): Solicitacao
    {
        $solicitacao = (new SolicitarFavor())->executar(
            $this->ticketDoSolicitante(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );

        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        $apurar = new ApurarRealizacao();
        $solicitacao = $apurar->abrirJanela(
            $solicitacao,
            $solicitacao->fim_previsto->copy()->addMinute(),
        );

        return $apurar->manifestar(
            $solicitacao,
            $this->solicitante,
            ManifestacaoRealizacao::CONTESTA,
            $solicitacao->fim_previsto->copy()->addHour(),
        );
    }

    // ---------------------------------------------------------------- Bloqueio

    public function test_o_bloqueio_impede_proposta_nas_duas_direcoes(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');

        [$doBruno] = (new EmitirTickets())->executar($this->favorDe($bruno), 1);

        (new GerirBloqueio())->bloquear($bruno, $ana);

        // I17 — o bloqueio de Bruno alcança a proposta que Ana tentaria enviar.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Não é possível negociar com este usuário/');

        (new EnviarProposta())->executar($ana, $bruno, Composicao::de([$doBruno]));
    }

    public function test_a_recusa_do_bloqueio_nao_revela_quem_bloqueou(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');

        [$doBruno] = (new EmitirTickets())->executar($this->favorDe($bruno), 1);

        (new GerirBloqueio())->bloquear($ana, $bruno);

        try {
            (new EnviarProposta())->executar($ana, $bruno, Composicao::de([$doBruno]));
            $this->fail('A proposta deveria ter sido recusada.');
        } catch (LogicException $erro) {
            // Dizer quem bloqueou entregaria a decisão de uma pessoa à outra,
            // que é justamente o que o bloqueio evita (02 §18.2).
            $this->assertStringNotContainsString('bloque', mb_strtolower($erro->getMessage()));
        }
    }

    public function test_o_bloqueio_nao_interrompe_solicitacao_ja_agendada(): void
    {
        $solicitacao = (new SolicitarFavor())->executar(
            $this->ticketDoSolicitante(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        (new GerirBloqueio())->bloquear($this->solicitante, $this->ajudante);

        // RF-BLO-06 — bloquear não é forma de escapar de um compromisso.
        $this->assertSame(Solicitacao::AGENDADA, $solicitacao->fresh()->estado);
        $this->assertSame(Ticket::RESERVADO, $solicitacao->ticket->fresh()->estado);
    }

    public function test_o_desbloqueio_devolve_a_possibilidade_de_negociar(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bruno = $this->usuario('Bruno Lisboa');
        [$doBruno] = (new EmitirTickets())->executar($this->favorDe($bruno), 1);

        $bloqueio = new GerirBloqueio();
        $bloqueio->bloquear($ana, $bruno);
        $bloqueio->desbloquear($ana, $bruno);

        $proposta = (new EnviarProposta())->executar($ana, $bruno, Composicao::de([$doBruno]));

        $this->assertNotNull($proposta->id);
    }

    public function test_o_bloqueio_nao_se_repete(): void
    {
        $ana = $this->usuario('Ana Prado');
        $bloqueio = new GerirBloqueio();
        $bloqueio->bloquear($this->solicitante, $ana);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/já está bloqueado/');

        $bloqueio->bloquear($this->solicitante, $ana);
    }

    // --------------------------------------------------------------- Denúncia

    public function test_a_denuncia_entra_aberta_na_fila_da_operacao(): void
    {
        $denuncia = (new RegistrarDenuncia())->contraUsuario(
            $this->solicitante,
            $this->ajudante,
            'Pediu dinheiro por fora para realizar o favor.',
        );

        // RF-BLO-08
        $this->assertSame(Denuncia::ABERTA, $denuncia->estado);
        $this->assertSame($this->ajudante->id, $denuncia->alvo_usuario_id);
    }

    public function test_a_denuncia_exige_motivo(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/precisa informar o motivo/');

        (new RegistrarDenuncia())->contraUsuario($this->solicitante, $this->ajudante, '   ');
    }

    public function test_denunciar_nao_bloqueia_automaticamente(): void
    {
        (new RegistrarDenuncia())->contraUsuario(
            $this->solicitante,
            $this->ajudante,
            'Comportamento inadequado durante o atendimento.',
        );

        // São dois atos com propósitos diferentes; presumir um a partir do
        // outro decidiria pela pessoa algo que ela não pediu.
        $this->assertFalse(
            \App\Models\Bloqueio::existeEntre($this->solicitante->id, $this->ajudante->id)
        );
    }

    // ------------------------------------------------------------- Ocorrência

    public function test_o_cancelamento_do_ajudante_registra_ocorrencia(): void
    {
        $solicitacao = (new SolicitarFavor())->executar(
            $this->ticketDoSolicitante(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        (new CancelarSolicitacao())->peloAjudante(
            $solicitacao,
            $this->ajudante,
            'Precisei viajar por motivo de família.',
        );

        // RF-CAN-06 — em qualquer prazo.
        $ocorrencia = Ocorrencia::where('contra_usuario_id', $this->ajudante->id)->first();

        $this->assertNotNull($ocorrencia);
        $this->assertSame(Ocorrencia::CANCELAMENTO_DO_AJUDANTE, $ocorrencia->tipo);
        $this->assertTrue($ocorrencia->ehAutomatica());
    }

    public function test_o_cancelamento_com_antecedencia_nao_registra_ocorrencia(): void
    {
        $solicitacao = (new SolicitarFavor())->executar(
            $this->ticketDoSolicitante(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        // RF-CAN-08 — 24 horas ou mais não gera ocorrência nem penalidade.
        (new CancelarSolicitacao())->peloSolicitante(
            $solicitacao,
            $this->solicitante,
            momento: $solicitacao->inicio_previsto->copy()->subDays(3),
        );

        $this->assertSame(0, Ocorrencia::count());
    }

    public function test_o_cancelamento_em_cima_da_hora_registra_ocorrencia(): void
    {
        $solicitacao = (new SolicitarFavor())->executar(
            $this->ticketDoSolicitante(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );
        $solicitacao = (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);

        // RF-CAN-15 — cancelar continua permitido; o registro é a consequência.
        (new CancelarSolicitacao())->peloSolicitante(
            $solicitacao,
            $this->solicitante,
            momento: $solicitacao->inicio_previsto->copy()->subHours(2),
        );

        $ocorrencia = Ocorrencia::where('contra_usuario_id', $this->solicitante->id)->first();

        $this->assertSame(Ocorrencia::CANCELAMENTO_EM_CIMA_DA_HORA, $ocorrencia->tipo);
        $this->assertSame(Solicitacao::CANCELADA_SOLICITANTE, $solicitacao->fresh()->estado);
    }

    public function test_a_parte_registra_ocorrencia_contra_a_outra(): void
    {
        $solicitacao = $this->contestada();

        $ocorrencia = (new RegistrarOcorrencia())->pelaParte(
            $solicitacao,
            $this->ajudante,
            'O solicitante não estava no endereço combinado.',
        );

        // RF-CAN-10 — o relato tem autor, e é lido como relato, não como fato.
        $this->assertSame(Ocorrencia::ORIGEM_USUARIO, $ocorrencia->origem);
        $this->assertSame($this->ajudante->id, $ocorrencia->autor_id);
        $this->assertSame($this->solicitante->id, $ocorrencia->contra_usuario_id);
    }

    public function test_quem_nao_e_parte_nao_registra_ocorrencia(): void
    {
        $solicitacao = $this->contestada();
        $estranho = $this->usuario('Ana Prado');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não participa da solicitação/');

        (new RegistrarOcorrencia())->pelaParte($solicitacao, $estranho, 'Vi tudo de longe.');
    }

    // ---------------------------------------------------------------- Chamado

    public function test_a_contestacao_abre_chamado_com_prazo_de_manifestacao(): void
    {
        $solicitacao = $this->contestada();

        // RF-SOL-12 — a contestação abre o chamado no mesmo ato.
        $chamado = Chamado::where('solicitacao_id', $solicitacao->id)->first();

        $this->assertNotNull($chamado);
        $this->assertTrue($chamado->estaAberto());
        $this->assertSame($this->solicitante->id, $chamado->aberto_por_id);

        // Prazo vigente decidido na Fase 0: 5 dias.
        $this->assertSame(5, (int) $chamado->aberto_em->diffInDays($chamado->manifestacao_ate));
    }

    public function test_cada_parte_apresenta_sua_versao_uma_vez(): void
    {
        $chamado = Chamado::where('solicitacao_id', $this->contestada()->id)->first();
        $abrir = new AbrirChamado();

        $abrir->manifestar($chamado, $this->ajudante, 'Estive lá e fiz o serviço.');
        $abrir->manifestar($chamado, $this->solicitante, 'Ninguém apareceu.');

        $this->assertCount(2, $chamado->fresh()->manifestacoes);

        // RF-SOL-14 — reescrever a própria versão depois de ler a da outra
        // não seria apuração.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/já apresentou sua versão/');

        $abrir->manifestar($chamado->fresh(), $this->ajudante, 'Na verdade foi assim.');
    }

    public function test_a_decisao_dispensa_a_manifestacao_das_partes(): void
    {
        $chamado = Chamado::where('solicitacao_id', $this->contestada()->id)->first();

        // RF-SOL-15 — quem se cala não trava a apuração da outra parte.
        $decidido = (new DecidirChamado())->executar(
            $chamado,
            $this->operador,
            Chamado::INCONCLUSIVO,
            'Sem elementos que permitam concluir em qualquer sentido.',
        );

        $this->assertSame(Chamado::DECIDIDO, $decidido->estado);
    }

    public function test_decisao_realizado_consome_o_ticket_e_registra_contra_quem_contestou(): void
    {
        $solicitacao = $this->contestada();
        $chamado = Chamado::where('solicitacao_id', $solicitacao->id)->first();

        (new DecidirChamado())->executar(
            $chamado,
            $this->operador,
            Chamado::REALIZADO,
            'O registro de comparecimento das duas partes confirma o atendimento.',
        );

        // RF-SOL-16
        $this->assertSame(Solicitacao::REALIZADA, $solicitacao->fresh()->estado);
        $this->assertSame(Ticket::CONSUMIDO, $solicitacao->ticket->fresh()->estado);

        $ocorrencia = Ocorrencia::where('contra_usuario_id', $this->solicitante->id)->first();
        $this->assertSame(Ocorrencia::CONTESTACAO_SEM_FUNDAMENTO, $ocorrencia->tipo);
    }

    public function test_decisao_nao_realizado_devolve_o_ticket_e_registra_contra_o_ajudante(): void
    {
        $solicitacao = $this->contestada();
        $chamado = Chamado::where('solicitacao_id', $solicitacao->id)->first();

        (new DecidirChamado())->executar(
            $chamado,
            $this->operador,
            Chamado::NAO_REALIZADO,
            'Não há registro de comparecimento do ajudante.',
        );

        // RF-SOL-17
        $this->assertSame(Solicitacao::NAO_REALIZADA, $solicitacao->fresh()->estado);

        $ticket = $solicitacao->ticket->fresh();
        $this->assertSame(Ticket::DISPONIVEL, $ticket->estado);
        $this->assertSame($this->solicitante->id, $ticket->proprietario_id);

        $ocorrencia = Ocorrencia::where('contra_usuario_id', $this->ajudante->id)->first();
        $this->assertSame(Ocorrencia::FAVOR_NAO_REALIZADO, $ocorrencia->tipo);
    }

    public function test_decisao_inconclusiva_devolve_o_ticket_sem_culpar_ninguem(): void
    {
        $solicitacao = $this->contestada();
        $chamado = Chamado::where('solicitacao_id', $solicitacao->id)->first();

        (new DecidirChamado())->executar(
            $chamado,
            $this->operador,
            Chamado::INCONCLUSIVO,
            'As duas versões são plausíveis e não há registro que as separe.',
        );

        // RF-SOL-18 — o inconclusivo favorece o portador sem atribuir culpa:
        // ticket emitido é direito adquirido e não se extingue por falta de
        // prova (02 §20).
        $this->assertSame(Solicitacao::INCONCLUSIVA, $solicitacao->fresh()->estado);
        $this->assertSame(Ticket::DISPONIVEL, $solicitacao->ticket->fresh()->estado);
        $this->assertSame(0, Ocorrencia::count());
    }

    public function test_a_decisao_do_chamado_exige_fundamento(): void
    {
        $chamado = Chamado::where('solicitacao_id', $this->contestada()->id)->first();

        // RF-SOL-19 — sem fundamento não há o que comunicar às partes.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/exige fundamento/');

        (new DecidirChamado())->executar($chamado, $this->operador, Chamado::REALIZADO, '  ');
    }

    public function test_o_chamado_nao_e_decidido_duas_vezes(): void
    {
        $chamado = Chamado::where('solicitacao_id', $this->contestada()->id)->first();
        $decidir = new DecidirChamado();

        $decidir->executar($chamado, $this->operador, Chamado::INCONCLUSIVO, 'Sem elementos.');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/já foi decidido/');

        $decidir->executar($chamado->fresh(), $this->operador, Chamado::REALIZADO, 'Mudei de ideia.');
    }

    public function test_a_decisao_entra_na_trilha_de_auditoria(): void
    {
        $chamado = Chamado::where('solicitacao_id', $this->contestada()->id)->first();

        (new DecidirChamado())->executar(
            $chamado,
            $this->operador,
            Chamado::INCONCLUSIVO,
            'Sem elementos que permitam concluir.',
        );

        // RF-ADM-10 — a auditoria é a contrapartida do poder da Operação.
        $registro = RegistroAuditoria::where('alvo_tipo', Chamado::class)->first();

        $this->assertSame($this->operador->id, $registro->operador_id);
        $this->assertSame('chamado_decidido:inconclusivo', $registro->acao);
        $this->assertNotNull($registro->motivo);
    }

    // -------------------------------------------------------------- Suspensão

    public function test_a_suspensao_do_usuario_impede_negociar(): void
    {
        $ana = $this->usuario('Ana Prado');
        [$daAna] = (new EmitirTickets())->executar($this->favorDe($ana), 1);

        (new AplicarSuspensao())->usuario(
            $ana,
            $this->operador,
            'Denúncias procedentes de cobrança por fora.',
        );

        $this->assertSame(Usuario::ESTADO_SUSPENSA, $ana->fresh()->estado_conta);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não está ativa/');

        (new EnviarProposta())->executar(
            $this->solicitante,
            $ana->fresh(),
            Composicao::de([$daAna]),
        );
    }

    public function test_a_suspensao_do_favor_nao_invalida_ticket_ja_emitido(): void
    {
        $favor = $this->favorDe($this->ajudante);
        [$ticket] = (new EmitirTickets())->executar($favor, 1);
        $ticket->transferirPara($this->solicitante, 'proposta_aceita');

        (new AplicarSuspensao())->favor(
            $favor->fresh(),
            $this->operador,
            'Categoria restrita pela curadoria.',
        );

        // 02 §20.3 — nem a suspensão extingue direito de terceiro que confiou
        // no que estava publicado.
        $this->assertSame(Ticket::DISPONIVEL, $ticket->fresh()->estado);
        $this->assertSame(Favor::ESTADO_DESATIVADO, $favor->fresh()->estado);
    }

    public function test_a_suspensao_exige_motivo(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/exige registro de motivo/');

        (new AplicarSuspensao())->usuario($this->ajudante, $this->operador, '  ');
    }

    public function test_encerrar_a_suspensao_preserva_o_registro(): void
    {
        $aplicar = new AplicarSuspensao();
        $suspensao = $aplicar->usuario($this->ajudante, $this->operador, 'Em apuração.');

        $aplicar->encerrar($suspensao, $this->operador, 'Apuração concluída sem irregularidade.');

        // O passado continua consultável; o presente volta ao normal.
        $this->assertNotNull($suspensao->fresh()->vigente_ate);
        $this->assertSame(Usuario::ESTADO_ATIVA, $this->ajudante->fresh()->estado_conta);
    }

    // ------------------------------------------------------- Termo de ciência

    public function test_o_termo_e_versionado_e_a_publicacao_encerra_a_anterior(): void
    {
        $gerir = new GerirTermoDeCiencia();

        $primeiro = $gerir->publicar('Primeira redação.', $this->operador);
        $segundo = $gerir->publicar('Segunda redação, revisada.', $this->operador);

        $this->assertSame(1, $primeiro->versao);
        $this->assertSame(2, $segundo->versao);

        // O texto de uma versão publicada nunca muda: quem aceitou a 1
        // aceitou aquele texto (RF-SEG-04).
        $this->assertNotNull($primeiro->fresh()->encerrado_em);
        $this->assertSame('Primeira redação.', $primeiro->fresh()->texto);
    }

    public function test_o_aceite_registra_qual_versao_foi_aceita(): void
    {
        $gerir = new GerirTermoDeCiencia();
        $gerir->publicar('Primeira redação.', $this->operador);
        $vigente = $gerir->publicar('Segunda redação.', $this->operador);

        $solicitacao = (new SolicitarFavor())->executar(
            $this->ticketDoSolicitante(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );

        $aceite = $gerir->aceitar($solicitacao, $this->solicitante);

        // RF-SEG-04 — sem a versão, o termo não sustenta a finalidade
        // probatória para a qual existe.
        $this->assertSame($vigente->id, $aceite->termo_ciencia_id);
        $this->assertTrue(GerirTermoDeCiencia::aceitouPara($solicitacao, $this->solicitante));
    }

    public function test_sem_termo_publicado_o_aceite_e_recusado(): void
    {
        $solicitacao = (new SolicitarFavor())->executar(
            $this->ticketDoSolicitante(),
            $this->solicitante,
            Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Não há termo de ciência publicado/');

        (new GerirTermoDeCiencia())->aceitar($solicitacao, $this->solicitante);
    }

    public function test_a_publicacao_do_termo_entra_na_trilha_de_auditoria(): void
    {
        (new GerirTermoDeCiencia())->publicar('Redação inicial.', $this->operador);

        $registro = RegistroAuditoria::where('acao', 'termo_de_ciencia_publicado')->first();

        $this->assertSame($this->operador->id, $registro->operador_id);
    }
}
