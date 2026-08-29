# Plataforma — aplicação do Ajude.me

Leia primeiro `../00 - Estado do Projeto.md` e `../CLAUDE.md`. As regras de negócio estão em `../02 - Conceito e Regras de Negócio.md` e os requisitos em `../04 - Requisitos Funcionais.md`.

## Não instale PHP nesta máquina

**Tudo roda em contêiner, por decisão.** Não há PHP nem Composer instalados aqui, e não é para instalar.

```bash
docker compose up -d
docker compose run --rm app php artisan migrate --seed
docker compose run --rm app composer require <pacote>
```

- Aplicação: `http://localhost:8000`
- Painel da Operação: `http://localhost:8000/operacao`
- Banco: `localhost:5433` — a 5432 costuma estar ocupada por outro projeto do autor

No Git Bash do Windows, prefixe comandos `docker` com `MSYS_NO_PATHCONV=1`, senão os caminhos são convertidos e o comando falha.

## Stack

Laravel 13 · PHP 8.4 · PostgreSQL 16 com PostGIS · Filament 5

## Convenções

**O domínio fala português.** Tabelas, modelos, colunas e métodos usam o vocabulário de `../02`: `usuarios`, `favores`, `tickets`, `proficiencias`, `chamados`. Nunca use "serviço", "preço", "comprar", "vender", "contratar", "profissional" ou "cliente".

**Operador não é Usuario.** São atores distintos, em tabelas e guards separados (`web` e `operacao`). A Operação decide; os contextos Ticket e Consumo executam. Nunca dê a um `Usuario` poderes administrativos.

**Prazo é parâmetro, não constante.** Todo prazo vem de `ParametroNegocio::inteiroEm($chave, $momento)`, com vigência. Nunca escreva um número de prazo no código.

**Nada de saldo.** Não deve existir campo que represente saldo, crédito ou valor acumulável — nem no banco, nem em modelo, nem em cálculo. A ausência dessa estrutura é o que garante que a moeda interna não apareça por conveniência.

**Comentário explica o porquê.** As regras aqui têm origem documentada; ao implementar uma, cite a seção (`02 §8.4`) ou o requisito (`RF-CAD-03`).

**Regra de negócio no domínio, não no controller.** As operações que atravessam mais de um agregado ou que podem violar uma invariante sob concorrência vivem em `app/Dominio/<Contexto>/`, em ações de um método (`EmitirTickets`, `AceitarProposta`). O modelo guarda o que é dele; a ação guarda o que é da operação.

**Transição de estado nunca é atribuição.** Ticket e Proposta mudam de estado por método próprio, que valida a transição contra a máquina declarada e deixa rastro (P2). Atribuir `estado` direto contorna a auditoria.

## Estado atual

Construídas: **Identidade + Catálogo**, **Painel da Operação**, **Oferta**, **Ticket**, **Negociação**, **Consumo**, **Segurança + Moderação** e **Reputação**. O loop de domínio de `03 §3` fecha ponta a ponta. O acompanhamento por fatia está em `../00 - Estado do Projeto.md §3`.

A seguir, **Descoberta + Notificações** — a última fatia de domínio — e depois a **camada web**, que ainda não existe: não há uma única tela de usuário fora do painel da Operação.

**Reputação é calculada, nunca armazenada.** Não deve existir coluna de média, nota consolidada ou contador de estrelas. Se um dia pesar, a saída é cache reconstruível a partir das avaliações — nunca uma coluna que vira a verdade.

**Cuidado com defaults de coluna.** Um `->default()` na migração não preenche o atributo do modelo recém-criado: o banco grava, o objeto em memória fica nulo, e qualquer checagem feita logo em seguida responde errado. Onde o default importa para uma regra, espelhe-o em `protected $attributes` (ver `Usuario` e `Denuncia`). Esse defeito já apareceu duas vezes.
