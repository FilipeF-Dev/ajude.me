<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Faixa de dia e horário em que o ajudante atende (02 §4.2).
 *
 * É o que faz a tela de agendamento oferecer apenas dias válidos, em vez de
 * aceitar qualquer data e recusar depois com mensagem de erro (RF-SOL-03).
 */
class JanelaDisponibilidade extends Model
{
    protected $table = 'janelas_disponibilidade';

    protected $fillable = ['favor_id', 'dia_semana', 'hora_inicio', 'hora_fim'];

    public function favor(): BelongsTo
    {
        return $this->belongsTo(Favor::class);
    }

    public static function diasSemana(): array
    {
        return [
            0 => 'Domingo',
            1 => 'Segunda',
            2 => 'Terça',
            3 => 'Quarta',
            4 => 'Quinta',
            5 => 'Sexta',
            6 => 'Sábado',
        ];
    }

    public function diaPorExtenso(): string
    {
        return self::diasSemana()[$this->dia_semana] ?? '—';
    }
}
