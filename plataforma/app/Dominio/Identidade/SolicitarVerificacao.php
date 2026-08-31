<?php

namespace App\Dominio\Identidade;

use App\Dominio\Saida\Despachar;
use App\Dominio\Saida\Mensagem;
use App\Models\ParametroNegocio;
use App\Models\Usuario;
use App\Models\VerificacaoContato;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

/**
 * Emite um código e o manda para o contato (RF-CAD-04, RF-CAD-05).
 *
 * O código é guardado **cifrado**: é credencial de uso único, e quem a tiver
 * confirma um contato que não é seu. A validade curta é atenuante, não
 * proteção.
 *
 * Enquanto não houver provedor contratado, o código sai pelo canal que
 * registra e não envia (05 §15.1) — ele fica legível em `envios`, que é como
 * um cadastro se completa hoje, e o painel mostra claramente que nada saiu
 * da plataforma.
 */
class SolicitarVerificacao
{
    public function __construct(
        private readonly Despachar $despachar,
    ) {
    }

    public function executar(
        Usuario $usuario,
        string $tipo,
        ?Carbon $momento = null,
    ): VerificacaoContato {
        $momento ??= Carbon::now();

        $destino = $this->destinoDe($usuario, $tipo);

        return DB::transaction(function () use ($usuario, $tipo, $destino, $momento) {
            $verificacao = VerificacaoContato::query()
                ->where('usuario_id', $usuario->id)
                ->where('tipo', $tipo)
                ->lockForUpdate()
                ->first();

            if ($verificacao?->estaVerificado()) {
                throw new LogicException('Este contato já está verificado.');
            }

            $this->recusarPedidoApressado($verificacao, $momento);

            $codigo = $this->gerarCodigo();

            $validade = ParametroNegocio::inteiroEm(
                ParametroNegocio::VERIFICACAO_VALIDADE_MINUTOS,
                $momento,
            ) ?? 30;

            $verificacao ??= new VerificacaoContato();
            $verificacao->forceFill([
                'usuario_id' => $usuario->id,
                'tipo' => $tipo,
                'valor' => $destino,
                'codigo' => Hash::make($codigo),
                'codigo_expira_em' => $momento->copy()->addMinutes($validade),
                // Zera as tentativas: código novo, contagem nova. Senão um
                // erro antigo travaria a verificação seguinte.
                'tentativas' => 0,
                'updated_at' => $momento,
            ]);
            $verificacao->save();

            $this->despachar->executar(
                $this->mensagem($tipo, $destino, $codigo, $usuario, $momento),
                $momento,
            );

            return $verificacao;
        });
    }

    private function destinoDe(Usuario $usuario, string $tipo): string
    {
        return match ($tipo) {
            VerificacaoContato::TIPO_EMAIL => $usuario->email,
            VerificacaoContato::TIPO_TELEFONE => $usuario->telefone,
            default => throw new LogicException("Tipo de verificação desconhecido: {$tipo}."),
        };
    }

    /**
     * NF-SEG-06 — limitação de tentativas.
     *
     * Pedir código é o gatilho de abuso mais barato que existe: cada pedido
     * custa uma mensagem, e mensagem custa dinheiro. O intervalo protege o
     * orçamento e o telefone de quem seria bombardeado.
     */
    private function recusarPedidoApressado(?VerificacaoContato $verificacao, Carbon $momento): void
    {
        if ($verificacao === null) {
            return;
        }

        $intervalo = ParametroNegocio::inteiroEm(
            ParametroNegocio::VERIFICACAO_INTERVALO_REENVIO_SEGUNDOS,
            $momento,
        ) ?? 60;

        $ultimo = $verificacao->updated_at;

        if ($ultimo !== null && $ultimo->diffInSeconds($momento) < $intervalo) {
            $faltam = $intervalo - (int) $ultimo->diffInSeconds($momento);

            throw new LogicException(
                "Espere {$faltam} segundos para pedir outro código."
            );
        }
    }

    /**
     * Seis dígitos. Curto o bastante para ser digitado por quem recebeu um
     * SMS, e o limite de tentativas é o que impede adivinhar.
     */
    private function gerarCodigo(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function mensagem(
        string $tipo,
        string $destino,
        string $codigo,
        Usuario $usuario,
        Carbon $momento,
    ): Mensagem {
        // A chave inclui o momento: pedir outro código é outra mensagem, mas
        // um duplo clique no mesmo segundo não vira duas.
        $chave = "verificacao-{$tipo}-{$usuario->id}-".$momento->getTimestamp();

        if ($tipo === VerificacaoContato::TIPO_EMAIL) {
            return Mensagem::email(
                destino: $destino,
                tipo: 'verificacao_contato',
                assunto: 'Seu código do Ajude.me',
                corpo: "Seu código é {$codigo}. Ele vale por pouco tempo. "
                    .'Se não foi você que pediu, ignore esta mensagem.',
                referencia: $usuario,
                chaveIdempotencia: $chave,
            );
        }

        return Mensagem::sms(
            destino: $destino,
            tipo: 'verificacao_contato',
            corpo: "Ajude.me: seu codigo e {$codigo}. Nao compartilhe.",
            referencia: $usuario,
            chaveIdempotencia: $chave,
        );
    }
}
