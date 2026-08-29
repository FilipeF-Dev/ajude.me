<?php

namespace Tests\Feature;

use App\Dominio\Consumo\ApurarRealizacao;
use App\Dominio\Consumo\CancelarSolicitacao;
use App\Dominio\Consumo\RegistrarComparecimento;
use App\Dominio\Consumo\ResponderSolicitacao;
use App\Dominio\Consumo\SolicitarFavor;
use App\Dominio\Ticket\EmitirTickets;
use App\Models\Categoria;
use App\Models\Favor;
use App\Models\JanelaDisponibilidade;
use App\Models\ManifestacaoRealizacao;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
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
 * As invariantes do contexto Consumo (05 §5.4).
 *
 * É a fatia em que o software encosta no mundo real: duas pessoas marcam um
 * encontro e depois divergem — ou não — sobre o que aconteceu.
 */
class ConsumoTest extends TestCase
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

    private function favor(array $atributos = []): Favor
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
            'usuario_id' => $this->ajudante->id,
            'proficiencia_id' => $proficiencia->id,
            'nivel' => ProficienciaUsuario::NIVEL_ESPECIALISTA,
            'declarada_em' => now(),
        ]);

        $favor = Favor::create(array_merge([
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
        ], $atributos));

        $favor->ativar();

        return $favor->fresh();
    }

    /**
     * Emite um ticket e o coloca na carteira do solicitante — é assim que ele
     * chega lá no mundo real, por troca.
     */
    private function ticketNaCarteiraDoSolicitante(?Favor $favor = null): Ticket
    {
        $favor ??= $this->favor();

        [$ticket] = (new EmitirTickets())->executar($favor, 1);
        $ticket->transferirPara($this->solicitante, 'proposta_aceita');

        return $ticket->fresh();
    }

    /** Uma quarta-feira às 10h, bem além da antecedência mínima. */
    private function proximaData(): Carbon
    {
        return Carbon::now()->addDays(10)->next(Carbon::WEDNESDAY)->setTime(10, 0);
    }

    private function solicitar(Ticket $ticket, ?Carbon $inicio = null): Solicitacao
    {
        return (new SolicitarFavor())->executar(
            $ticket,
            $this->solicitante,
            $inicio ?? $this->proximaData(),
        );
    }

    private function agendar(Solicitacao $solicitacao): Solicitacao
    {
        return (new ResponderSolicitacao())->agendar($solicitacao, $this->ajudante);
    }

    /** Leva a solicitação até a janela de contestação aberta. */
    private function ateAJanela(Solicitacao $solicitacao): Solicitacao
    {
        return (new ApurarRealizacao())->abrirJanela(
            $solicitacao,
            $solicitacao->fim_previsto->copy()->addMinute(),
        );
    }

    public function test_a_solicitacao_nao_reserva_o_ticket_antes_da_confirmacao(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante();
        $solicitacao = $this->solicitar($ticket);

        // RF-SOL-06 — só o agendamento reserva. Prender o direito por conta
        // de um pedido que ainda pode ser recusado seria antecipar o ônus.
        $this->assertSame(Solicitacao::SOLICITADA, $solicitacao->estado);
        $this->assertSame(Ticket::DISPONIVEL, $ticket->fresh()->estado);

        $this->agendar($solicitacao);

        $this->assertSame(Ticket::RESERVADO, $ticket->fresh()->estado);
    }

    public function test_o_fim_previsto_deriva_do_tempo_estimado(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante($this->favor(['tempo_estimado_minutos' => 90]));
        $inicio = $this->proximaData();

        $solicitacao = $this->solicitar($ticket, $inicio);

        $this->assertSame(90, (int) $solicitacao->inicio_previsto->diffInMinutes($solicitacao->fim_previsto));
    }

    public function test_recusa_solicitacao_sem_a_antecedencia_minima(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante($this->favor(['antecedencia_minima_dias' => 5]));

        // RF-SOL-03 e I18 — condição publicada é condição, não sugestão.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/5 dia\(s\) de antecedência/');

        $this->solicitar($ticket, Carbon::now()->addDays(2)->setTime(10, 0));
    }

    public function test_recusa_horario_fora_das_janelas_publicadas(): void
    {
        $favor = $this->favor();
        JanelaDisponibilidade::create([
            'favor_id' => $favor->id,
            'dia_semana' => 3, // quarta
            'hora_inicio' => '08:00:00',
            'hora_fim' => '12:00:00',
        ]);

        $ticket = $this->ticketNaCarteiraDoSolicitante($favor->fresh());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/fora das janelas de atendimento/');

        // Quarta às 18h: dia certo, hora errada.
        $this->solicitar($ticket, $this->proximaData()->setTime(18, 0));
    }

    public function test_o_atendimento_inteiro_precisa_caber_na_janela(): void
    {
        $favor = $this->favor(['tempo_estimado_minutos' => 120]);
        JanelaDisponibilidade::create([
            'favor_id' => $favor->id,
            'dia_semana' => 3,
            'hora_inicio' => '08:00:00',
            'hora_fim' => '12:00:00',
        ]);

        $ticket = $this->ticketNaCarteiraDoSolicitante($favor->fresh());

        // Começar às 11h com 2h de duração empurraria o ajudante para além
        // do que ele publicou.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/fora das janelas de atendimento/');

        $this->solicitar($ticket, $this->proximaData()->setTime(11, 0));
    }

    public function test_recusa_agendar_para_depois_da_validade_do_ticket(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante($this->favor(['validade_ticket_dias' => 20]));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/escolha uma data anterior/');

        $this->solicitar($ticket, Carbon::now()->addDays(40)->setTime(10, 0));
    }

    public function test_ninguem_solicita_o_proprio_favor(): void
    {
        $favor = $this->favor();
        [$ticket] = (new EmitirTickets())->executar($favor, 1);

        // O ticket está na carteira de quem o emitiu; consumi-lo ali não move
        // ajuda nenhuma de lugar.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Não se solicita a si mesmo/');

        (new SolicitarFavor())->executar($ticket, $this->ajudante, $this->proximaData());
    }

    public function test_favor_desativado_ainda_atende_ticket_ja_emitido(): void
    {
        $favor = $this->favor();
        $ticket = $this->ticketNaCarteiraDoSolicitante($favor);

        $favor->fresh()->desativar();

        // RF-FAV-12 — desativar impede novas emissões e não invalida direito
        // já adquirido.
        $solicitacao = $this->solicitar($ticket->fresh());

        $this->assertSame(Solicitacao::SOLICITADA, $solicitacao->estado);
    }

    public function test_um_ticket_sustenta_uma_solicitacao_viva_por_vez(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante();
        $this->solicitar($ticket);

        // 02 §5.2 — 1 ticket = 1 realização.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/já tem uma solicitação em andamento/');

        $this->solicitar($ticket->fresh(), $this->proximaData()->addWeek());
    }

    public function test_solicitacao_recusada_libera_o_ticket_para_novo_pedido(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante();
        $primeira = $this->solicitar($ticket);

        (new ResponderSolicitacao())->recusar(
            $primeira,
            $this->ajudante,
            'A data cai fora da minha janela de atendimento.',
        );

        $segunda = $this->solicitar($ticket->fresh(), $this->proximaData()->addWeek());

        $this->assertSame(Solicitacao::SOLICITADA, $segunda->estado);
    }

    public function test_a_recusa_exige_apontar_a_condicao_violada(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante();
        $solicitacao = $this->solicitar($ticket);

        // RF-SOL-05 — recusar não é livre arbítrio.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/exige apontar a condição/');

        (new ResponderSolicitacao())->recusar($solicitacao, $this->ajudante, '   ');
    }

    public function test_o_ajudante_nao_tem_dois_atendimentos_sobrepostos(): void
    {
        $inicio = $this->proximaData();

        $primeira = $this->solicitar($this->ticketNaCarteiraDoSolicitante(), $inicio);
        $this->agendar($primeira);

        $segunda = $this->solicitar(
            $this->ticketNaCarteiraDoSolicitante(),
            $inicio->copy()->addMinutes(30),
        );

        // I19 — o mesmo corpo não atende duas pessoas ao mesmo tempo.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/já tem atendimento agendado/');

        $this->agendar($segunda);
    }

    public function test_atendimentos_encostados_nao_se_sobrepoem(): void
    {
        $inicio = $this->proximaData();

        $primeira = $this->solicitar($this->ticketNaCarteiraDoSolicitante(), $inicio);
        $this->agendar($primeira);

        // O intervalo é semiaberto: terminar às 11h e começar às 11h convive.
        $segunda = $this->solicitar(
            $this->ticketNaCarteiraDoSolicitante(),
            $inicio->copy()->addHour(),
        );

        $this->assertSame(Solicitacao::AGENDADA, $this->agendar($segunda)->estado);
    }

    public function test_o_comparecimento_e_registrado_por_cada_parte(): void
    {
        $solicitacao = $this->agendar($this->solicitar($this->ticketNaCarteiraDoSolicitante()));
        $registro = new RegistrarComparecimento();

        $registro->inicio($solicitacao, $this->ajudante);
        $registro->inicio($solicitacao, $this->solicitante);

        // RF-SEG-08 — entre o início e o fim, o atendimento está em curso.
        $this->assertTrue($solicitacao->fresh()->atendimentoEmCurso());

        $registro->fim($solicitacao, $this->ajudante);
        $registro->fim($solicitacao, $this->solicitante);

        $this->assertFalse($solicitacao->fresh()->atendimentoEmCurso());
        $this->assertCount(4, $solicitacao->fresh()->comparecimentos);
    }

    public function test_o_registro_de_comparecimento_nao_se_repete(): void
    {
        $solicitacao = $this->agendar($this->solicitar($this->ticketNaCarteiraDoSolicitante()));
        $registro = new RegistrarComparecimento();

        $registro->inicio($solicitacao, $this->ajudante);

        // Evidência que se reescreve não é evidência.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/já foi feito e não se repete/');

        $registro->inicio($solicitacao, $this->ajudante);
    }

    public function test_a_confirmacao_das_duas_partes_encerra_a_janela_na_hora(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante();
        $solicitacao = $this->ateAJanela($this->agendar($this->solicitar($ticket)));

        $apurar = new ApurarRealizacao();
        $momento = $solicitacao->fim_previsto->copy()->addHours(2);

        $apurar->manifestar($solicitacao, $this->ajudante, ManifestacaoRealizacao::CONFIRMA, $momento);
        $solicitacao = $apurar->manifestar(
            $solicitacao->fresh(),
            $this->solicitante,
            ManifestacaoRealizacao::CONFIRMA,
            $momento,
        );

        // RF-SOL-09 — sem esperar o prazo.
        $this->assertSame(Solicitacao::REALIZADA, $solicitacao->estado);
        $this->assertSame(Ticket::CONSUMIDO, $ticket->fresh()->estado);
    }

    public function test_o_silencio_das_duas_partes_confirma(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante();
        $solicitacao = $this->ateAJanela($this->agendar($this->solicitar($ticket)));

        $depoisDaJanela = $solicitacao->janela_contestacao_ate->copy()->addMinute();

        // RF-SOL-10 — o silêncio confirma. Um ciclo aberto para sempre
        // prenderia o ticket e travaria a reputação dos dois.
        $solicitacao = (new ApurarRealizacao())->apurarConfirmacaoTacita($solicitacao, $depoisDaJanela);

        $this->assertSame(Solicitacao::REALIZADA, $solicitacao->estado);
        $this->assertSame(Ticket::CONSUMIDO, $ticket->fresh()->estado);
    }

    public function test_a_janela_nao_encerra_antes_do_prazo(): void
    {
        $solicitacao = $this->ateAJanela($this->agendar($this->solicitar($this->ticketNaCarteiraDoSolicitante())));

        // 05 §7.5 — a confirmação tácita é apurada após o limite, nunca
        // antecipada.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/ainda está aberta/');

        (new ApurarRealizacao())->apurarConfirmacaoTacita(
            $solicitacao,
            $solicitacao->janela_contestacao_ate->copy()->subHour(),
        );
    }

    public function test_a_janela_usa_o_prazo_vigente_de_72_horas(): void
    {
        $solicitacao = $this->ateAJanela($this->agendar($this->solicitar($this->ticketNaCarteiraDoSolicitante())));

        $this->assertSame(
            72,
            (int) $solicitacao->fim_previsto->diffInHours($solicitacao->janela_contestacao_ate)
        );
    }

    public function test_a_contestacao_leva_a_analise_e_suspende_a_validade(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante();
        $solicitacao = $this->ateAJanela($this->agendar($this->solicitar($ticket)));

        $solicitacao = (new ApurarRealizacao())->manifestar(
            $solicitacao,
            $this->solicitante,
            ManifestacaoRealizacao::CONTESTA,
            $solicitacao->fim_previsto->copy()->addHour(),
        );

        $this->assertSame(Solicitacao::EM_ANALISE, $solicitacao->estado);

        // I7 — a demora da plataforma não extingue direito em disputa.
        $ticket = $ticket->fresh();
        $this->assertSame(Ticket::EM_ANALISE, $ticket->estado);
        $this->assertNotNull($ticket->validade_suspensa_em);
    }

    public function test_uma_parte_manifesta_uma_vez_so(): void
    {
        $solicitacao = $this->ateAJanela($this->agendar($this->solicitar($this->ticketNaCarteiraDoSolicitante())));
        $apurar = new ApurarRealizacao();
        $momento = $solicitacao->fim_previsto->copy()->addHour();

        $apurar->manifestar($solicitacao, $this->ajudante, ManifestacaoRealizacao::CONFIRMA, $momento);

        // I22 — quem se manifestou não muda de ideia por outro registro.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/já se manifestou/');

        $apurar->manifestar($solicitacao->fresh(), $this->ajudante, ManifestacaoRealizacao::CONTESTA, $momento);
    }

    public function test_a_contestacao_no_limite_da_janela_ainda_vale(): void
    {
        $solicitacao = $this->ateAJanela($this->agendar($this->solicitar($this->ticketNaCarteiraDoSolicitante())));

        // 05 §7.5 — a contestação registrada dentro da janela prevalece,
        // inclusive no instante exato do vencimento.
        $solicitacao = (new ApurarRealizacao())->manifestar(
            $solicitacao,
            $this->solicitante,
            ManifestacaoRealizacao::CONTESTA,
            $solicitacao->janela_contestacao_ate->copy(),
        );

        $this->assertSame(Solicitacao::EM_ANALISE, $solicitacao->estado);
    }

    public function test_o_cancelamento_pelo_ajudante_exige_justificativa(): void
    {
        $solicitacao = $this->agendar($this->solicitar($this->ticketNaCarteiraDoSolicitante()));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/exige justificativa/');

        (new CancelarSolicitacao())->peloAjudante($solicitacao, $this->ajudante, '  ');
    }

    public function test_o_cancelamento_devolve_o_ticket_a_carteira(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante();
        $solicitacao = $this->agendar($this->solicitar($ticket));

        $solicitacao = (new CancelarSolicitacao())->peloAjudante(
            $solicitacao,
            $this->ajudante,
            'Precisei viajar por motivo de família.',
        );

        // RF-CAN-05 — o direito não se extingue porque o encontro não houve.
        $this->assertSame(Solicitacao::CANCELADA_AJUDANTE, $solicitacao->estado);
        $this->assertSame(Ticket::DISPONIVEL, $ticket->fresh()->estado);
        $this->assertSame($this->solicitante->id, $ticket->fresh()->proprietario_id);
    }

    public function test_o_solicitante_cancela_sem_justificativa_e_sem_bloqueio(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante();
        $solicitacao = $this->agendar($this->solicitar($ticket));

        // RF-CAN-07 — o sistema não bloqueia. Bloquear não faz ninguém
        // comparecer; converte aviso em ausência sem aviso.
        $solicitacao = (new CancelarSolicitacao())->peloSolicitante($solicitacao, $this->solicitante);

        $this->assertSame(Solicitacao::CANCELADA_SOLICITANTE, $solicitacao->estado);
        $this->assertSame(Ticket::DISPONIVEL, $ticket->fresh()->estado);
    }

    public function test_a_antecedencia_de_24h_separa_o_cancelamento_com_ocorrencia(): void
    {
        $solicitacao = $this->agendar($this->solicitar($this->ticketNaCarteiraDoSolicitante()));
        $cancelar = new CancelarSolicitacao();

        // RF-CAN-16 — a resposta precisa existir antes de confirmar.
        $this->assertFalse($cancelar->geraOcorrencia(
            $solicitacao,
            $this->solicitante,
            $solicitacao->inicio_previsto->copy()->subHours(48),
        ));

        // RF-CAN-15
        $this->assertTrue($cancelar->geraOcorrencia(
            $solicitacao,
            $this->solicitante,
            $solicitacao->inicio_previsto->copy()->subHours(3),
        ));

        // RF-CAN-06 — o do ajudante gera ocorrência em qualquer prazo.
        $this->assertTrue($cancelar->geraOcorrencia(
            $solicitacao,
            $this->ajudante,
            $solicitacao->inicio_previsto->copy()->subMonth(),
        ));
    }

    public function test_o_solicitante_desiste_antes_da_confirmacao(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante();
        $solicitacao = $this->solicitar($ticket);

        // RF-CAN-07 — "a qualquer momento" inclui antes de o ajudante
        // confirmar. O ticket nunca chegou a ser reservado.
        $solicitacao = (new CancelarSolicitacao())->peloSolicitante($solicitacao, $this->solicitante);

        $this->assertSame(Solicitacao::CANCELADA_SOLICITANTE, $solicitacao->estado);
        $this->assertSame(Ticket::DISPONIVEL, $ticket->fresh()->estado);
    }

    public function test_toda_transicao_da_solicitacao_deixa_rastro(): void
    {
        $solicitacao = $this->ateAJanela($this->agendar($this->solicitar($this->ticketNaCarteiraDoSolicitante())));

        // P2 — nem o nascimento fica sem registro.
        $this->assertSame(
            ['solicitacao_enviada', 'solicitacao_agendada', 'janela_de_contestacao_aberta'],
            $solicitacao->transicoes()->pluck('evento')->all(),
        );
    }

    public function test_recusa_transicao_fora_da_maquina_de_estados(): void
    {
        $solicitacao = $this->solicitar($this->ticketNaCarteiraDoSolicitante());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Transição inválida/');

        // Não se realiza um favor que nunca foi agendado.
        $solicitacao->transicionar(Solicitacao::REALIZADA, 'atalho_indevido');
    }

    public function test_favor_presencial_exige_localizacao_nas_duas_contas(): void
    {
        $ticket = $this->ticketNaCarteiraDoSolicitante($this->favor([
            'modalidade' => Favor::MODALIDADE_PRESENCIAL,
            'area_atendimento_km' => 5,
        ]));

        // Seguir sem as coordenadas seria fingir que a área foi conferida.
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/exige endereço com localização/');

        $this->solicitar($ticket);
    }

    public function test_recusa_favor_presencial_fora_da_area_de_atendimento(): void
    {
        $this->enderecoCom($this->ajudante, -23.5505, -46.6333);   // Sé, São Paulo
        $this->enderecoCom($this->solicitante, -23.9608, -46.3336); // Santos

        $ticket = $this->ticketNaCarteiraDoSolicitante($this->favor([
            'modalidade' => Favor::MODALIDADE_PRESENCIAL,
            'area_atendimento_km' => 5,
        ]));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/atende até 5 km/');

        $this->solicitar($ticket);
    }

    public function test_aceita_favor_presencial_dentro_da_area(): void
    {
        $this->enderecoCom($this->ajudante, -23.5505, -46.6333);
        $this->enderecoCom($this->solicitante, -23.5610, -46.6560); // ~2,5 km

        $ticket = $this->ticketNaCarteiraDoSolicitante($this->favor([
            'modalidade' => Favor::MODALIDADE_PRESENCIAL,
            'area_atendimento_km' => 5,
        ]));

        $this->assertSame(Solicitacao::SOLICITADA, $this->solicitar($ticket)->estado);
    }

    private function enderecoCom(Usuario $usuario, float $latitude, float $longitude): void
    {
        \App\Models\Endereco::create([
            'usuario_id' => $usuario->id,
            'cep' => '01001000',
            'logradouro' => 'Praça da Sé',
            'numero' => '1',
            'bairro' => 'Sé',
            'municipio' => 'São Paulo',
            'uf' => 'SP',
        ]);

        \Illuminate\Support\Facades\DB::update(
            'UPDATE enderecos SET coordenada = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
             WHERE usuario_id = ?',
            [$longitude, $latitude, $usuario->id]
        );
    }
}
