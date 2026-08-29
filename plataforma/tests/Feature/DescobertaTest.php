<?php

namespace Tests\Feature;

use App\Dominio\Descoberta\ApagarTextoDasBuscas;
use App\Dominio\Descoberta\AvisarProficienciaDisponivel;
use App\Dominio\Descoberta\BuscarFavores;
use App\Dominio\Descoberta\BuscarPorNecessidade;
use App\Dominio\Descoberta\ReconhecerNecessidade;
use App\Dominio\Moderacao\GerirBloqueio;
use App\Dominio\Ticket\EmitirTickets;
use App\Models\Categoria;
use App\Models\Endereco;
use App\Models\Favor;
use App\Models\Notificacao;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Subcategoria;
use App\Models\TermoPopular;
use App\Models\Ticket;
use App\Models\Usuario;
use Database\Seeders\ParametrosNegocioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

/**
 * O contexto Descoberta (02 §3.4, §3.5, §18.3).
 *
 * > **O usuário informa o sintoma. O sistema encontra a competência.**
 *
 * E o que ele digita é o dado mais sensível do produto — por isso metade
 * destes testes é sobre o que o sistema **não** faz com ele.
 */
class DescobertaTest extends TestCase
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

    private function proficiencia(string $nome, array $termos = []): Proficiencia
    {
        $categoria = Categoria::create(['nome' => 'Casa e reparos '.uniqid()]);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'nome' => 'Reparos']);
        $proficiencia = Proficiencia::create(['subcategoria_id' => $subcategoria->id, 'nome' => $nome]);

        foreach ($termos as $termo) {
            TermoPopular::create(['proficiencia_id' => $proficiencia->id, 'termo' => $termo]);
        }

        return $proficiencia;
    }

    private function favorCom(Proficiencia $proficiencia, ?Usuario $de = null, array $atributos = []): Favor
    {
        $de ??= $this->ajudante;

        $proficienciaUsuario = ProficienciaUsuario::create([
            'usuario_id' => $de->id,
            'proficiencia_id' => $proficiencia->id,
            'nivel' => ProficienciaUsuario::NIVEL_ESPECIALISTA,
            'declarada_em' => now(),
        ]);

        $favor = Favor::create(array_merge([
            'proficiencia_usuario_id' => $proficienciaUsuario->id,
            'titulo' => 'Reparos elétricos em geral',
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

    private function comTicketDisponivel(Favor $favor): Favor
    {
        (new EmitirTickets())->executar($favor, 1);

        return $favor->fresh();
    }

    private function enderecoDe(Usuario $usuario, string $municipio = 'São Paulo', float $lat = -23.55, float $lon = -46.63): void
    {
        Endereco::create([
            'usuario_id' => $usuario->id,
            'cep' => '01001000',
            'logradouro' => 'Praça da Sé',
            'numero' => '1',
            'bairro' => 'Sé',
            'municipio' => $municipio,
            'uf' => 'SP',
        ]);

        DB::update(
            'UPDATE enderecos SET coordenada = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
             WHERE usuario_id = ?',
            [$lon, $lat, $usuario->id]
        );
    }

    // --------------------------------------------------------- Reconhecimento

    public function test_reconhece_a_competencia_a_partir_do_sintoma(): void
    {
        $eletrica = $this->proficiencia('Elétrica residencial', ['chuveiro', 'disjuntor', 'tomada']);

        // 02 §3.4 — quem digita não sabe que precisa de um eletricista; sabe
        // que o chuveiro não esquenta.
        $reconhecidas = (new ReconhecerNecessidade())->executar('trocar a resistência do chuveiro');

        $this->assertSame([$eletrica->id], $reconhecidas->pluck('id')->all());
    }

    public function test_o_reconhecimento_ignora_acento_e_maiuscula(): void
    {
        $this->proficiencia('Elétrica residencial', ['fiação']);

        $reconhecidas = (new ReconhecerNecessidade())->executar('FIACAO velha em casa');

        $this->assertCount(1, $reconhecidas);
    }

    public function test_a_proficiencia_com_mais_termos_em_comum_vem_primeiro(): void
    {
        $eletrica = $this->proficiencia('Elétrica residencial', ['chuveiro', 'resistencia']);
        $this->proficiencia('Encanamento', ['chuveiro']);

        $reconhecidas = (new ReconhecerNecessidade())->executar('resistencia do chuveiro');

        // Mais termos acionados é sinal mais forte, e a interpretação mais
        // provável precisa aparecer em primeiro lugar (RF-BUS-18).
        $this->assertSame($eletrica->id, $reconhecidas->first()->id);
        $this->assertCount(2, $reconhecidas);
    }

    public function test_necessidade_sem_vocabulario_nao_reconhece_nada(): void
    {
        $this->proficiencia('Elétrica residencial', ['chuveiro']);

        // Falha de vocabulário, não de algoritmo: conserta-se com um termo
        // novo (RF-ADM-17).
        $this->assertCount(0, (new ReconhecerNecessidade())->executar('preciso de alguém'));
    }

    // ------------------------------------------------------------------ Busca

    public function test_a_busca_devolve_a_interpretacao_junto_com_o_resultado(): void
    {
        $eletrica = $this->proficiencia('Elétrica residencial', ['chuveiro']);
        $this->comTicketDisponivel($this->favorCom($eletrica));

        $resultado = (new BuscarPorNecessidade())->executar(
            'o chuveiro parou de esquentar',
            $this->solicitante,
        );

        // RF-BUS-18 — resultado inexplicado parece defeito.
        $this->assertSame([$eletrica->id], $resultado['proficiencias']->pluck('id')->all());
        $this->assertCount(1, $resultado['favores']);
        $this->assertTrue($resultado['busca']->houve_resultado);
    }

    public function test_o_usuario_descarta_uma_proficiencia_reconhecida_por_engano(): void
    {
        $eletrica = $this->proficiencia('Elétrica residencial', ['chuveiro']);
        $encanamento = $this->proficiencia('Encanamento', ['chuveiro']);

        $this->comTicketDisponivel($this->favorCom($eletrica));
        $this->comTicketDisponivel($this->favorCom($encanamento));

        $resultado = (new BuscarPorNecessidade())->executar(
            'chuveiro',
            $this->solicitante,
            proficienciasDescartadas: [$encanamento->id],
        );

        // RF-BUS-19 — o descarte refina o resultado...
        $this->assertCount(1, $resultado['favores']);

        // ...e fica registrado, para que o erro de vocabulário seja
        // observável pela Operação.
        $descartada = $resultado['busca']->reconhecidas()
            ->where('proficiencia_id', $encanamento->id)
            ->first();

        $this->assertTrue($descartada->descartada);
    }

    public function test_busca_sem_resultado_e_registrada_como_lacuna(): void
    {
        $eletrica = $this->proficiencia('Elétrica residencial', ['chuveiro']);
        $this->enderecoDe($this->solicitante);

        // Existe a proficiência, mas ninguém oferece: é o principal sinal de
        // demanda da plataforma (02 §3.5).
        $resultado = (new BuscarPorNecessidade())->executar('chuveiro queimado', $this->solicitante);

        $this->assertFalse($resultado['busca']->houve_resultado);
        $this->assertCount(0, $resultado['favores']);

        $lacunas = (new BuscarPorNecessidade())->lacunasDeCobertura();

        $this->assertSame('Elétrica residencial', $lacunas[0]['proficiencia']);
        $this->assertSame('São Paulo', $lacunas[0]['municipio']);
        $this->assertSame(1, $lacunas[0]['buscas']);
    }

    public function test_a_busca_exige_alguma_descricao(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Descreva o que você precisa/');

        (new BuscarPorNecessidade())->executar('   ', $this->solicitante);
    }

    // ------------------------------------------------------------------- Feed

    public function test_o_feed_oculta_favor_sem_ticket_disponivel(): void
    {
        $eletrica = $this->proficiencia('Elétrica residencial');
        $this->favorCom($eletrica); // ativo, mas sem emitir ticket

        // RF-BUS-10 — mostrar favor sem ticket seria oferecer o que não se
        // pode dar.
        $this->assertCount(0, (new BuscarFavores())->executar($this->solicitante));
    }

    public function test_o_feed_oculta_favor_desativado(): void
    {
        $favor = $this->comTicketDisponivel($this->favorCom($this->proficiencia('Elétrica residencial')));
        $favor->desativar();

        $this->assertCount(0, (new BuscarFavores())->executar($this->solicitante));
    }

    public function test_o_feed_oculta_favor_de_quem_bloqueou_e_de_quem_foi_bloqueado(): void
    {
        $favor = $this->comTicketDisponivel($this->favorCom($this->proficiencia('Elétrica residencial')));
        $busca = new BuscarFavores();

        $this->assertCount(1, $busca->executar($this->solicitante));

        // RF-BUS-11 — o efeito do bloqueio é mútuo, ainda que o registro seja
        // direcional. Aqui quem bloqueia é o ajudante, e some para os dois.
        (new GerirBloqueio())->bloquear($this->ajudante, $this->solicitante);

        $this->assertCount(0, $busca->executar($this->solicitante));
    }

    public function test_o_feed_nao_mostra_a_propria_oferta(): void
    {
        $this->comTicketDisponivel($this->favorCom($this->proficiencia('Elétrica residencial')));

        $this->assertCount(0, (new BuscarFavores())->executar($this->ajudante));
    }

    public function test_o_visitante_sem_conta_ve_o_feed(): void
    {
        $this->comTicketDisponivel($this->favorCom($this->proficiencia('Elétrica residencial')));

        // RF-BUS-12 — quem ainda não entrou consegue ver o que existe.
        $this->assertCount(1, (new BuscarFavores())->executar(null));
    }

    public function test_o_feed_filtra_por_modalidade_e_nivel(): void
    {
        $this->comTicketDisponivel($this->favorCom($this->proficiencia('Elétrica residencial')));
        $busca = new BuscarFavores();

        // RF-BUS-05 e RF-BUS-06
        $this->assertCount(1, $busca->executar($this->solicitante, modalidade: Favor::MODALIDADE_REMOTO));
        $this->assertCount(0, $busca->executar($this->solicitante, modalidade: Favor::MODALIDADE_PRESENCIAL));
        $this->assertCount(
            1,
            $busca->executar($this->solicitante, nivel: ProficienciaUsuario::NIVEL_ESPECIALISTA)
        );
        $this->assertCount(
            0,
            $busca->executar($this->solicitante, nivel: ProficienciaUsuario::NIVEL_BASICO)
        );
    }

    public function test_o_feed_ordena_por_proximidade(): void
    {
        $perto = $this->usuario('Vizinha Rita');
        $longe = $this->usuario('Distante Caio');

        $this->enderecoDe($this->solicitante, lat: -23.55, lon: -46.63);
        $this->enderecoDe($perto, lat: -23.56, lon: -46.65);          // ~2 km
        $this->enderecoDe($longe, 'Santos', lat: -23.96, lon: -46.33); // ~70 km

        $this->comTicketDisponivel($this->favorCom($this->proficiencia('Elétrica'), $longe));
        $this->comTicketDisponivel($this->favorCom($this->proficiencia('Encanamento'), $perto));

        // 06 §2 — uma rede de troca depende de densidade geográfica, então
        // perto importa mais que novo.
        $resultado = (new BuscarFavores())->executar($this->solicitante);

        $this->assertSame($perto->id, $resultado->first()->proficienciaUsuario->usuario_id);
        $this->assertCount(2, $resultado);
    }

    public function test_o_raio_limita_a_area_de_busca(): void
    {
        $longe = $this->usuario('Distante Caio');

        $this->enderecoDe($this->solicitante, lat: -23.55, lon: -46.63);
        $this->enderecoDe($longe, 'Santos', lat: -23.96, lon: -46.33);

        $this->comTicketDisponivel($this->favorCom($this->proficiencia('Elétrica'), $longe));

        $busca = new BuscarFavores();

        // RF-BUS-24 — ampliar a área é aumentar o raio, não trocar de busca.
        $this->assertCount(0, $busca->executar($this->solicitante, raioKm: 10));
        $this->assertCount(1, $busca->executar($this->solicitante, raioKm: 100));
    }

    // ---------------------------------------------------------- Fila de aviso

    public function test_o_aviso_vincula_se_a_proficiencia_e_nao_ao_texto(): void
    {
        $eletrica = $this->proficiencia('Elétrica residencial', ['chuveiro']);
        $this->enderecoDe($this->solicitante);

        $aviso = (new BuscarPorNecessidade())->pedirAviso($this->solicitante, $eletrica);

        // RF-BUS-29 — a pessoa aguarda "elétrica residencial no seu
        // município", não "o chuveiro que ela disse que queimou".
        $this->assertSame($eletrica->id, $aviso->proficiencia_id);
        $this->assertSame('São Paulo', $aviso->municipio);
        $this->assertArrayNotHasKey('texto', $aviso->getAttributes());
    }

    public function test_pedir_aviso_de_novo_nao_duplica_a_fila(): void
    {
        $eletrica = $this->proficiencia('Elétrica residencial');
        $buscar = new BuscarPorNecessidade();

        $primeiro = $buscar->pedirAviso($this->solicitante, $eletrica);
        $segundo = $buscar->pedirAviso($this->solicitante, $eletrica);

        $this->assertSame($primeiro->id, $segundo->id);
    }

    public function test_o_aviso_e_cumprido_quando_surge_ticket_na_regiao(): void
    {
        $eletrica = $this->proficiencia('Elétrica residencial');
        $this->enderecoDe($this->solicitante);
        $this->enderecoDe($this->ajudante);

        $aviso = (new BuscarPorNecessidade())->pedirAviso($this->solicitante, $eletrica);

        // Nada ainda: ninguém oferece.
        $this->assertSame(0, (new AvisarProficienciaDisponivel())->executar());

        $this->comTicketDisponivel($this->favorCom($eletrica));

        // RF-BUS-23 e RF-NOT-18
        $this->assertSame(1, (new AvisarProficienciaDisponivel())->executar());
        $this->assertNotNull($aviso->fresh()->atendido_em);

        $notificacao = Notificacao::where('destinatario_id', $this->solicitante->id)->first();
        $this->assertSame(Notificacao::PROFICIENCIA_AGUARDADA_DISPONIVEL, $notificacao->tipo);
    }

    public function test_ninguem_e_avisado_de_que_outra_pessoa_procurou_algo(): void
    {
        $eletrica = $this->proficiencia('Elétrica residencial', ['chuveiro']);
        $this->comTicketDisponivel($this->favorCom($eletrica));

        (new BuscarPorNecessidade())->executar('chuveiro queimado', $this->solicitante);

        // RF-BUS-27 — exclui, por decisão, o recurso aparentemente útil de
        // "alguém perto precisa disso".
        $this->assertSame(0, Notificacao::where('destinatario_id', $this->ajudante->id)->count());
    }

    // -------------------------------------------------------------- Retenção

    public function test_o_texto_da_busca_e_apagado_aos_noventa_dias(): void
    {
        $this->proficiencia('Elétrica residencial', ['chuveiro']);

        $busca = (new BuscarPorNecessidade())->executar(
            'alguém para dar banho na minha mãe acamada',
            $this->solicitante,
            momento: Carbon::now()->subDays(91),
        )['busca'];

        $this->assertSame(1, (new ApagarTextoDasBuscas())->executar());

        // 05 §11.5 — cumprida a função de corrigir o vocabulário, o texto
        // vira passivo sem contrapartida.
        $busca = $busca->fresh();
        $this->assertNull($busca->texto);
        $this->assertTrue($busca->textoFoiApagado());
    }

    public function test_apagar_o_texto_preserva_o_agregado(): void
    {
        $eletrica = $this->proficiencia('Elétrica residencial', ['chuveiro']);
        $this->enderecoDe($this->solicitante);

        (new BuscarPorNecessidade())->executar(
            'chuveiro queimado',
            $this->solicitante,
            momento: Carbon::now()->subDays(91),
        );

        (new ApagarTextoDasBuscas())->executar();

        // O mapa de cobertura não precisa do texto para existir.
        $lacunas = (new BuscarPorNecessidade())->lacunasDeCobertura();

        $this->assertSame('Elétrica residencial', $lacunas[0]['proficiencia']);
        $this->assertSame(1, $lacunas[0]['buscas']);
    }

    public function test_o_texto_recente_nao_e_apagado(): void
    {
        $this->proficiencia('Elétrica residencial', ['chuveiro']);

        (new BuscarPorNecessidade())->executar('chuveiro queimado', $this->solicitante);

        $this->assertSame(0, (new ApagarTextoDasBuscas())->executar());
    }
}
