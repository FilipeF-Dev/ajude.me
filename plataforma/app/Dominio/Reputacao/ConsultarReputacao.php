<?php

namespace App\Dominio\Reputacao;

use App\Models\Avaliacao;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Leitura da reputação de um usuário (04 §14).
 *
 * **A reputação é sempre calculada, nunca armazenada como estado** (05 §4.8).
 * Toda média aqui sai das avaliações no momento da consulta. Se um dia isso
 * pesar, a saída é cache — reconstruível a partir das avaliações a qualquer
 * momento — e não uma coluna que passa a ser a verdade.
 *
 * Nada nesta classe escreve. É a garantia mais simples de I27: o caminho que
 * lê reputação não tem por onde tocar em `ProficienciaUsuario.nivel`.
 */
class ConsultarReputacao
{
    /**
     * RF-REP-01 e RF-REP-02 — média geral no papel.
     *
     * Média de todas as notas de todos os critérios daquele papel. Cada
     * critério pesa igual: nenhum dos documentos estabelece hierarquia entre
     * eles, e inventar peso aqui seria decidir por conta própria o que
     * importa mais numa troca de favor.
     */
    public function mediaGeral(Usuario $usuario, string $papel): ?float
    {
        $media = $this->notasDoPapel($usuario, $papel)->avg('nota');

        return $media === null ? null : round((float) $media, 2);
    }

    /**
     * RF-REP-03 — médias por critério.
     *
     * @return array<string, float>
     */
    public function mediasPorCriterio(Usuario $usuario, string $papel): array
    {
        return $this->notasDoPapel($usuario, $papel)
            ->groupBy('criterio')
            ->map(fn ($notas) => round((float) $notas->avg('nota'), 2))
            ->all();
    }

    /**
     * RF-REP-04 e RF-REP-07 — reputação segmentada por proficiência.
     *
     * Só faz sentido no papel de Ajudante: é a proficiência dele que originou
     * o favor. O vínculo atravessa solicitação → ticket → favor →
     * proficiência declarada, e é o que permite exibir o nível declarado ao
     * lado da reputação obtida naquela mesma proficiência.
     *
     * @return array<int, array{proficiencia: string, nivel: string, media: float, avaliacoes: int}>
     */
    public function porProficiencia(Usuario $usuario): array
    {
        return DB::table('avaliacoes')
            ->join('solicitacoes', 'solicitacoes.id', '=', 'avaliacoes.solicitacao_id')
            ->join('tickets', 'tickets.id', '=', 'solicitacoes.ticket_id')
            ->join('favores', 'favores.id', '=', 'tickets.favor_id')
            ->join('proficiencias_usuario', 'proficiencias_usuario.id', '=', 'favores.proficiencia_usuario_id')
            ->join('proficiencias', 'proficiencias.id', '=', 'proficiencias_usuario.proficiencia_id')
            ->join('notas_criterio', 'notas_criterio.avaliacao_id', '=', 'avaliacoes.id')
            ->where('avaliacoes.avaliado_id', $usuario->id)
            ->where('avaliacoes.papel_avaliado', Solicitacao::PARTE_AJUDANTE)
            ->groupBy('proficiencias.nome', 'proficiencias_usuario.nivel')
            ->selectRaw(
                'proficiencias.nome AS proficiencia,
                 proficiencias_usuario.nivel AS nivel,
                 ROUND(AVG(notas_criterio.nota)::numeric, 2) AS media,
                 COUNT(DISTINCT avaliacoes.id) AS avaliacoes'
            )
            ->get()
            ->map(fn ($linha) => [
                'proficiencia' => $linha->proficiencia,
                'nivel' => $linha->nivel,
                'media' => (float) $linha->media,
                'avaliacoes' => (int) $linha->avaliacoes,
            ])
            ->all();
    }

    /** RF-REP-05 — favores prestados e recebidos, contados por ciclo concluído. */
    public function favoresPrestados(Usuario $usuario): int
    {
        return $this->concluidas($usuario, 'ajudante_id');
    }

    public function favoresRecebidos(Usuario $usuario): int
    {
        return $this->concluidas($usuario, 'solicitante_id');
    }

    /** RF-REP-06 — tempo de participação, em dias. */
    public function diasDeParticipacao(Usuario $usuario): int
    {
        return (int) $usuario->created_at->diffInDays(now());
    }

    /**
     * RF-REP-08 — o perfil indica quando ainda não há avaliações suficientes.
     *
     * Uma média sobre uma avaliação só não é reputação: é a opinião de uma
     * pessoa exibida com aparência de estatística. Dizer isso é mais honesto
     * do que mostrar "7,0" para quem foi avaliado uma vez.
     */
    public function temAvaliacoesSuficientes(Usuario $usuario, string $papel, int $minimo = 3): bool
    {
        return Avaliacao::query()
            ->where('avaliado_id', $usuario->id)
            ->where('papel_avaliado', $papel)
            ->count() >= $minimo;
    }

    private function notasDoPapel(Usuario $usuario, string $papel)
    {
        return DB::table('notas_criterio')
            ->join('avaliacoes', 'avaliacoes.id', '=', 'notas_criterio.avaliacao_id')
            ->where('avaliacoes.avaliado_id', $usuario->id)
            ->where('avaliacoes.papel_avaliado', $papel)
            ->select('notas_criterio.criterio', 'notas_criterio.nota')
            ->get();
    }

    private function concluidas(Usuario $usuario, string $coluna): int
    {
        return Solicitacao::query()
            ->where($coluna, $usuario->id)
            ->whereIn('estado', [Solicitacao::REALIZADA, Solicitacao::ENCERRADA])
            ->count();
    }
}
