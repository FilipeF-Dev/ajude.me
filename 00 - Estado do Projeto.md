# Ajude.me — Estado do Projeto

> Documento de continuidade. Serve para que qualquer pessoa — ou qualquer sessão de trabalho — entenda em minutos onde o projeto está, o que já foi decidido e o que não deve ser redecidido.
>
> **Atualize este documento sempre que uma decisão estrutural for tomada.**

**Última atualização:** 31 de agosto de 2026

---

# 1. O que é

Plataforma de troca de favores entre pessoas, onde **habilidade vira moeda**. O objetivo social é dar acesso a serviços para quem não pode pagá-los, com foco em público de baixa e média renda no Brasil.

> **Precisar de ajuda → Encontrar alguém → Negociar → Receber ajuda → Ajudar outra pessoa**

---

# 2. Links

| Recurso | Endereço |
|---|---|
| Repositório remoto (privado) | https://github.com/FilipeF-Dev/ajude.me.git |
| Canvas de telas | https://claude.ai/code/artifact/5755cb4c-52c0-459b-b0e3-5b6e9dd22165 |

---

# 3. Onde estamos

| Etapa | Situação |
|---|---|
| Documentação conceitual (`01` a `06`) | ✅ Completa |
| Design de telas | 🔵 Em andamento — 16 telas do loop principal |
| Fase 0 do roadmap (fundação) | ✅ Fechada — restam apenas os itens jurídicos |
| Código | 🔵 Fase 1 em andamento — ver a ordem de construção abaixo |

## Fatias construídas

A ordem é a de `06 §5.3`. Cada fatia entra com migração, modelo, ação de domínio e teste das invariantes que ela protege.

| Fatia | Situação | Entregue |
|---|---|---|
| Identidade + Catálogo | ✅ | Usuário, verificação de contato, endereço, contato de confiança, catálogo, parâmetros de negócio |
| Painel da Operação | ✅ | Filament 5 em `/operacao`, com guard próprio e recursos do catálogo |
| Oferta | ✅ | Favor, janelas, congelamento na primeira emissão, descritor de dois eixos |
| Ticket | ✅ | Emissão sob limite de circulação, máquina de estados, cadeia de propriedade, expiração por avaliação de estado |
| Negociação | ✅ | Proposta versionada, bloqueio de tickets, revisão, aceite atômico, chat |
| Consumo | ✅ | Solicitação contra a condição publicada, agendamento sem sobreposição, comparecimento, janela de contestação, confirmação tácita, cancelamento |
| Segurança e Moderação | ✅ | Termo de ciência versionado, bloqueio, denúncia, ocorrência, chamado com decisão e efeitos, suspensão, trilha de auditoria |
| Reputação | ✅ | Avaliação bilateral obrigatória e imutável, notas por critério, reputação calculada e segmentada, encerramento do ciclo |
| Descoberta e Notificações | ✅ | Reconhecimento por termos populares, feed com filtros e proximidade, fila de aviso, retenção de 90 dias, notificações do loop e **rotinas temporais** |
| Métricas e cobertura | ✅ | Painel do piloto por coorte mensal, vocabulário faltante e cobertura faltante separados |

## Fase 1 — portão fechado

As cinco condições de saída de `06 §5.4` estão cumpridas. A última era esta:

> As métricas de `03 §7` são apuráveis a partir dos eventos.

O painel em `/operacao/metricas` apura as cinco hipóteses do MVP, mais cancelamento, contestação e **profundidade de circulação** — o indicador que testa se o ticket transferível funciona como rede ou apenas como vale nominal (`06 §10.3`).

A leitura é **por coorte mensal**, não em números absolutos: não existe referência publicada para redes de troca sem dinheiro em comunidades de baixa renda no Brasil, e critério direcional resiste ao viés dos primeiros usuários (`06 §10.1`).

Em `/operacao/cobertura`, duas listas que nunca devem ser somadas: **falta vocabulário** (a busca não reconheceu nada — acrescente um termo) e **falta quem ofereça** (reconheceu e não achou — recrute alguém). Medir as duas juntas mandaria a Operação recrutar quando bastava um sinônimo.

**Requisitos adiados por dependência de fatia**, não por decisão — estão anotados no próprio código:

| Requisito | Espera por |
|---|---|
| RF-SEG-02 / RF-SEG-10 / RF-NOT-08 / RF-NOT-11 — avisos ao **contato de confiança** | Provedor de SMS |

O contato de confiança não é usuário da plataforma e nunca aceitou termo algum (`05 §14.3`): o aviso a ele não cabe na tabela de notificações, que é endereçada a contas. Depende do provedor de SMS, ainda não escolhido (`05 §15.1`).

## Lacunas de implementação

Encontradas em auditoria, e diferentes das acima: **não estão adiadas por dependência, estão simplesmente ausentes.**

| Requisito | Situação |
|---|---|
| ~~`RF-CAD-04` a `RF-CAD-06` — verificação de e-mail e telefone~~ | ✅ **Feita.** Código cifrado, validade e limite de erros por parâmetro, tela em `/perfil/contato` e selo no perfil. Enquanto não há provedor, o código fica legível em `envios` — é assim que um cadastro se completa hoje. |
| ~~`RF-CAD-08` — recuperação de senha~~ | ✅ **Feita.** Token cifrado, uso único, validade por parâmetro. O pedido é silencioso por decisão: e-mail que existe e que não existe respondem igual (NF-SEG-05). |
| ~~`RF-CAD-11` a `RF-CAD-16` — encerramento de conta~~ | ✅ **Feito.** Sai na hora quem não deve nada; quem tem ticket circulando entra em `ENCERRANDO`, para de ofertar e continua atendendo até o último ticket ser usado ou vencer. A rotina temporal encerra sozinha. |
| Envio de e-mail e SMS | `MAIL_MAILER=log` e nenhuma chamada de envio no domínio. As notificações são gravadas e ficam na tabela — ninguém é avisado fora do aplicativo. |

Sobrou uma: o **envio de verdade**. É o que falta para as demais servirem a alguém — verificação e recuperação estão construídas e testadas, mas os códigos e os links ficam presos em `envios`.

### A camada de saída já existe

O que falta é o provedor, não a estrutura. `app/Dominio/Saida/` traz a interface `CanalDeSaida`, o valor `Mensagem`, o despachante e a tabela `envios`.

Contratar um provedor é escrever a classe que implementa a interface, registrá-la em `config/saida.php` e mudar `SAIDA_CANAL` no ambiente. Nada mais na aplicação precisa saber quem entrega.

**A saída é endereçada a um endereço, não a uma conta** — e essa distinção é a razão de ela não caber em `notificacoes`. O contato de confiança não tem conta (`05 §14.3`), então sem esta camada `RF-SEG-02` não teria onde existir.

O canal em uso é o `registrado`: grava e não envia. Ele **nunca marca "entregue"**, e é assim que se enxerga, olhando o painel, que nada saiu da plataforma.

**Enquanto o canal for o `registrado`, o piloto depende de alguém abrir a lista de envios** para ler um código de verificação ou um link de recuperação. Funciona para testar o produto entre pessoas próximas; não funciona com gente que você não pode ligar e avisar.

## O domínio está completo

Todas as sete fatias de `06 §5.3` foram construídas, e as rotinas temporais fazem os prazos correrem sozinhos: proposta vencida, janela de contestação, confirmação tácita, expiração de ticket, cobrança de avaliação e fila de aviso.

```
docker compose run --rm app php artisan ajudeme:rotinas
```

Agendado a cada dez minutos em `routes/console.php`. **O agendador do Laravel precisa estar rodando em produção** — sem ele, nenhum prazo do produto se cumpre.

## Camada web — primeira volta construída

Blade + Livewire sobre o domínio, na linguagem visual da seção 7.2, instalável como PWA (decisão A1).

| Tela | Situação |
|---|---|
| Entrada e cadastro | ✅ |
| Início: feed neutro + busca por necessidade | ✅ |
| Página do favor com todas as condições | ✅ |
| Carteira | ✅ |
| Oferecer ajuda | ✅ |
| Montar proposta · Negociação com chat | ✅ |
| Pedir o favor, com termo de ciência no presencial | ✅ |
| Perfil próprio e público | ✅ |
| Atendimento: contato liberado, comparecimento, emergência, confirmar/contestar | ✅ |
| Cancelar, com a consequência dita antes | ✅ |
| Avaliar | ✅ |
| Diagnóstico do aparelho (instrumento de A1) | ✅ |
| Moderação no aplicativo: denunciar e bloquear | ✅ |
| Painel da Operação: chamados, denúncias, ocorrências, termo, auditoria | ✅ |
| Convites de acesso da base restrita (RF-ADM-08) | ✅ |
| Métricas de `03 §7` no painel (RF-ADM-09) | ✅ |
| Necessidades não atendidas e vocabulário (RF-ADM-16 a 18) | ✅ |
| Confirmar contato, com o selo no perfil (RF-CAD-04 a 06) | ✅ |
| Recuperação de senha (RF-CAD-08) | ✅ |
| Encerrar a conta, com o que se deve dito antes (RF-CAD-11 a 16) | ✅ |

```
cd plataforma
docker compose up -d
docker compose run --rm app php artisan migrate --seed
docker compose run --rm app php artisan db:seed --class=DemonstracaoSeeder
npm install && npm run build
```

Entre em `http://localhost:8000` com `marta@exemplo.br` ou `joel@exemplo.br`, senha `segredo123`.

## O que falta para a Fase 1

O loop está completo e o piloto tem como ser operado. Restam três coisas:

| Falta | Natureza |
|---|---|
| Métricas de `03 §7` no painel (RF-ADM-09) | Sem elas, o portão da Fase 2 não é apurável |
| Necessidades não atendidas e vocabulário (RF-ADM-16 a 18) | O mapa do que falta na região já é coletado; falta a tela |
| Fechar a verificação de A1 | Ver abaixo — dois toques num aparelho |

O portão de saída (`06 §5.4`) — "duas pessoas percorrem o loop completo sem intervenção técnica" — já é verificável de ponta a ponta.

## Verificação de A1 em aparelho real — parcial

`05 §15.3` manda verificar A1 ainda na Fase 1, com aparelho real. A tela `/diagnostico` é o instrumento dessa verificação; o que segue foi medido, não presumido.

**Aparelho:** Xiaomi 2311DRK48G, Android 16, Chrome 151, 8 GB de RAM, 8 núcleos.

> **Ressalva que muda a leitura:** este é um aparelho topo de linha, o oposto do "Android antigo" de que `05 §15.3` desconfia. O que passa aqui não prova que passa no aparelho do público-alvo. A verificação precisa ser repetida em aparelho de entrada antes de dar A1 por confirmada.

| O que | Resultado |
|---|---|
| Instala e roda em modo standalone | ✅ verificado — abre com splash, sem barra de endereço |
| Contexto seguro, manifesto com ícones PNG | ✅ |
| Service worker registrado e ativo | ✅ |
| Sincronização em segundo plano e periódica | ✅ disponíveis |
| Push API presente | ✅ |
| Carregamento da página | ✅ 1,5 s |
| Armazenamento | ✅ cota de 10 GB |
| Vibração e wake lock | ✅ |
| **Notificação de fato exibida** | 🟡 **inconclusivo** |
| **Acionamento de emergência (`tel:`)** | 🟡 não verificado |

**Sobre o inconclusivo.** A notificação foi disparada pelo service worker e o navegador a reportou como postada, mas ela não apareceu no gerenciador de notificações do Android. A permissão havia sido concedida por automação (`Browser.grantPermissions` do protocolo DevTools), e o Chrome pode não percorrer o caminho real de entrega nesse modo — então **isto não é evidência de que a notificação falha**. Só um toque humano em "Pedir permissão" e depois em "Disparar notificação" resolve a dúvida.

As duas linhas amarelas são exatamente os dois riscos que `05 §15.3` nomeia. **Enquanto elas não fecharem, A1 não está confirmada.**

O APK de demonstração sai do empacotamento TWA sobre este mesmo PWA (`05 §15.2`).

## Nota de ambiente

O `vendor/` foi movido para um volume Docker. O bootstrap do Laravel abre centenas de arquivos por requisição, e a travessia do bind mount do Windows custava **15 segundos por página**; com o vendor no sistema de arquivos do Linux, caiu para **0,5s**. O ciclo de desenvolvimento era inviável antes disso.

Consequência: `composer install` roda dentro do contêiner, e o `vendor/` do host fica desatualizado de propósito — coerente com a decisão de que tudo roda em contêiner.

## Como rodar

O projeto vive em `plataforma/`. **Não é preciso ter PHP instalado** — tudo roda em contêiner:

```
cd plataforma
docker compose up -d
docker compose run --rm app php artisan migrate --seed
```

A aplicação sobe em `http://localhost:8000` e o banco em `localhost:5433` (5432 costuma estar ocupada por outro projeto).

Instalar o **Laravel Herd** deixa o ciclo bem mais rápido, mas é opcional — o Docker cobre tudo.

Os testes rodam contra PostgreSQL, não SQLite — o esquema usa PostGIS e restrições `CHECK` que o SQLite não reproduz:

```
docker compose run --rm app php artisan test
```

**Stack:** Laravel 13 · PHP 8.4 · PostgreSQL 16 com PostGIS · Filament 5

**Equipe:** uma pessoa. Isso é restrição de planejamento, não detalhe — pesa sobre a carga de operação manual prevista nas fases 2 e 3 do `06`.

---

# 4. Documentação

Toda a série está escrita e sincronizada entre si. Cada documento referencia os anteriores por seção.

| Documento | Versão | Conteúdo |
|---|---|---|
| `01 - Visão do Produto.md` | 1.0 | Identidade, propósito, princípios |
| `02 - Conceito e Regras de Negócio.md` | 2.9 | Conceitos e regras — **a fonte de verdade** |
| `03 - MVP.md` | 1.3 | Escopo da primeira versão e dívidas assumidas |
| `04 - Requisitos Funcionais.md` | 1.9 | Requisitos rastreáveis em 14 módulos |
| `05 - Arquitetura Conceitual.md` | 1.2 | Contextos, entidades, 27 invariantes, decisões técnicas |
| `06 - Roadmap do Produto.md` | 1.0 | Seis fases com portões de evidência |
| `07 - Minutas Jurídicas.md` | 1.0 | Briefing de fatos, perguntas e minutas para a consultoria jurídica |

`_arquivo/` guarda o rascunho fundador `CoreV1`, **encerrado e sem valor de referência**.

---

# 5. Decisões estruturais — não redecidir

Estas foram tomadas com justificativa registrada. Reabri-las exige motivo novo, não desconhecimento.

| # | Decisão | Onde |
|---|---|---|
| 1 | **O MVP não tem dinheiro.** Nenhum pagamento, taxa ou intermediação. | `03 §1.1` |
| 2 | **A plataforma descreve o favor, não o precifica.** Descritor = tempo estimado × nível de proficiência. Sem moeda, saldo ou acumulador — proibido inclusive no modelo de dados. | `02 §6`, `05 P8` |
| 3 | **O ticket é direito, não dinheiro.** Circula entre pessoas sem precisar ser consumido. | `02 §5` |
| 4 | **Ticket emitido não desaparece.** Nem por desativação de favor, nem por encerramento de conta, nem por bloqueio. | `02 §20.3` |
| 5 | **Limite de tickets é de circulação simultânea**, não vitalício. Consumido libera vaga. | `02 §5.1` |
| 6 | **Condição publicada é imutável.** Favor congela na primeira emissão. | `02 §20.2` |
| 7 | **Não existe pedido de ajuda como entidade.** Existe busca por necessidade em linguagem natural, mapeada para proficiências. | `02 §3.4` |
| 8 | **O silêncio confirma.** Só a contestação ativa impede o encerramento de um favor. | `02 §8.4` |
| 9 | **Avaliação é obrigatória, pública, imutável, bilateral e por favor.** Escala 0 a 7, três critérios por lado. | `02 §11` |
| 10 | **Proficiência ≠ Certificação ≠ Reputação.** Nenhuma altera a outra automaticamente. | `02 §3.3` |
| 11 | **Segurança presencial e proteção jurídica são complementares.** Termo de ciência não substitui curadoria de categorias. | `02 §8.2` |
| 12 | **Concentrar antes de crescer.** Rede de troca depende de densidade geográfica. | `06 §2` |

---

# 6. Decisões abertas

## 6.1 Fase 0 — fechada

| Decisão | Resultado | Onde |
|---|---|---|
| Nível de proficiência no favor | Congelado na primeira emissão | `05 §14.1` |
| Exclusão de dados pessoais | Anonimização, preservando o histórico | `05 §11.4` |
| Texto original das buscas | Retido 90 dias; agregado permanente | `05 §11.5` |
| Mensagens de negociação | Retidas 1 ano após o encerramento | `05 §12` |
| Plataforma cliente | Web responsiva instalável | `05 §15` |
| Armazenamento | Relacional com transações fortes | `05 §15` |
| Integração entre contextos | Aplicação única com fronteiras internas | `05 §15` |
| Eventos de domínio | Síncronos, com fronteira preparada | `05 §15` |
| Expiração de ticket | Avaliação por estado | `05 §15` |
| Reconhecimento de necessidade | Termos populares curados | `05 §15` |

## 6.2 Parâmetros temporais — definidos

| Parâmetro | Valor |
|---|---|
| Janela de contestação | 72 h |
| Manifestação das partes na análise | 5 dias |
| Avaliação pendente antes da restrição | 7 dias |
| Validade da proposta de troca | 7 dias |
| Antecedência de cancelamento sem ocorrência | 24 h |
| Validade máxima do ticket | 1 ano |
| Conta em Encerrando | Sem prazo máximo |

**Regra:** são parâmetros versionados com vigência, nunca constantes no código (`05 §9`).

## 6.3 Trabalho jurídico a contratar

Termo de ciência de riscos · escopo do acionamento de emergência · natureza jurídica da troca sem dinheiro · base legal do contato de confiança · política de privacidade · termos de uso.

## 6.4 Lacuna de produto

**Disputa por qualidade** (`04 L11`): a análise por chamado resolve *se o favor aconteceu*, não o caso em que ambos concordam que aconteceu mas divergem sobre o resultado. Vence na Fase 4.

---

# 7. Design

## 7.1 Telas prontas

Canvas em três páginas, 18 telas:

| Página | Telas |
|---|---|
| **Encontrar e trocar** | Início · Busca por necessidade · Busca sem resposta · Página do favor · Montar proposta · Negociação · Carteira · Conversas · Perfil |
| **Oferecer ajuda** | 4 passos: habilidade → a ajuda → quando e onde → os tickets |
| **Receber a ajuda** | Pedir o favor · Combinado · Durante o atendimento · Aconteceu? · Avaliação |

Todas as telas de percurso do loop existem. O que falta agora são estados secundários — vazios, erros, carregamento — e o cadastro.

**Inconsistência conhecida:** a página do favor exibe reputação com estrela; a tela de avaliação usa barras de 7 segmentos. Definir se estrela fica como símbolo compacto de leitura e barras só na atribuição da nota.

## 7.2 Linguagem visual

Decidida com o autor. "Clean com calor" — **nunca** glassmorphism, aero ou blur: custa desempenho em aparelho de entrada e destrói contraste sob sol.

| Elemento | Valor |
|---|---|
| Fundo | `#FAF7F2` (creme quente) |
| Superfície | `#FFFFFF`, borda `#EDE6DC` |
| Texto | `#1F1C19` · secundário `#857A6E` · terciário `#9A9086` |
| Acento | `#2E6B4F` (verde quente). Alternativas no canvas: terracota `#B0563A`, oliva `#6E6136` |
| Emergência | `#B3261E` — **único uso de vermelho no app** |
| Aviso | fundo `#FBF4E9`, borda `#EFE2CC`, texto `#6B5626` |
| Cantos | 14–22px · pílulas 999px |
| Sombra | `0 1px 2px rgba(31,28,25,0.04)` — discreta |
| Tipografia | Fonte do sistema, por exigência de desempenho (`05 NF-ACE-01`) |
| Alvo de toque | 44px mínimo |

**Barra inferior:** Início · Carteira · **[Oferecer]** · Conversas · Perfil. O círculo central é deliberado — dá a ofertar o mesmo peso que a descobrir, contra o risco de a rede encher de demanda e esvaziar de oferta (`06 R2`).

**Descritor:** pílula ocupando o lugar onde estaria o preço — `≈ 1h · Especialista`, com o nível em cor de acento para forçar a leitura das duas dimensões.

## 7.3 Como editar o canvas

Fontes em `design/*.dc.html` (um arquivo por tela) e `design/canvas.json` (posições, páginas, notas). Editar os fontes, re-gerar com o utilitário da skill `design` e republicar no mesmo endereço.

---

# 8. Vocabulário obrigatório

Favor · Ticket · Carteira · Proposta · Contrapartida · Ajudante · Solicitante · Proficiência · Descritor · Necessidade · Ocorrência · Chamado.

**Nunca usar:** serviço, preço, comprar, vender, contratar, profissional, cliente.

> *Ticket* designa exclusivamente o direito de receber um favor. O registro de atendimento da Operação chama-se **chamado** — usar "ticket" para suporte tornaria ambíguo o conceito central do produto.

---

# 9. Como trabalhamos

Convenções que mantiveram a documentação coerente até aqui:

1. **Decisão tomada é decisão sincronizada.** Toda escolha volta aos documentos afetados na mesma sessão — o `04` e o `05` derivam do `02`, e drift entre eles é o modo de falha mais provável.
2. **Documento alterado é documento versionado**, com histórico de versões no rodapé dizendo o que mudou.
3. **Requisitos têm identificador estável** e nunca reaproveitado.
4. **O motivo importa tanto quanto a regra.** Decisões trazem a justificativa junto, para que uma sessão futura saiba o que reabrir e o que não.
5. **Pendência é registrada, não esquecida** — com a fase em que deve ser resolvida (`06 §12`).
6. **Requisito sem regra de origem é marcado `—`** e consolidado em `04 §18`: é decisão implícita esperando confirmação.

---

# 10. Próximo passo

**As fases 0 e 1 estão fechadas.** O domínio está completo, a camada web tem a primeira volta do loop, o painel da Operação decide e mede.

O que separa o projeto da **Fase 2 — piloto fechado** (`06 §6.2`) não é mais código:

| Pré-requisito | Situação |
|---|---|
| Termo de ciência, termos de uso e política de privacidade **publicados** | 🟡 Depende do trabalho jurídico (seção 6.3) |
| Base legal do contato de confiança **definida** | 🟡 Depende do trabalho jurídico |
| Categorias de risco físico e cuidado de vulneráveis **bloqueadas** | ✅ Curadoria disponível no painel |
| Canal de emergência testado ponta a ponta | 🟡 Depende do provedor de SMS (`05 §15.1`) |
| Comunidade-piloto escolhida | 🟡 Pendente (`06 §4.5`) |

Três dos cinco itens dependem de decisões externas ao código — e o jurídico é o mais lento, então é o que deve começar primeiro.

Continua valendo o que está em `06 §11`: as duas hipóteses que decidem o produto — reciprocidade e aceitação da troca sem dinheiro — podem ser observadas com um grupo pequeno e mediação manual. Agora que o software existe, essa alternativa deixou de economizar meses, mas continua servindo para testar a comunidade antes de convidá-la para o aplicativo.
