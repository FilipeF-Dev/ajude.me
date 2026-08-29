<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LogicException;

/**
 * Usuário da plataforma.
 *
 * Não existe papel fixo: a mesma pessoa é Ajudante quando oferece e
 * Solicitante quando recebe (02 §1). Por isso não há campo de papel.
 */
class Usuario extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    public const ESTADO_ATIVA = 'ativa';
    public const ESTADO_ENCERRANDO = 'encerrando';
    public const ESTADO_DESATIVADA = 'desativada';
    public const ESTADO_SUSPENSA = 'suspensa';

    protected $table = 'usuarios';

    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'cpf',
        'senha',
        'foto_caminho',
    ];

    protected $hidden = [
        'senha',
        'cpf',
        'remember_token',
    ];

    /**
     * Espelha o default da coluna `estado_conta`.
     *
     * Sem isto, o modelo recém-criado fica com o atributo nulo enquanto o
     * banco já gravou "ativa", e `podeOfertar()` / `podeNegociar()` reprovam
     * uma conta que está ativa. É a diferença entre o que o esquema diz e o
     * que o objeto em memória sabe — e quem cadastra e age em seguida paga
     * por ela.
     */
    protected $attributes = [
        'estado_conta' => self::ESTADO_ATIVA,
    ];

    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
            'encerramento_solicitado_em' => 'datetime',
            'encerrada_em' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->senha;
    }

    /**
     * RF-CAD-10 — o CPF é imutável após o cadastro.
     *
     * A regra vive aqui e não no banco porque um CHECK não distingue
     * inserção de atualização. Bloquear na fronteira do modelo impede que
     * qualquer caminho da aplicação a contorne por descuido.
     */
    public function setCpfAttribute(string $valor): void
    {
        $apenasDigitos = preg_replace('/\D/', '', $valor);

        if ($this->exists && $this->attributes['cpf'] !== $apenasDigitos) {
            throw new LogicException('O CPF não pode ser alterado após o cadastro.');
        }

        $this->attributes['cpf'] = $apenasDigitos;
    }

    public function setTelefoneAttribute(string $valor): void
    {
        $this->attributes['telefone'] = preg_replace('/\D/', '', $valor);
    }

    public function verificacoes(): HasMany
    {
        return $this->hasMany(VerificacaoContato::class);
    }

    public function endereco(): HasOne
    {
        return $this->hasOne(Endereco::class);
    }

    public function contatoConfianca(): HasOne
    {
        return $this->hasOne(ContatoConfianca::class);
    }

    public function proficiencias(): HasMany
    {
        return $this->hasMany(ProficienciaUsuario::class);
    }

    /**
     * A carteira (02 §7) — não é saldo.
     *
     * É a lista dos tickets de que a pessoa é dona neste momento. O que
     * existe são direitos identificáveis, cada um preso a um favor concreto;
     * nenhuma soma deles significa coisa alguma (P8).
     */
    public function carteira(): HasMany
    {
        return $this->hasMany(Ticket::class, 'proprietario_id');
    }

    /** RF-NEG-17 — histórico de propostas enviadas e recebidas. */
    public function propostasEnviadas(): HasMany
    {
        return $this->hasMany(Proposta::class, 'remetente_id');
    }

    public function propostasRecebidas(): HasMany
    {
        return $this->hasMany(Proposta::class, 'destinatario_id');
    }

    /**
     * RF-SOL-22 — os dois lados da mesma pessoa.
     *
     * Não existe papel fixo (02 §1): quem hoje pede é quem amanhã atende, e
     * as duas listas convivem na mesma conta.
     */
    public function solicitacoesFeitas(): HasMany
    {
        return $this->hasMany(Solicitacao::class, 'solicitante_id');
    }

    public function atendimentos(): HasMany
    {
        return $this->hasMany(Solicitacao::class, 'ajudante_id');
    }

    /**
     * RF-CAD-06 — o selo é derivado, nunca armazenado.
     */
    public function contatoVerificado(): bool
    {
        $verificados = $this->verificacoes
            ->whereNotNull('verificado_em')
            ->pluck('tipo')
            ->all();

        return in_array(VerificacaoContato::TIPO_EMAIL, $verificados, true)
            && in_array(VerificacaoContato::TIPO_TELEFONE, $verificados, true);
    }

    /**
     * RF-CAD-13 — conta em encerramento não inicia nada novo.
     */
    public function podeOfertar(): bool
    {
        return $this->estado_conta === self::ESTADO_ATIVA;
    }

    public function podeNegociar(): bool
    {
        return $this->estado_conta === self::ESTADO_ATIVA;
    }

    /**
     * RF-CAD-14 — quem está encerrando continua obrigado a atender os
     * tickets que já emitiu. O direito do portador não depende da vontade
     * de quem saiu (02 §2.2).
     */
    public function deveAtender(): bool
    {
        return in_array($this->estado_conta, [
            self::ESTADO_ATIVA,
            self::ESTADO_ENCERRANDO,
        ], true);
    }
}
