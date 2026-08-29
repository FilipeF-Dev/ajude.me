<?php

namespace App\Dominio\Descoberta;

use App\Models\Favor;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Feed e filtros de favores (RF-BUS-01 a RF-BUS-11, RF-BUS-20).
 *
 * O feed inicial é **neutro**: não assume que quem chegou quer ajudar nem
 * que quer ser ajudado (RF-BUS-02). Não existe papel fixo (02 §1), e um feed
 * que escolhe o papel por você já começa errando a metade das vezes.
 *
 * Um favor só aparece se puder ser adquirido agora: ativo e com ticket
 * disponível (RF-BUS-10, RF-BUS-20). Mostrar favor sem ticket seria oferecer
 * o que não se pode dar.
 */
class BuscarFavores
{
    /**
     * @param  array<int, int>  $proficienciaIds  restringe às proficiências reconhecidas
     * @return Collection<int, Favor>
     */
    public function executar(
        ?Usuario $usuario = null,
        array $proficienciaIds = [],
        ?string $texto = null,
        ?int $categoriaId = null,
        ?string $modalidade = null,
        ?string $nivel = null,
        ?int $raioKm = null,
        int $limite = 50,
    ): Collection {
        $consulta = Favor::query()
            ->with(['proficienciaUsuario.usuario', 'proficienciaUsuario.proficiencia.subcategoria'])
            ->where('favores.estado', Favor::ESTADO_ATIVO)
            ->whereExists(fn ($sub) => $sub
                ->selectRaw('1')
                ->from('tickets')
                ->whereColumn('tickets.favor_id', 'favores.id')
                ->where('tickets.estado', Ticket::DISPONIVEL))
            ->join('proficiencias_usuario', 'proficiencias_usuario.id', '=', 'favores.proficiencia_usuario_id')
            ->join('proficiencias', 'proficiencias.id', '=', 'proficiencias_usuario.proficiencia_id')
            ->join('subcategorias', 'subcategorias.id', '=', 'proficiencias.subcategoria_id')
            ->select('favores.*');

        if ($proficienciaIds !== []) {
            $consulta->whereIn('proficiencias.id', $proficienciaIds);
        }

        if ($texto !== null && trim($texto) !== '') {
            // RF-BUS-03 — busca por texto livre, sobre o que o ajudante
            // escreveu. É complementar à busca por necessidade, não substituta.
            $termo = '%'.mb_strtolower(trim($texto)).'%';

            $consulta->where(fn (Builder $sub) => $sub
                ->whereRaw('LOWER(favores.titulo) LIKE ?', [$termo])
                ->orWhereRaw('LOWER(favores.descricao) LIKE ?', [$termo])
                ->orWhereRaw('LOWER(proficiencias.nome) LIKE ?', [$termo]));
        }

        if ($categoriaId !== null) {
            $consulta->where('subcategorias.categoria_id', $categoriaId);
        }

        if ($modalidade !== null) {
            $consulta->where('favores.modalidade', $modalidade);
        }

        if ($nivel !== null) {
            // Nível vigente do favor: o congelado, quando já congelou
            // (RF-FAV-22); o declarado, enquanto não.
            $consulta->whereRaw(
                'COALESCE(favores.nivel_congelado, proficiencias_usuario.nivel) = ?',
                [$nivel]
            );
        }

        if ($usuario !== null) {
            $this->ocultarBloqueados($consulta, $usuario);

            // Ninguém precisa ver a própria oferta no feed de descoberta.
            $consulta->where('proficiencias_usuario.usuario_id', '<>', $usuario->id);
        }

        $this->ordenarPorProximidade($consulta, $usuario, $raioKm);

        return $consulta->limit($limite)->get();
    }

    /**
     * RF-BUS-11 — o bloqueio oculta mutuamente perfis e favores.
     *
     * As duas direções, porque o efeito do bloqueio é mútuo ainda que o
     * registro seja direcional (02 §18.2).
     */
    private function ocultarBloqueados(Builder $consulta, Usuario $usuario): void
    {
        $consulta->whereNotExists(fn ($sub) => $sub
            ->selectRaw('1')
            ->from('bloqueios')
            ->where(fn ($ou) => $ou
                ->where(fn ($direcao) => $direcao
                    ->where('bloqueios.usuario_id', $usuario->id)
                    ->whereColumn('bloqueios.usuario_bloqueado_id', 'proficiencias_usuario.usuario_id'))
                ->orWhere(fn ($direcao) => $direcao
                    ->whereColumn('bloqueios.usuario_id', 'proficiencias_usuario.usuario_id')
                    ->where('bloqueios.usuario_bloqueado_id', $usuario->id))));
    }

    /**
     * RF-BUS-01 e RF-BUS-07 — proximidade primeiro, publicação depois.
     *
     * Sem endereço não há proximidade a calcular, e a ordenação cai para a
     * data. Uma rede de troca depende de densidade geográfica (06 §2), então
     * perto importa mais que novo — mas exigir endereço para ver o feed
     * fecharia a porta de quem ainda está conhecendo a plataforma.
     */
    private function ordenarPorProximidade(Builder $consulta, ?Usuario $usuario, ?int $raioKm): void
    {
        $origem = $usuario === null ? null : DB::table('enderecos')
            ->where('usuario_id', $usuario->id)
            ->whereNotNull('coordenada')
            ->selectRaw('ST_AsText(coordenada) AS wkt')
            ->value('wkt');

        if ($origem === null) {
            $consulta->orderByDesc('favores.created_at');

            return;
        }

        $consulta
            ->leftJoin('enderecos AS endereco_do_ajudante', function ($juncao) {
                $juncao->on('endereco_do_ajudante.usuario_id', '=', 'proficiencias_usuario.usuario_id');
            })
            ->selectRaw(
                "ST_Distance(
                    endereco_do_ajudante.coordenada,
                    ST_GeogFromText(?)
                 ) AS distancia_metros",
                [$origem]
            );

        if ($raioKm !== null) {
            // RF-BUS-24 — ampliar a área é aumentar este raio, não trocar de
            // consulta.
            $consulta->whereRaw(
                'ST_DWithin(endereco_do_ajudante.coordenada, ST_GeogFromText(?), ?)',
                [$origem, $raioKm * 1000]
            );
        }

        $consulta
            ->orderByRaw('distancia_metros ASC NULLS LAST')
            ->orderByDesc('favores.created_at');
    }
}
