<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Verificação da decisão A1 em aparelho real (05 §15.3).
 *
 * > A decisão que mais cedo pode precisar ser revista é **A1**: se a
 * > segurança presencial exigir recursos que a web não entrega bem em
 * > Android antigo — notificação confiável e acionamento de emergência em
 * > segundo plano — o aplicativo nativo deixa de ser luxo e passa a ser
 * > requisito. Isso deve ser verificado ainda na Fase 1, **com aparelho
 * > real**, e não presumido.
 *
 * Esta tela é o instrumento dessa verificação. Ela não decide nada: mede o
 * que o aparelho entrega e mostra o resultado, para que a decisão seja
 * tomada sobre evidência em vez de suposição.
 *
 * É tela de trabalho, fora do loop do produto e fora da barra de navegação.
 */
#[Layout('components.layouts.app')]
class Diagnostico extends Component
{
    public function render()
    {
        return view('livewire.diagnostico');
    }
}
