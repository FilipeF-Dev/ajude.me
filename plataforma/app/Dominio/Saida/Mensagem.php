<?php

namespace App\Dominio\Saida;

use App\Models\Envio;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * O que se quer dizer, para quem, por qual canal.
 *
 * Endereço, e não conta: é o que permite falar com o contato de confiança,
 * que não é usuário da plataforma (05 §14.3).
 */
class Mensagem
{
    public function __construct(
        public readonly string $canal,
        public readonly string $destino,
        public readonly string $tipo,
        public readonly string $corpo,
        public readonly ?string $assunto = null,
        public readonly ?Model $referencia = null,
        public readonly ?string $chaveIdempotencia = null,
    ) {
        if (! in_array($canal, [Envio::EMAIL, Envio::SMS], true)) {
            throw new LogicException("Canal desconhecido: {$canal}.");
        }

        if (trim($destino) === '') {
            throw new LogicException('Mensagem sem destino não é mensagem.');
        }

        if ($canal === Envio::SMS && $assunto !== null) {
            throw new LogicException('SMS não tem assunto.');
        }
    }

    public static function email(
        string $destino,
        string $tipo,
        string $assunto,
        string $corpo,
        ?Model $referencia = null,
        ?string $chaveIdempotencia = null,
    ): self {
        return new self(Envio::EMAIL, $destino, $tipo, $corpo, $assunto, $referencia, $chaveIdempotencia);
    }

    public static function sms(
        string $destino,
        string $tipo,
        string $corpo,
        ?Model $referencia = null,
        ?string $chaveIdempotencia = null,
    ): self {
        return new self(Envio::SMS, $destino, $tipo, $corpo, null, $referencia, $chaveIdempotencia);
    }

    /**
     * Sem chave explícita, a identidade da mensagem é o seu conteúdo.
     *
     * Duas tentativas de mandar a mesma coisa para a mesma pessoa são a mesma
     * mensagem; mandar um código novo é outra, porque o corpo muda.
     */
    public function chave(): string
    {
        return $this->chaveIdempotencia
            ?? substr(hash('sha256', implode('|', [
                $this->canal, $this->destino, $this->tipo, $this->corpo,
            ])), 0, 64);
    }
}
