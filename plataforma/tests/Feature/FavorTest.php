<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Favor;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Subcategoria;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * As invariantes do contexto Oferta (05 §5.2).
 *
 * Rodam contra PostgreSQL, não SQLite: metade destas garantias é restrição
 * de banco, e testá-las contra outro motor validaria um sistema que não é o
 * nosso.
 */
class FavorTest extends TestCase
{
    use RefreshDatabase;

    private function proficienciaDeclarada(
        string $nivel = ProficienciaUsuario::NIVEL_AVANCADO
    ): ProficienciaUsuario {
        $usuario = Usuario::create([
            'nome' => 'Rafael Menezes',
            'email' => 'rafael'.uniqid().'@exemplo.br',
            'telefone' => '11988420000',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'senha' => 'segredo123',
        ]);

        $categoria = Categoria::create(['nome' => 'Casa e reparos '.uniqid()]);
        $subcategoria = Subcategoria::create([
            'categoria_id' => $categoria->id,
            'nome' => 'Elétrica',
        ]);
        $proficiencia = Proficiencia::create([
            'subcategoria_id' => $subcategoria->id,
            'nome' => 'Elétrica residencial',
        ]);

        return ProficienciaUsuario::create([
            'usuario_id' => $usuario->id,
            'proficiencia_id' => $proficiencia->id,
            'nivel' => $nivel,
            'declarada_em' => now(),
        ]);
    }

    private function favorDe(ProficienciaUsuario $proficienciaUsuario, array $atributos = []): Favor
    {
        return Favor::create(array_merge([
            'proficiencia_usuario_id' => $proficienciaUsuario->id,
            'titulo' => 'Instalações e reparos elétricos',
            'descricao' => 'Troco resistência de chuveiro, tomada e disjuntor.',
            'tempo_estimado_minutos' => 40,
            'modalidade' => Favor::MODALIDADE_PRESENCIAL,
            'area_atendimento_km' => 3,
            'antecedencia_minima_dias' => 2,
            'validade_ticket_dias' => 365,
            'maximo_circulacao' => 3,
            'trocavel' => true,
        ], $atributos));
    }

    public function test_descreve_o_favor_por_tempo_e_nivel_sem_preco(): void
    {
        $favor = $this->favorDe(
            $this->proficienciaDeclarada(ProficienciaUsuario::NIVEL_ESPECIALISTA)
        );

        $this->assertSame('≈ 40min · Especialista', $favor->descritor());
    }

    public function test_recusa_alterar_condicao_publicada_depois_do_congelamento(): void
    {
        $favor = $this->favorDe($this->proficienciaDeclarada());
        $favor->congelar();

        $favor->titulo = 'Outro título';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não pode mudar/');

        $favor->save();
    }

    public function test_permite_desativar_mesmo_depois_do_congelamento(): void
    {
        $favor = $this->favorDe($this->proficienciaDeclarada());
        $favor->congelar();

        $favor->desativar();

        $this->assertSame(Favor::ESTADO_DESATIVADO, $favor->fresh()->estado);
    }

    public function test_mantem_o_nivel_congelado_quando_o_ajudante_evolui_depois(): void
    {
        $proficienciaUsuario = $this->proficienciaDeclarada(ProficienciaUsuario::NIVEL_AVANCADO);
        $favor = $this->favorDe($proficienciaUsuario);
        $favor->congelar();

        $proficienciaUsuario->update(['nivel' => ProficienciaUsuario::NIVEL_ESPECIALISTA]);

        // Quem adquiriu o ticket contou com "Avançado" (RF-FAV-22, 05 §14.1).
        $this->assertSame(
            ProficienciaUsuario::NIVEL_AVANCADO,
            $favor->fresh()->nivel()
        );
    }

    public function test_nao_emite_ticket_em_favor_desativado(): void
    {
        $favor = $this->favorDe($this->proficienciaDeclarada());
        $favor->ativar();

        $this->assertTrue($favor->podeEmitirTicket());

        $favor->desativar();

        $this->assertFalse($favor->fresh()->podeEmitirTicket());
    }

    public function test_nao_emite_ticket_em_proficiencia_restrita_pela_curadoria(): void
    {
        $proficienciaUsuario = $this->proficienciaDeclarada();
        $favor = $this->favorDe($proficienciaUsuario);
        $favor->ativar();

        $proficienciaUsuario->proficiencia->update([
            'restrita' => true,
            'motivo_restricao' => 'Risco físico — curadoria de lançamento.',
        ]);

        $this->assertFalse($favor->fresh()->podeEmitirTicket());
    }

    public function test_recusa_publicar_favor_em_proficiencia_restrita(): void
    {
        $proficienciaUsuario = $this->proficienciaDeclarada();
        $proficienciaUsuario->proficiencia->update([
            'restrita' => true,
            'motivo_restricao' => 'Risco físico — curadoria de lançamento.',
        ]);

        $favor = $this->favorDe($proficienciaUsuario);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/restrita pela curadoria/');

        $favor->ativar();
    }

    public function test_impede_remover_proficiencia_com_favor_vinculado(): void
    {
        $proficienciaUsuario = $this->proficienciaDeclarada();
        $this->favorDe($proficienciaUsuario);

        $this->assertFalse($proficienciaUsuario->podeSerRemovida());

        $this->expectException(QueryException::class);

        $proficienciaUsuario->delete();
    }

    public function test_recusa_favor_remoto_com_area_de_atendimento(): void
    {
        $this->expectException(QueryException::class);

        $this->favorDe($this->proficienciaDeclarada(), [
            'modalidade' => Favor::MODALIDADE_REMOTO,
            'area_atendimento_km' => 5,
        ]);
    }

    public function test_recusa_cpf_alterado_apos_o_cadastro(): void
    {
        $usuario = $this->proficienciaDeclarada()->usuario;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/não pode ser alterado/');

        $usuario->cpf = '12345678909';
    }
}
