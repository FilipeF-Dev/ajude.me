<?php

namespace App\Livewire;

use App\Dominio\Ticket\EmitirTickets;
use App\Models\Favor;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;

/**
 * Oferecer ajuda: habilidade → o favor → quando e onde → os tickets.
 *
 * Os quatro passos do canvas em uma página só, por enquanto. O que não muda
 * é a ordem das perguntas: primeiro o que a pessoa sabe fazer, depois o que
 * ela se dispõe a fazer com isso.
 *
 * A emissão congela a condição publicada (RF-FAV-10). O aviso na tela existe
 * porque a regra é dura e irreversível: quem adquire o ticket conta com o que
 * está escrito, e por isso nada disso muda depois.
 */
#[Layout('components.layouts.app')]
class Oferecer extends Component
{
    public ?int $proficienciaId = null;

    public string $nivel = ProficienciaUsuario::NIVEL_INTERMEDIARIO;

    public string $titulo = '';

    public string $descricao = '';

    public int $tempoEstimadoMinutos = 60;

    public string $modalidade = Favor::MODALIDADE_PRESENCIAL;

    public ?int $areaAtendimentoKm = 5;

    public int $antecedenciaMinimaDias = 2;

    public int $validadeTicketDias = 180;

    public int $quantidade = 3;

    public bool $trocavel = true;

    public string $condicoes = '';

    protected function rules(): array
    {
        return [
            'proficienciaId' => ['required', 'exists:proficiencias,id'],
            'nivel' => ['required', 'in:'.implode(',', array_keys(ProficienciaUsuario::niveis()))],
            'titulo' => ['required', 'string', 'min:6', 'max:120'],
            'descricao' => ['required', 'string', 'min:20'],
            'tempoEstimadoMinutos' => ['required', 'integer', 'min:10', 'max:600'],
            'modalidade' => ['required', 'in:presencial,remoto'],
            'areaAtendimentoKm' => ['nullable', 'integer', 'min:1', 'max:200'],
            'antecedenciaMinimaDias' => ['required', 'integer', 'min:0', 'max:60'],
            'validadeTicketDias' => ['required', 'integer', 'min:1', 'max:365'],
            'quantidade' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function publicar(): void
    {
        $dados = $this->validate();

        // A área só existe no presencial; o esquema recusa a combinação
        // incoerente, e a tela não deve deixar chegar lá.
        $area = $this->modalidade === Favor::MODALIDADE_PRESENCIAL
            ? ($dados['areaAtendimentoKm'] ?? 5)
            : null;

        try {
            $favor = DB::transaction(function () use ($dados, $area) {
                $declarada = ProficienciaUsuario::firstOrCreate(
                    [
                        'usuario_id' => auth()->id(),
                        'proficiencia_id' => $dados['proficienciaId'],
                    ],
                    [
                        'nivel' => $dados['nivel'],
                        'declarada_em' => now(),
                    ],
                );

                $favor = Favor::create([
                    'proficiencia_usuario_id' => $declarada->id,
                    'titulo' => trim($dados['titulo']),
                    'descricao' => trim($dados['descricao']),
                    'tempo_estimado_minutos' => $dados['tempoEstimadoMinutos'],
                    'modalidade' => $dados['modalidade'],
                    'area_atendimento_km' => $area,
                    'antecedencia_minima_dias' => $dados['antecedenciaMinimaDias'],
                    'validade_ticket_dias' => $dados['validadeTicketDias'],
                    'maximo_circulacao' => $dados['quantidade'],
                    'trocavel' => $this->trocavel,
                    'condicoes' => trim($this->condicoes) ?: null,
                ]);

                $favor->ativar();

                app(EmitirTickets::class)->executar($favor->fresh(), $dados['quantidade']);

                return $favor;
            });
        } catch (LogicException $erro) {
            $this->addError('proficienciaId', $erro->getMessage());

            return;
        }

        session()->flash('publicado', 'Favor publicado e tickets emitidos.');

        $this->redirectRoute('favor', ['favor' => $favor], navigate: true);
    }

    public function render()
    {
        return view('livewire.oferecer', [
            'proficiencias' => Proficiencia::query()
                ->where('ativa', true)
                ->where('restrita', false)
                ->with('subcategoria.categoria')
                ->orderBy('nome')
                ->get()
                ->groupBy(fn (Proficiencia $p) => $p->subcategoria->categoria->nome),
            'niveis' => ProficienciaUsuario::niveis(),
        ]);
    }
}
