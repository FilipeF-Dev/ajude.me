<?php

namespace App\Livewire;

use App\Dominio\Descoberta\BuscarFavores;
use App\Dominio\Descoberta\BuscarPorNecessidade;
use App\Models\Categoria;
use App\Models\Proficiencia;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tela inicial: feed neutro e busca por necessidade (04 §8, §8.1).
 *
 * O feed é **neutro** por regra (RF-BUS-02): não assume que quem chegou quer
 * ajudar nem que quer ser ajudado. Não existe papel fixo (02 §1), e um feed
 * que escolhe o papel por você já começa errando metade das vezes.
 *
 * O campo principal pede a **necessidade**, não a competência: quem digita
 * sabe que o chuveiro não esquenta, não que precisa de um eletricista.
 */
#[Layout('components.layouts.app')]
class Inicio extends Component
{
    #[Url(as: 'preciso', except: '')]
    public string $necessidade = '';

    #[Url(as: 'categoria', except: null)]
    public ?int $categoriaId = null;

    /** @var array<int, int> proficiências que o usuário descartou (RF-BUS-19) */
    public array $descartadas = [];

    public bool $buscou = false;

    /** @var Collection<int, Proficiencia> */
    public Collection $reconhecidas;

    public ?int $raioKm = null;

    public function mount(): void
    {
        $this->reconhecidas = collect();

        if ($this->necessidade !== '') {
            $this->buscar();
        }
    }

    public function buscar(): void
    {
        $this->necessidade = trim($this->necessidade);

        if ($this->necessidade === '') {
            $this->limpar();

            return;
        }

        $resultado = app(BuscarPorNecessidade::class)->executar(
            $this->necessidade,
            auth()->user(),
            $this->descartadas,
            $this->raioKm,
        );

        $this->reconhecidas = $resultado['proficiencias'];
        $this->buscou = true;
    }

    /**
     * RF-BUS-19 — o usuário corrige a interpretação do sistema.
     *
     * Cada correção refaz a busca **e** registra o descarte: é assim que o
     * erro de vocabulário chega à Operação em vez de morrer na tela.
     */
    public function descartar(int $proficienciaId): void
    {
        $this->descartadas[] = $proficienciaId;
        $this->buscar();
    }

    /** RF-BUS-24 — ampliar a área quando não há resultado na região. */
    public function ampliarArea(): void
    {
        $this->raioKm = null;
        $this->buscar();
    }

    public function limpar(): void
    {
        $this->necessidade = '';
        $this->descartadas = [];
        $this->reconhecidas = collect();
        $this->buscou = false;
    }

    public function filtrarPorCategoria(?int $categoriaId): void
    {
        $this->categoriaId = $categoriaId;
    }

    public function render()
    {
        $aplicaveis = $this->reconhecidas
            ->reject(fn (Proficiencia $p) => in_array($p->id, $this->descartadas, true));

        $favores = $this->buscou && $aplicaveis->isEmpty()
            ? collect()
            : app(BuscarFavores::class)->executar(
                usuario: auth()->user(),
                proficienciaIds: $this->buscou ? $aplicaveis->pluck('id')->all() : [],
                categoriaId: $this->categoriaId,
                raioKm: $this->raioKm,
            );

        return view('livewire.inicio', [
            'favores' => $favores,
            'aplicaveis' => $aplicaveis,
            'categorias' => Categoria::where('ativa', true)->orderBy('ordem')->orderBy('nome')->get(),
        ]);
    }
}
