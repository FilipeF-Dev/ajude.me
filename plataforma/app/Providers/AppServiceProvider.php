<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /**
         * O identificador de registro é sempre numérico.
         *
         * Sem esta restrição, uma URL como `/operacao/chamados/create` casa
         * com `chamados/{record}` e o "create" chega ao banco como se fosse um
         * identificador: o Postgres recusa converter texto para bigint, a
         * consulta estoura e o operador autenticado vê uma página de erro 500.
         *
         * Com a restrição, a rota simplesmente não casa, cai no
         * `Route::fallback()` e a pessoa volta para onde consegue seguir.
         *
         * Vale para todo o painel porque todas as tabelas usam chave inteira.
         * Se algum dia um recurso passar a usar UUID ou slug, esta linha
         * precisa deixar de ser global.
         *
         * Fica em `register()`, e não em `boot()`: o padrão é aplicado quando
         * a rota é **criada**, e o Filament registra as dele antes deste
         * provedor inicializar. Em `boot()` a restrição chega tarde e não
         * surte efeito nenhum.
         */
        Route::pattern('record', '[0-9]+');
    }
}
