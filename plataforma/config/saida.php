<?php

use App\Dominio\Saida\Canais\CanalRegistrado;

return [

    /*
    | O canal de saída em uso.
    |
    | `registrado` grava e não envia — é o que roda enquanto não há provedor
    | contratado (05 §15.1). Trocar por um provedor real é acrescentar a
    | classe aqui e mudar a variável de ambiente; nada mais na aplicação
    | precisa saber quem entrega.
    */

    'canal' => env('SAIDA_CANAL', 'registrado'),

    'canais' => [
        'registrado' => CanalRegistrado::class,
    ],

    'log' => env('SAIDA_LOG', 'stack'),

];
