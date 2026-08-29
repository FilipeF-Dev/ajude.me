<?php

use Illuminate\Support\Facades\Schedule;

/**
 * O que corre sem ninguém clicar.
 *
 * A cada dez minutos porque as condições são de horas e dias, e todas são
 * avaliação de estado (05 §15, decisão A6): atrasar uma execução adia o
 * efeito, nunca o perde. `withoutOverlapping` evita que uma execução longa
 * encontre a seguinte no meio do caminho.
 */
Schedule::command('ajudeme:rotinas')
    ->everyTenMinutes()
    ->withoutOverlapping();
