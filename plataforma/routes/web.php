<?php

use App\Livewire\Atendimento;
use App\Livewire\Avaliar;
use App\Livewire\Cadastrar;
use App\Livewire\Carteira;
use App\Livewire\Conversas;
use App\Livewire\Diagnostico;
use App\Livewire\Entrar;
use App\Livewire\EscolherSenha;
use App\Livewire\EsqueciSenha;
use App\Livewire\Inicio;
use App\Livewire\Negociacao;
use App\Livewire\Oferecer;
use App\Livewire\PaginaFavor;
use App\Livewire\Perfil;
use App\Livewire\PerfilPublico;
use App\Livewire\Propor;
use App\Livewire\Solicitar;
use App\Livewire\VerificarContato;
use Illuminate\Support\Facades\Route;

/**
 * Web responsiva instalável (decisão A1, 05 §15).
 *
 * Uma base de código, sem loja de aplicativos, sem ciclo de revisão e com
 * atualização imediata — e o público-alvo não instala aplicativos com
 * facilidade. O empacotamento Android por TWA fica disponível quando
 * desejado (05 §15.2), sobre estas mesmas telas.
 */
Route::get('/manifesto.webmanifest', fn () => response(
    view('pwa.manifesto')->render(),
    200,
    ['Content-Type' => 'application/manifest+json; charset=utf-8'],
))->name('manifesto');

Route::middleware('guest')->group(function () {
    Route::get('/entrar', Entrar::class)->name('entrar');
    Route::get('/criar-conta', Cadastrar::class)->name('cadastrar');
    Route::get('/esqueci-a-senha', EsqueciSenha::class)->name('esqueci-senha');
    Route::get('/senha-nova/{token}', EscolherSenha::class)->name('redefinir-senha');
});

Route::middleware('auth')->group(function () {
    Route::get('/', Inicio::class)->name('inicio');

    Route::get('/carteira', Carteira::class)->name('carteira');
    Route::get('/oferecer', Oferecer::class)->name('oferecer');
    Route::get('/conversas', Conversas::class)->name('conversas');
    Route::get('/perfil', Perfil::class)->name('perfil');
    Route::get('/perfil/contato', VerificarContato::class)->name('verificar-contato');

    Route::get('/favor/{favor}', PaginaFavor::class)->name('favor');
    Route::get('/favor/{favor}/propor', Propor::class)->name('propor');
    Route::get('/negociacao/{proposta}', Negociacao::class)->name('negociacao');
    Route::get('/ticket/{ticket}/pedir', Solicitar::class)->name('solicitar');
    Route::get('/atendimento/{solicitacao}', Atendimento::class)->name('atendimento');
    Route::get('/atendimento/{solicitacao}/avaliar', Avaliar::class)->name('avaliar');
    Route::get('/pessoa/{usuario}', PerfilPublico::class)->name('perfil.publico');

    // Fora do loop do produto e fora da barra de navegação: é o instrumento
    // de verificação da decisão A1 (05 §15.3), usado em aparelho real.
    Route::get('/diagnostico', Diagnostico::class)->name('diagnostico');
});

// Quem não entrou cai na tela de entrada, não em um 404.
Route::fallback(fn () => redirect()->route(auth()->check() ? 'inicio' : 'entrar'));
