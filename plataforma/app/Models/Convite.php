<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Convite de acesso (RF-ADM-08).
 *
 * Um convite, uma conta. O código é curto e legível porque vai ser lido em
 * voz alta e digitado à mão — quem entrega o convite costuma estar do lado
 * de quem o recebe, e é isso que a base restrita quer preservar.
 */
class Convite extends Model
{
    /**
     * Alfabeto sem os caracteres que se confundem lidos ou escritos à mão:
     * 0/O, 1/I/L, 5/S, 2/Z. Um convite mal digitado é um convite perdido.
     */
    private const ALFABETO = 'ABCDEFGHJKMNPQRTUVWXY346789';

    protected $table = 'convites';

    protected $fillable = [
        'codigo', 'criado_por_id', 'destinatario', 'observacao', 'expira_em',
    ];

    protected function casts(): array
    {
        return [
            'expira_em' => 'datetime',
            'usado_em' => 'datetime',
            'revogado_em' => 'datetime',
        ];
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(Operador::class, 'criado_por_id');
    }

    public function usadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usado_por_id');
    }

    public function revogadoPor(): BelongsTo
    {
        return $this->belongsTo(Operador::class, 'revogado_por_id');
    }

    public function foiUsado(): bool
    {
        return $this->usado_em !== null;
    }

    public function foiRevogado(): bool
    {
        return $this->revogado_em !== null;
    }

    public function estaVencido(?Carbon $momento = null): bool
    {
        return $this->expira_em !== null
            && $this->expira_em->lessThanOrEqualTo($momento ?? Carbon::now());
    }

    public function podeSerUsado(?Carbon $momento = null): bool
    {
        return ! $this->foiUsado()
            && ! $this->foiRevogado()
            && ! $this->estaVencido($momento);
    }

    public function situacao(?Carbon $momento = null): string
    {
        return match (true) {
            $this->foiUsado() => 'usado',
            $this->foiRevogado() => 'revogado',
            $this->estaVencido($momento) => 'vencido',
            default => 'disponivel',
        };
    }

    /** Formato `AJUD-XXXX`: identifica a plataforma e é fácil de ditar. */
    public static function gerarCodigo(): string
    {
        do {
            $sufixo = '';

            for ($i = 0; $i < 4; $i++) {
                $sufixo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
            }

            $codigo = "AJUD-{$sufixo}";
        } while (static::where('codigo', $codigo)->exists());

        return $codigo;
    }

    /** Normaliza o que a pessoa digitou: maiúsculas, sem espaço, com hífen. */
    public static function normalizar(string $codigo): string
    {
        $limpo = mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $codigo));

        return str_starts_with($limpo, 'AJUD') && strlen($limpo) > 4
            ? 'AJUD-'.substr($limpo, 4)
            : $limpo;
    }
}
