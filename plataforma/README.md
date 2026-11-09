# Ajude.me: plataforma

Aplicação Laravel do [Ajude.me](..). As regras de negócio estão nos documentos
da raiz; aqui fica o código.

## Rodando localmente

Requisitos: Docker e Node 22+. Não é preciso PHP na máquina: tudo roda no
contêiner.

1. Crie o `.env` a partir do `.env.example` e aponte o banco para o contêiner:

   ```env
   DB_CONNECTION=pgsql
   DB_HOST=banco
   DB_PORT=5432
   DB_DATABASE=ajudeme
   DB_USERNAME=ajudeme
   DB_PASSWORD=ajudeme
   ```

2. Suba e prepare:

   ```bash
   docker compose up -d
   docker compose run --rm app php artisan key:generate
   docker compose run --rm app php artisan migrate --seed
   docker compose run --rm app php artisan db:seed --class=DemonstracaoSeeder
   npm install && npm run build
   ```

A aplicação fica em `http://localhost:8000` e o painel da Operação em
`/operacao`. No Git Bash do Windows, prefixe os comandos `docker` com
`MSYS_NO_PATHCONV=1`.

## Testes

```bash
docker compose run --rm app php artisan test
```

## Como o código está organizado

- **O domínio fala português.** Tabelas, modelos e métodos usam o vocabulário
  de `02`: `usuarios`, `favores`, `tickets`, `chamados`.
- **Regra de negócio no domínio, não no controller.** Operações que
  atravessam mais de um agregado ficam em `app/Dominio/<Contexto>/`, uma ação
  por classe (`EmitirTickets`, `AceitarProposta`).
- **Transição de estado nunca é atribuição.** Ticket e Proposta mudam de
  estado por método próprio, que valida a transição e deixa rastro.
- **Prazo é parâmetro, não constante.** Vem de `ParametroNegocio`, com
  vigência.
- **Não existe saldo.** Nenhum campo representa crédito acumulável: é isso
  que impede a moeda interna de aparecer por conveniência.
- **Operador não é usuário.** Tabelas e guards separados; o painel da
  Operação fica em `/operacao`.
