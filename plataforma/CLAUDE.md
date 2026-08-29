# Plataforma — aplicação do Ajude.me

Leia primeiro `../00 - Estado do Projeto.md` e `../CLAUDE.md`. As regras de negócio estão em `../02 - Conceito e Regras de Negócio.md` e os requisitos em `../04 - Requisitos Funcionais.md`.

## Não instale PHP nesta máquina

**Tudo roda em contêiner, por decisão.** Não há PHP nem Composer instalados aqui, e não é para instalar.

```bash
docker compose up -d
docker compose run --rm app php artisan migrate --seed
docker compose run --rm app php artisan db:seed --class=DemonstracaoSeeder
docker compose run --rm app composer require <pacote>
npm install && npm run build   # os assets compilam no host
```

**O `vendor/` mora num volume Docker, não no bind mount.** O bootstrap do Laravel abre centenas de arquivos por requisição, e a travessia do mount do Windows custava 15s por página — agora são 0,5s. Consequência: `composer install` roda **dentro do contêiner** e o `vendor/` do host fica desatualizado de propósito. Depois de mexer no `composer.json`, rode `docker compose run --rm app composer install`.

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

**O domínio está completo** e a **camada web tem sua primeira volta**: entrada, cadastro, feed com busca por necessidade, página do favor, carteira, oferecer, propor, negociar, pedir o favor, perfil próprio e público. O acompanhamento por fatia está em `../00 - Estado do Projeto.md §3`.

A seguir: convites de acesso (RF-ADM-08, pré-requisito do piloto fechado), métricas no painel e as telas de vocabulário/cobertura da Operação.

**O painel da Operação decide, não edita.** Os recursos do Filament em `app/Filament/Resources/` recusam `create`/`edit`/`delete` onde a regra manda: chamado nasce de contestação e sai por decisão fundamentada; ocorrência é consequência de um ato; a trilha de auditoria não se edita. Toda ação da Operação chama a ação de domínio e passa por `RegistroAuditoria::registrar()`.

**Interface em Livewire, uma classe por tela**, em `app/Livewire/`. Todas declaram `#[Layout(...)]` explicitamente. As ações de domínio nunca são reimplementadas na tela: o componente valida a entrada, chama a ação e traduz `LogicException` em erro de formulário.

**Nenhuma fonte externa e nada de blur.** Fonte do sistema por exigência de desempenho (`05 NF-ACE-01`), e a proibição de glassmorphism/aero é decisão de produto (`00 §7.2`), não gosto.

**Reputação é calculada, nunca armazenada.** Não deve existir coluna de média, nota consolidada ou contador de estrelas. Se um dia pesar, a saída é cache reconstruível a partir das avaliações — nunca uma coluna que vira a verdade.

**O momento é sempre parâmetro.** Toda ação de domínio aceita `?Carbon $momento`, e `transicionar()` grava esse instante na trilha — o do evento, não o do processamento. As rotinas temporais rodam a cada dez minutos e reavaliam condições que já valiam antes; gravar a hora da execução faria a trilha mentir, e prazos contados a partir dela sairiam errados.

**Prazo só corre se o agendador rodar.** `ajudeme:rotinas` é o que encerra proposta vencida, abre janela de contestação, aplica confirmação tácita e expira ticket. Sem `schedule:work` em produção, nenhum prazo do produto se cumpre.

**Cuidado com defaults de coluna.** Um `->default()` na migração não preenche o atributo do modelo recém-criado: o banco grava, o objeto em memória fica nulo, e qualquer checagem feita logo em seguida responde errado. Onde o default importa para uma regra, espelhe-o em `protected $attributes` (ver `Usuario` e `Denuncia`). Esse defeito já apareceu duas vezes.
