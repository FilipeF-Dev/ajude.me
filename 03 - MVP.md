# Ajude.me — MVP

> Documento que define o escopo da primeira versão funcional do Ajude.me. O objetivo deste documento não é descrever tudo o que o produto será, mas sim o mínimo necessário para que o ciclo de ajuda aconteça de ponta a ponta entre pessoas reais.
>
> Este documento **restringe** o escopo definido em `01 - Visão do Produto.md` e `02 - Conceito e Regras de Negócio.md`. Ele não altera conceitos — apenas decide o que entra agora e o que fica para depois.

---

# 1. Objetivo do MVP

O MVP do Ajude.me existe para responder a uma única pergunta:

> **Pessoas estão dispostas a trocar habilidade por habilidade, sem dinheiro no meio?**

Toda funcionalidade incluída neste escopo existe porque é indispensável para que essa troca aconteça e possa ser observada. Tudo o que não for indispensável foi deliberadamente adiado.

## 1.1 Decisão fundadora do MVP

> **O MVP não terá dinheiro.**

Nenhuma operação financeira, nenhum pagamento, nenhuma taxa, nenhuma intermediação de valores.

A justificativa é direta:

1. O dinheiro é o caminho mais fácil e, por isso, o mais perigoso nesta fase — se existir, ele será usado, e a tese central (habilidade como moeda) nunca será testada de verdade.
2. Operação financeira traz gateway, antifraude, tributação, estorno, proteção ao consumidor e obrigações regulatórias — peso que inviabiliza uma primeira versão.
3. O modelo de dinheiro ainda está pendente de definição no documento de regras de negócio (seções 15 e 16).

Se a troca pura funcionar, o dinheiro pode ser adicionado depois como complemento. Se a troca pura não funcionar, o produto precisa ser repensado — e nenhum gateway de pagamento resolveria isso.

---

# 2. Hipóteses que o MVP precisa validar

| # | Hipótese | Como saberemos |
|---|---|---|
| H1 | Pessoas conseguem descrever suas habilidades como favores ofertáveis | % de usuários cadastrados que criam ao menos um favor |
| H2 | Pessoas encontram favores que realmente desejam | % de buscas/sessões que terminam em proposta enviada |
| H3 | Pessoas aceitam trocar habilidade por habilidade sem dinheiro | Nº de propostas aceitas / propostas enviadas |
| H4 | O favor negociado é efetivamente realizado no mundo real | Nº de consumos concluídos / tickets transferidos |
| H5 | A experiência gera reciprocidade e não apenas extração | % de usuários que **prestam** e **recebem** ajuda |

**H5 é a hipótese mais importante.** Um MVP cheio de solicitantes e vazio de ajudantes é um MVP fracassado, mesmo com números altos de cadastro.

---

# 3. O loop mínimo

O MVP entrega um único fluxo completo, do início ao fim:

> **Cadastrar → Declarar proficiência → Criar favor → Emitir ticket → Descobrir → Propor troca → Negociar → Aceitar → Solicitar consumo → Realizar → Avaliar**

```
┌─────────────┐     ┌──────────────┐     ┌────────────┐
│  Cadastro   │────▶│ Proficiência │────▶│   Favor    │
└─────────────┘     └──────────────┘     └─────┬──────┘
                                               │ emite
                                               ▼
┌─────────────┐     ┌──────────────┐     ┌────────────┐
│  Descoberta │◀────│   Vitrine    │◀────│   Ticket   │
└──────┬──────┘     └──────────────┘     └────────────┘
       │ propõe troca
       ▼
┌─────────────┐     ┌──────────────┐     ┌────────────┐
│   Proposta  │────▶│  Negociação  │────▶│   Aceite   │
└─────────────┘     └──────────────┘     └─────┬──────┘
                                               │ tickets trocam de carteira
                                               ▼
┌─────────────┐     ┌──────────────┐     ┌────────────┐
│  Avaliação  │◀────│  Realização  │◀────│ Solicitação│
│ (bilateral) │     │              │     │ de consumo │
└─────────────┘     └──────────────┘     └────────────┘
```

Nenhuma etapa deste loop pode ser removida sem quebrar a validação do produto. Qualquer coisa fora deste desenho está fora do MVP.

---

# 4. Escopo incluído

## 4.1 Cadastro e identidade

**Incluído:**

- Cadastro com nome, e-mail, telefone, CPF e localização.
- Um CPF = um perfil (unicidade garantida).
- Validação de CPF por dígito verificador e checagem de unicidade.
- Verificação de e-mail por link/código.
- Verificação de telefone por código SMS.
- Login e recuperação de senha.
- Desativação de conta pelo próprio usuário.

**Simplificação assumida no MVP:**

A validação documental de identidade (KYC com foto de documento, prova de vida, consulta a base oficial) **não entra no MVP**. O selo exibido será apenas **"Contato verificado"**, e não "Identidade verificada".

> Isso é uma dívida consciente. O documento de regras exige validação de identidade real, e ela será necessária antes de qualquer abertura pública ampla. No MVP, o risco é mitigado pela escala reduzida e pela operação assistida.

**Status:** 🟡 Validação documental de identidade adiada — obrigatória antes da abertura pública.

### Desativação com tickets em circulação

Regra do MVP:

- A conta é desativada imediatamente para **novas** atividades (criar favor, emitir ticket, receber propostas).
- Os tickets já emitidos pelo usuário permanecem válidos até o fim de sua validade.
- A conta entra em estado **"Encerrando"** e só é efetivamente desativada quando todos os tickets em circulação forem consumidos ou expirarem.
- Durante esse período, o usuário continua obrigado a atender solicitações que respeitem as condições dos tickets.

Sem dinheiro no MVP, não existe compensação financeira possível — logo, a única proteção viável ao portador do ticket é o **prazo**. Essa é a alternativa já prevista na seção 2.2 do documento de regras.

---

## 4.2 Proficiências

**Incluído:**

- Catálogo de proficiências pré-cadastrado pela plataforma, organizado em Categoria → Subcategoria → Proficiência.
- Usuário adiciona e remove proficiências de seu perfil.
- Cada proficiência do usuário recebe um nível declarado.

**Níveis definidos para o MVP:**

| Nível | Significado |
|---|---|
| Básico | Consigo ajudar em situações simples |
| Intermediário | Tenho prática regular nesta atividade |
| Avançado | Tenho domínio consolidado |
| Especialista | Atuo profissionalmente nesta área |

O nível é **declarado** e permanece declarado. Nenhuma avaliação altera o nível automaticamente — conforme a seção 3.3 do documento de regras.

**Fora do MVP:** cadastro e verificação de certificações. O usuário poderá mencionar formação no texto livre do perfil, sem qualquer selo de verificação.

> Justificativa: certificação verificada exige um processo operacional de conferência que ainda não está definido, e um selo de verificação falso é pior do que selo nenhum.

---

## 4.3 Favores

**Incluído:**

Criação de favor vinculado obrigatoriamente a uma proficiência declarada no perfil. Campos do MVP:

| Campo | Obrigatório |
|---|---|
| Proficiência relacionada | Sim |
| Título | Sim |
| Descrição | Sim |
| Tempo estimado de realização | Sim |
| Modalidade (presencial / remoto) | Sim |
| Área de atendimento ou raio de distância | Sim, se presencial |
| Dias e horários disponíveis | Sim |
| Prazo mínimo de antecedência para solicitação | Sim |
| Validade do ticket | Sim |
| Quantidade máxima de tickets | Sim |
| Tickets a emitir agora | Sim |
| Ticket é trocável | Sim |
| Condições, exceções e avisos | Não |

O **nível de proficiência** não é um campo do favor: ele é herdado da proficiência vinculada e compõe, junto ao tempo estimado, o descritor de equivalência definido na seção 4.4.

- Edição do favor **só é permitida enquanto não houver ticket emitido**. Depois disso, o favor fica congelado e só pode ser desativado. Isso protege a regra de que as condições devem ser conhecidas antes da negociação.
- Desativação do favor impede novas emissões, mas **não invalida tickets já emitidos**.

**Fora do MVP:** transferência condicional (autorização caso a caso do criador). No MVP o ticket é apenas **trocável** ou **não trocável** — booleano.

---

## 4.4 Valor e equivalência do favor

O documento de regras define `valor = valor/hora × tempo estimado` e deixa a unidade pendente.

### O problema com o tempo como medida

O tempo mede esforço, não conhecimento. E o que caracteriza um profissional não é saber apertar um parafuso — é saber **qual** parafuso apertar.

Um diagnóstico elétrico de 30 minutos feito por quem sabe o que está olhando pode resolver um problema que quatro horas de tentativa e erro não resolveriam. Medir o favor pelo tempo pune exatamente quem é bom, porque quem é bom é rápido.

Qualquer modelo de valor do Ajude.me precisa carregar as duas dimensões: **quanto custa** e **o que exige**.

### Decisão do MVP

> **O Ajude.me não precifica o favor. Ele o descreve.**
>
> **Descritor do favor = tempo estimado × nível da proficiência vinculada**

Não existe unidade, não existe saldo, não existe moeda, não existe acúmulo. O usuário **não** define um valor para sua hora.

Os dois eixos do descritor já existem no modelo e não exigem nenhum campo novo:

- **Tempo estimado** — já é campo obrigatório do favor (seção 4.3).
- **Nível de proficiência** — já é declarado no perfil (seção 4.2), e todo favor é obrigatoriamente vinculado a uma proficiência. O favor **herda** o nível.

Apresentação ao usuário:

| Favor | Descritor |
|---|---|
| Diagnóstico elétrico | ≈ 30min · **Especialista** |
| Ajuda em mudança | ≈ 4h · **Básico** |
| Aula de matemática | ≈ 2h · **Avançado** |

Nenhum dos dois lados precisa que a plataforma diga qual vale mais. As duas pessoas olham o descritor e negociam.

> Justificativa: isto é o que **"a necessidade dita o valor"** significa operacionalmente. O que trava uma negociação não é a ausência de um número — é a assimetria de informação. Quem recebe a proposta precisa de descrição suficiente para julgar se a troca é justa, e não de um preço imposto por terceiros.

### Proteção contra inflação de nível declarado

Declarar-se Especialista sem ser não é gratuito. O nível fica exibido ao lado da reputação **daquela proficiência específica** (seção 4.12). Quem se declara Especialista e entrega Básico acumula avaliação negativa exatamente no ponto onde a declaração é falsa.

Nenhuma correção automática de nível é aplicada — conforme a seção 3.3 do documento de regras, proficiência é declaração e reputação é evidência. As duas convivem visíveis, e a divergência entre elas é a informação.

### Alternativas avaliadas e descartadas

| Alternativa | Motivo do descarte |
|---|---|
| **Cada usuário define o valor da própria hora** | Cria moeda interna de fato. Traz precificação, arbitragem, inflação e acúmulo — e converte o Ajude.me no marketplace que a visão do produto rejeita. |
| **Valor fixo: toda hora vale uma hora** | Ignora conhecimento e pune o especialista por ser rápido. |
| **Média de valor entre favores semelhantes** | A média é precisamente a operação que **apaga a variância** entre um bom e um mau profissional — justamente a informação que mais importa. Soma-se a isso: não há dado no lançamento (*cold start*); vira circular, porque a média passa a se alimentar de si mesma e congela o comportamento dos primeiros usuários; e um número exibido pela plataforma **é** um preço, com todos os efeitos de moeda interna. Há ainda a questão de a plataforma passar a afirmar publicamente quanto vale uma hora de determinado trabalho — decisão que deve ser avaliada juridicamente antes de qualquer implementação. |

### Evolução prevista — Trocas semelhantes

A intuição de aproveitar o histórico coletivo é correta; o erro está em reduzi-la a um escalar. A versão viável, **posterior ao MVP**, preserva os exemplos em vez de mediá-los:

> **Trocas semelhantes:** este favor já foi trocado por → *aula de inglês (2h)*, *conserto de bicicleta*, *corte de cabelo*

É ancoragem por preferência revelada, baseada em trocas que realmente aconteceram, sem criar unidade de valor. Degrada bem: enquanto não houver histórico, simplesmente não aparece.

**Status:** 🟡 "Trocas semelhantes" previsto para versão futura. Unidade formal de valor permanece descartada por decisão de produto, não pendente.

---

## 4.5 Tickets e carteira

**Incluído:**

- Emissão de tickets a partir de um favor, respeitando a quantidade máxima.
- Cada ticket possui identificador único e rastreável.
- Regra `1 ticket = 1 realização`.
- Carteira com abas: **Disponíveis**, **Em negociação**, **Reservados**, **Consumidos**, **Expirados**.
- Carteira de Expirados separada, com opção de manter ou excluir.
- Histórico de propriedade do ticket (cadeia de transferências).

**Fora do MVP:** carteira de coleção, tickets raros, colecionáveis.

> Os tickets expirados já são preservados no MVP, o que mantém a porta aberta para a mecânica de colecionáveis sem construí-la agora.

### Máquina de estados do ticket

| Estado | Significado |
|---|---|
| `DISPONIVEL` | Na carteira do proprietário, livre para uso ou troca |
| `EM_NEGOCIACAO` | Bloqueado por uma proposta de troca ativa |
| `RESERVADO` | Vinculado a uma solicitação de consumo aceita |
| `EM_ANALISE` | Bloqueado por contestação da realização em apuração |
| `CONSUMIDO` | Favor realizado, aguardando avaliações |
| `ENCERRADO` | Ciclo completo, ambas as avaliações registradas |
| `EXPIRADO` | Validade atingida, movido para a Carteira de Expirados |
| `DESCARTADO` | Ticket expirado excluído pelo proprietário |

**Transições permitidas:**

```
DISPONIVEL ──proposta enviada──▶ EM_NEGOCIACAO
EM_NEGOCIACAO ──recusada/retirada/expirada──▶ DISPONIVEL
EM_NEGOCIACAO ──aceita──▶ DISPONIVEL (na carteira do novo proprietário)

DISPONIVEL ──solicitação aceita──▶ RESERVADO
RESERVADO ──cancelamento válido──▶ DISPONIVEL
RESERVADO ──realização confirmada ou tácita──▶ CONSUMIDO
RESERVADO ──realização contestada──▶ EM_ANALISE
EM_ANALISE ──decisão: realizado──▶ CONSUMIDO
EM_ANALISE ──decisão: não realizado ou inconclusivo──▶ DISPONIVEL
CONSUMIDO ──ambas avaliações──▶ ENCERRADO

DISPONIVEL ──validade atingida──▶ EXPIRADO
EM_NEGOCIACAO ──validade atingida──▶ EXPIRADO (proposta é cancelada)
EXPIRADO ──exclusão pelo usuário──▶ DESCARTADO
```

**Regra de proteção contra expiração:**

> Um ticket em estado `RESERVADO`, cuja realização foi agendada para uma data dentro da validade, **não expira** antes da conclusão daquela realização.
>
> Um ticket em `EM_ANALISE` tem a validade **suspensa** até a decisão da Operação.

A primeira regra impede que um ajudante deixe a data passar para inutilizar um ticket legítimo. A segunda impede que a demora da própria plataforma extinga um direito em disputa.

**Limite de circulação:**

> A quantidade máxima do favor limita os tickets **em circulação simultânea**, não o total emitido ao longo da vida do favor.

Tickets consumidos, expirados ou descartados liberam vaga para novas emissões. Um favor bem-sucedido nunca se esgota.

---

## 4.6 Descoberta

**Incluído:**

- Busca por texto livre.
- Filtro por categoria e subcategoria.
- Filtro por modalidade (presencial / remoto).
- Ordenação por proximidade geográfica.
- Feed inicial com favores próximos e recentes.
- Página do favor com todas as condições visíveis antes de qualquer negociação.
- Perfil público do usuário com proficiências, reputação e favores ativos.

**Fora do MVP:** recomendação personalizada, feed algorítmico, favores em destaque, filtros avançados por reputação.

**Decisão de identidade visual:** a tela inicial abre em um feed neutro de ajuda disponível, e não em um "perfil profissional". A escolha é coerente com a regra fundamental de que não existe divisão permanente entre quem presta e quem recebe ajuda.

---

## 4.7 Negociação e troca

Esta é a etapa mais crítica do MVP e a que mais depende de acerto de experiência.

**Fluxo incluído:**

1. O solicitante visualiza a vitrine de tickets de outro usuário.
2. Seleciona o ticket desejado e a quantidade, adicionando à **proposta**.
3. Monta a contrapartida escolhendo tickets da própria carteira.
4. Envia a proposta.
5. O outro usuário recebe a proposta e pode **aceitar**, **recusar** ou **abrir negociação**.
6. A negociação acontece em um **chat vinculado à proposta**, onde a oferta pode ser revisada por qualquer uma das partes.
7. No aceite, os tickets são transferidos entre as carteiras de forma atômica.

**Regras:**

- Tickets envolvidos em uma proposta ficam bloqueados (`EM_NEGOCIACAO`) e não podem participar de outra proposta simultaneamente.
- A proposta possui prazo de validade; expirado, os tickets são liberados.
- Tickets marcados como **não trocáveis** não podem compor uma contrapartida.
- A transferência é atômica: ou todos os tickets mudam de carteira, ou nenhum muda.
- **Não há dinheiro na proposta.** A contrapartida é exclusivamente composta por tickets.

### Máquina de estados da proposta

| Estado | Significado |
|---|---|
| `RASCUNHO` | Sendo montada, ainda não enviada |
| `ENVIADA` | Aguardando resposta do destinatário |
| `EM_NEGOCIACAO` | Chat aberto, oferta pode ser revisada |
| `ACEITA` | Tickets transferidos |
| `RECUSADA` | Encerrada pelo destinatário |
| `RETIRADA` | Encerrada pelo remetente |
| `EXPIRADA` | Prazo de resposta esgotado |

> Regra de negócio correspondente: seção 5.4 do `02 - Conceito e Regras de Negócio.md`.

---

## 4.8 Solicitação e consumo

**Incluído:**

- O portador do ticket solicita o favor escolhendo data e horário dentro das condições publicadas.
- O sistema impede solicitações fora das condições (dia, horário, antecedência mínima, validade, área de atendimento).
- O ajudante recebe a solicitação e confirma.
- O ajudante **só pode recusar** se a solicitação violar as condições publicadas. Solicitação dentro das condições deve ser atendida — conforme a seção 8 do documento de regras.
- Após a data, ambas as partes confirmam a realização.
- Divergência na confirmação abre uma **ocorrência**.

### Máquina de estados da solicitação

```
SOLICITADA ──confirmada pelo ajudante──▶ AGENDADA
SOLICITADA ──fora das condições──▶ RECUSADA
AGENDADA ──cancelada pelo ajudante──▶ CANCELADA_AJUDANTE
AGENDADA ──cancelada pelo solicitante──▶ CANCELADA_SOLICITANTE
AGENDADA ──data alcançada──▶ AGUARDANDO_CONFIRMACAO
AGUARDANDO_CONFIRMACAO ──ambos confirmam──▶ REALIZADA
AGUARDANDO_CONFIRMACAO ──janela encerrada sem contestação──▶ REALIZADA (tácita)
AGUARDANDO_CONFIRMACAO ──contestação──▶ EM_ANALISE
EM_ANALISE ──decisão: realizado──▶ REALIZADA
EM_ANALISE ──decisão: não realizado──▶ NAO_REALIZADA
EM_ANALISE ──decisão: inconclusiva──▶ INCONCLUSIVA
REALIZADA ──ambas avaliações──▶ ENCERRADA
```

### Confirmação da realização

> **O silêncio confirma. Somente a contestação ativa impede o encerramento.**

Se nenhuma das partes contestar dentro da janela, o favor é considerado realizado — ainda que ninguém tenha confirmado explicitamente. A alternativa manteria o ciclo aberto indefinidamente, prendendo o ticket e travando as avaliações de ambos.

Havendo contestação, a prestação vai para análise da Operação por meio de um **chamado**, no qual cada parte apresenta sua versão. Os desfechos possíveis são:

| Decisão | Ticket | Registro |
|---|---|---|
| Favor realizado | Consumido | Ocorrência contra quem contestou sem fundamento |
| Favor não realizado | Devolvido | Ocorrência contra o ajudante |
| Inconclusivo | Devolvido | Nenhuma ocorrência |

Não há avaliação quando o favor não se realizou: avaliar uma experiência inexistente distorceria a reputação de ambos.

> **Nota terminológica:** *ticket* designa apenas o direito de receber um favor. O registro de atendimento da Operação chama-se **chamado**.

### Compartilhamento de dados de contato

Regra do MVP, atendendo à seção 18 do documento de regras:

| Momento | Dados visíveis |
|---|---|
| Antes da proposta | Nome, foto, cidade/região aproximada, reputação |
| Durante a negociação | Chat interno apenas |
| Após a solicitação ser **agendada** | Telefone e endereço completo de atendimento |
| Após o encerramento | Volta ao estado anterior à negociação |

O endereço exato e o telefone **nunca** ficam públicos.

---

## 4.9 Segurança em atendimento presencial

O favor presencial coloca duas pessoas que não se conhecem no mesmo espaço físico, frequentemente na residência de uma delas. Este é o maior risco real do produto, e ele existe desde o primeiro atendimento.

### Princípio

> **Termo de ciência é papel, e papel não socorre ninguém.**

O termo protege a plataforma juridicamente, o que é necessário. Ele **não** reduz o risco do usuário. As duas coisas precisam existir juntas: a proteção jurídica e o mecanismo operacional.

### Incluído no MVP

| Mecanismo | Funcionamento |
|---|---|
| **Check-in / check-out** | Ambas as partes registram início e fim do atendimento presencial, com horário. Gera o registro objetivo usado em ocorrências e disputas. |
| **Contato de confiança** | O usuário indica uma pessoa que recebe automaticamente local, horário e identificação da contraparte quando um atendimento presencial é agendado. |
| **Botão de emergência** | Disponível durante a janela do atendimento, entre o check-in e o check-out. |
| **Termo de ciência específico** | Exibido no **aceite do atendimento presencial em residência** — não no cadastro, onde ninguém lê. |

O check-in e o check-out também servem à seção 4.8: são a evidência de comparecimento que alimenta a confirmação de realização.

### Curadoria de categorias no lançamento

A curadoria manual das categorias oferecidas **permanece no MVP mesmo com o termo assinado**.

> O termo cobre o risco jurídico. A curadoria cobre o risco de acontecer.

Ficam fora do lançamento os favores que envolvam risco físico relevante, cuidado de pessoas vulneráveis (crianças, idosos, pessoas com deficiência) ou acesso a residência sem contexto prévio. Essas categorias serão reabertas conforme os mecanismos de identidade e certificação amadurecerem (dívidas D1 e D2).

### Apoio jurídico

A redação do termo de ciência e das opções de suporte de emergência será conduzida com apoio jurídico externo.

Dois pontos adicionais devem entrar no escopo dessa consultoria:

1. **Natureza jurídica da troca sem dinheiro.** Um favor recorrente, com horário combinado e obrigação de comparecimento, possui elementos que merecem análise quanto a vínculo e responsabilidade. É preferível esclarecer isso antes de haver base de usuários.
2. **Exibição de valor pela plataforma.** Caso a plataforma venha futuramente a exibir qualquer valor de referência para trabalho (ver seção 4.4), ela passa a afirmar publicamente quanto vale determinada hora de serviço. A implicação disso precisa ser avaliada antes de qualquer implementação.

**Status:** 🟡 Redação do termo e escopo do suporte de emergência pendentes de apoio jurídico.

---

## 4.10 Cancelamentos e ocorrências

**Incluído:**

- Cancelamento pelo ajudante: justificativa obrigatória, notificação ao solicitante, registro no histórico, ticket retorna a `DISPONIVEL`.
- Cancelamento pelo solicitante: permitido respeitando antecedência mínima; ticket retorna a `DISPONIVEL`.
- Registro de ocorrência pelos dois lados.
- Contador de cancelamentos visível internamente.

**Fora do MVP:** aplicação automática de penalidades, suspensão automática, processo formal de disputa, upload de evidências.

No MVP, ocorrências são **registradas e acumuladas**, e a análise é feita manualmente pela operação. Isso é viável na escala inicial e evita construir um sistema de justiça automatizado antes de entender quais conflitos realmente acontecem.

**Status:** 🟡 Penalidades automáticas e processo de disputa permanecem pendentes.

---

## 4.11 Avaliações

Avaliação é obrigatória, pública, imutável e bilateral.

**Escala:** 0 a 7 estrelas, conforme a seção 11.3 do `02 - Conceito e Regras de Negócio.md`.

**Critérios ao avaliar o Ajudante:**

| Critério | Significado |
|---|---|
| Comunicação | Facilidade de contato e clareza |
| Qualidade | Qualidade da ajuda prestada |
| Satisfação | Satisfação geral com o favor |

**Critérios ao avaliar o Solicitante:**

| Critério | Significado |
|---|---|
| Cordialidade | Educação e hospitalidade |
| Comunicação | Facilidade de contato e clareza |
| Condições | Condições reais em que o favor foi prestado |

**Regras:**

- A avaliação só é liberada após a realização confirmada.
- Ambas as partes devem avaliar; enquanto uma não avaliar, a outra fica pendente e o ciclo não encerra.
- A avaliação é imutável após o envio.
- A avaliação é vinculada ao **favor específico**, não ao usuário genericamente — a mesma pessoa pode ter reputações distintas em favores distintos.
- Comentário em texto é opcional; as notas são obrigatórias.

**Bloqueio por avaliação pendente:** um usuário com avaliação pendente há mais de um prazo definido não pode enviar novas propostas. É o mecanismo mais simples de garantir a obrigatoriedade sem inventar punições.

> Regras de negócio correspondentes: seções 11.3 e 11.4 do `02 - Conceito e Regras de Negócio.md`.

---

## 4.12 Reputação

**Incluído no MVP — apenas exibição:**

- Média geral como Ajudante.
- Média geral como Solicitante.
- Médias por critério individual.
- Quantidade de favores prestados e recebidos.
- Tempo de participação na plataforma.

**Fora do MVP:** limites e privilégios baseados em reputação (seção 13 do documento de regras).

> Justificativa: não existe base histórica para calibrar limites. Aplicar limites sobre dados inexistentes produziria regras arbitrárias. Primeiro coletamos o comportamento real, depois definimos os limites.

---

## 4.13 Bloqueios

**Incluído:**

- Bloquear um usuário específico (impede propostas e visualização mútua).
- Denunciar um usuário ou favor.

Regra de negócio correspondente: seção 18.2 do `02 - Conceito e Regras de Negócio.md`.

**Fora do MVP:** bloqueio por endereço ou região.

---

## 4.14 Notificações

**Incluído — apenas os eventos do loop:**

- Proposta recebida, aceita ou recusada.
- Nova mensagem no chat de negociação.
- Solicitação recebida, agendada ou cancelada.
- Lembrete de check-in e de check-out em atendimento presencial.
- Aviso ao contato de confiança quando um atendimento presencial é agendado.
- Confirmação de realização pendente.
- Avaliação pendente.
- Ticket próximo do vencimento.

Sem notificações de marketing, engajamento ou recomendação.

---

# 5. Escopo explicitamente excluído

| Item | Motivo da exclusão |
|---|---|
| Dinheiro em qualquer forma | Decisão fundadora — contaminaria a validação da tese |
| Compra direta de tickets | Depende de dinheiro |
| Monetização e planos pagos | Não há o que monetizar antes de haver uso |
| Garantias, reembolso e proteção | Dependem de dinheiro |
| Validação documental de identidade | Processo operacional não definido; mitigado pela escala reduzida |
| Certificações verificadas | Processo de verificação não definido |
| Tickets colecionáveis e raros | Conceito futuro; a base (expirados preservados) já fica pronta |
| Limites por nível de confiança | Sem dados históricos para calibrar |
| Penalidades automáticas | Sem repertório de conflitos reais |
| Processo formal de disputa | Análise manual é suficiente na escala inicial |
| Upload de evidências | Depende do processo de disputa |
| Conta empresarial / CNPJ | Fora do foco no público principal |
| Transferência condicional de ticket | Complexidade de fluxo desproporcional ao ganho |
| Feed algorítmico e recomendação | Sem dados para alimentar |
| Bloqueio por endereço/região | Bloqueio por usuário resolve o caso principal |
| Valor da hora definido pelo usuário | Cria moeda interna de fato (ver seção 4.4) |
| Média de valor entre favores semelhantes | Apaga a variância entre bom e mau profissional (ver seção 4.4) |
| "Trocas semelhantes" (histórico de equivalências) | Depende de histórico inexistente no lançamento — previsto para versão futura |
| Categorias de risco físico e cuidado de vulneráveis | Curadoria de lançamento (ver seção 4.9) |

---

# 6. Simplificações assumidas — a dívida do MVP

Estas decisões foram tomadas para viabilizar o MVP e **precisam ser revisitadas** antes de qualquer abertura pública ampla:

| # | Simplificação | Risco assumido | Quando revisitar |
|---|---|---|---|
| D1 | Sem validação documental de identidade | Perfis falsos, mesma pessoa com múltiplos CPFs de terceiros | Antes da abertura pública |
| D2 | Sem certificação verificada | Usuários declaram competências que não possuem | Quando surgirem favores de risco (elétrica, saúde, cuidados) |
| D3 | Análise manual de ocorrências | Não escala além de algumas centenas de usuários | Ao atingir volume que sature a operação |
| D4 | Sem limites por reputação | Usuário novo pode emitir muitos tickets e não honrar | Assim que houver base de dados de comportamento |
| D5 | Equivalência descritiva, sem referência de mercado | Duas pessoas podem não chegar a acordo por falta de parâmetro externo | Quando houver histórico suficiente para "trocas semelhantes" |
| D6 | Proteção por prazo no encerramento de conta | Portador pode ficar sem o favor se o prazo não bastar | Junto com o modelo de compensação |
| D7 | Nível de proficiência é autodeclarado e compõe o descritor de valor | Inflação de nível para obter trocas mais vantajosas | Monitorar divergência entre nível declarado e reputação obtida |

> **Riscos D1 e D2 são os mais sérios.** O MVP deve operar com base restrita e convidada, e com curadoria manual das categorias de favor oferecidas (seção 4.9) — evitando, na largada, favores que envolvam risco físico, cuidado de pessoas vulneráveis ou acesso a residências sem contexto prévio. O termo de ciência não substitui essa curadoria; os dois mecanismos são complementares.

---

# 7. Critério de pronto

O MVP estará pronto quando:

> Duas pessoas que não se conhecem conseguirem, sem qualquer suporte da equipe, percorrer o loop completo — cadastro, criação de favor, descoberta, proposta, negociação, aceite, solicitação, realização e avaliação bilateral — e ambas terminarem a experiência dispostas a repetir.

Métricas de acompanhamento:

| Métrica | Por que importa |
|---|---|
| Usuários que criaram ao menos um favor | Mede se a oferta existe (H1) |
| Propostas enviadas por usuário ativo | Mede intenção real (H2) |
| Taxa de aceite de propostas | Mede se a troca sem dinheiro funciona (H3) |
| Consumos concluídos / tickets transferidos | Mede se a ajuda sai do papel (H4) |
| % de usuários que prestaram **e** receberam ajuda | Mede reciprocidade (H5) |
| Taxa de cancelamento por lado | Mede confiabilidade |
| Tempo do cadastro até a primeira troca | Mede atrito do fluxo |

---

# 8. Rastreabilidade com o documento de regras

Este documento está alinhado à versão **2.0** do `02 - Conceito e Regras de Negócio.md`.

Correspondência entre as seções deste MVP e as regras que as sustentam:

| MVP | Regra de negócio |
|---|---|
| 4.1 Cadastro e identidade | 2.1, 2.2 |
| 4.2 Proficiências | 3.1, 3.3 |
| 4.3 Favores | 4.1, 4.2, 4.3 |
| 4.4 Valor e equivalência | 6 |
| 4.5 Tickets e carteira | 5.1, 5.2, 5.3, 7, 7.1 |
| 4.7 Negociação e troca | 5.4 |
| 4.8 Solicitação e consumo | 8 |
| 4.9 Segurança em atendimento presencial | 8.1, 8.2, 8.3 |
| 4.10 Cancelamentos e ocorrências | 9.1, 9.2, 10 |
| 4.11 Avaliações | 11.1, 11.2, 11.3, 11.4 |
| 4.12 Reputação | 12 |
| 4.13 Bloqueios | 18.2 |
| Compartilhamento de contato (4.8) | 18.1 |

O rascunho `CoreV1` foi encerrado na versão 2.0 do documento de regras e **não é mais fonte de referência**.

**Status:** ✅ Documentos sincronizados.

---

# 9. Decisões pendentes que permanecem pendentes

Estas decisões continuam abertas e **não bloqueiam** o MVP:

| Tema | Status |
|---|---|
| Método de validação documental de identidade | 🟡 Pendente |
| Método de verificação de certificados | 🟡 Pendente |
| Penalidades por cancelamentos recorrentes | 🟡 Pendente |
| Limites baseados em reputação | 🟡 Pendente |
| Compensação no encerramento de conta | 🟡 Pendente |
| Redação do termo de ciência de riscos | 🟡 Pendente — apoio jurídico |
| Escopo do suporte de emergência | 🟡 Pendente — apoio jurídico |
| Natureza jurídica da troca sem dinheiro | 🟡 Pendente — apoio jurídico |
| Modelo operacional de dinheiro | 🟡 Pendente |
| Modelo de monetização | 🟡 Pendente |
| Modelo de garantia e proteção | 🟡 Pendente |
| Processo de análise de disputas | 🟡 Pendente |
| Regras completas de evidências | 🟡 Pendente |

---

# 10. Status do documento

| Item | Status |
|---|---|
| Objetivo do MVP | ✅ Definido |
| Decisão sobre dinheiro | ✅ Definido — fora do MVP |
| Modelo de valor e equivalência | ✅ Definido — descritor de dois eixos |
| Hipóteses a validar | ✅ Definidas |
| Loop mínimo | ✅ Definido |
| Escopo incluído | ✅ Definido |
| Escopo excluído | ✅ Definido |
| Máquina de estados do ticket | ✅ Definida |
| Máquina de estados da solicitação | ✅ Definida |
| Máquina de estados da proposta | ✅ Definida |
| Regras de avaliação | ✅ Definidas |
| Segurança em atendimento presencial | ✅ Mecanismos definidos |
| Termo de ciência de riscos | 🟡 Pendente — apoio jurídico |
| Simplificações e dívidas | ✅ Registradas |
| Critério de pronto | ✅ Definido |
| Requisitos funcionais detalhados | 🟡 Próxima etapa (`04`) |
| Arquitetura conceitual | 🟡 Próxima etapa (`05`) |

---

**Versão:** 1.2  
**Produto:** Ajude.me  
**Documento:** MVP

**Histórico de versões**

| Versão | Alterações |
|---|---|
| 1.0 | Definição inicial do escopo do MVP |
| 1.2 | Limite de tickets passa a ser de circulação simultânea. Novo estado de ticket `EM_ANALISE`, com validade suspensa. Confirmação tácita da realização e análise por chamado em caso de contestação. |
| 1.1 | Seção 4.4 reescrita: modelo de valor passa a ser descritor de dois eixos (tempo × nível de proficiência), substituindo o valor por hora. Nova seção 4.9 — Segurança em atendimento presencial. Nova dívida D7. |
