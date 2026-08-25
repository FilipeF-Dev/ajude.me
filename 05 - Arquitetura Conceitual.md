# Ajude.me — Arquitetura Conceitual

> Documento que traduz os requisitos funcionais em estrutura: quais conceitos existem no sistema, como se relacionam, o que nunca pode ser violado e quais decisões técnicas permanecem em aberto.
>
> Base: `02 - Conceito e Regras de Negócio.md` (v2.2), `03 - MVP.md` (v1.2) e `04 - Requisitos Funcionais.md` (v1.2).

---

# 1. Objetivo e escopo

Este documento define **a forma do sistema**, não sua implementação.

| Está aqui | Não está aqui |
|---|---|
| Contextos e fronteiras do domínio | Escolha de linguagem, framework ou banco |
| Entidades, atributos e relacionamentos | Esquema físico, índices, migrações |
| Invariantes que o sistema deve garantir | Código, bibliotecas, padrões de projeto |
| Pontos de concorrência e integridade | Infraestrutura, provedores, custos |
| Integrações necessárias | Contratos de API |
| Requisitos não funcionais | Metas numéricas de capacidade |

A intenção é que este documento continue válido independentemente da tecnologia escolhida, e que a escolha tecnológica seja avaliada **contra** ele.

---

# 2. Princípios arquiteturais

Os princípios abaixo derivam diretamente das regras de negócio. Não são preferências técnicas.

| # | Princípio | Origem |
|---|---|---|
| P1 | **O ticket é o agregado central.** Sua integridade tem precedência sobre qualquer outra conveniência do sistema. | `02 §5`, `02 §20.3` |
| P2 | **Todo estado é explícito e toda transição é registrada.** Não existe estado inferido. | `04 RF-GER-01` |
| P3 | **O histórico de confiança é imutável e apenas cresce.** Avaliações, ocorrências, cancelamentos e comparecimentos não são alterados nem removidos. | `02 §20.6`, `04 RF-GER-05` |
| P4 | **Transferência de propriedade é atômica.** Não existe estado intermediário observável em uma troca. | `04 RF-NEG-10` |
| P5 | **Condição publicada é imutável.** O que foi mostrado antes da negociação continua valendo depois dela. | `02 §20.2`, `04 RF-FAV-10` |
| P6 | **Prazo é parâmetro, não constante.** Todo prazo de negócio é configurável sem alteração de código. | `04 §19.2` |
| P7 | **Dado de contato é revelado por contexto, não por posse.** A visibilidade acompanha o estágio da relação. | `02 §18.1` |
| P8 | **A plataforma descreve, não precifica.** Não existe unidade monetária, saldo ou acumulador de valor no modelo. | `02 §6.2` |

> **P8 é uma restrição de modelagem, não apenas de produto.** Não deve existir no sistema nenhum campo que represente saldo, crédito ou valor acumulado. A ausência dessa estrutura é a garantia técnica de que a moeda interna não surgirá por conveniência de implementação.

---

# 3. Mapa de contextos

O domínio se divide em nove contextos. A fronteira entre eles é onde a linguagem muda de significado.

```
┌────────────────────────────────────────────────────────────────┐
│                        IDENTIDADE                              │
│         Usuário · Verificação · Endereço · Contato de          │
│                    confiança · Aceite de termo                 │
└───────────────┬────────────────────────────────┬───────────────┘
                │                                │
    ┌───────────▼──────────┐        ┌────────────▼───────────┐
    │      CATÁLOGO        │        │      MODERAÇÃO         │
    │  Categoria ·         │        │  Ocorrência · Chamado  │
    │  Proficiência ·      │        │  Denúncia · Bloqueio   │
    │  Nível declarado     │        │  Suspensão             │
    └───────────┬──────────┘        └────────────▲───────────┘
                │                                │
    ┌───────────▼──────────┐                     │
    │       OFERTA         │                     │
    │  Favor · Janela de   │                     │
    │  disponibilidade     │                     │
    └───────────┬──────────┘                     │
                │ emite                          │
    ┌───────────▼──────────────────────────┐     │
    │              TICKET                  │     │
    │   Ticket · Carteira · Transferência  │     │
    └──────┬─────────────────────┬─────────┘     │
           │                     │               │
┌──────────▼─────────┐  ┌────────▼────────────┐  │
│    NEGOCIAÇÃO      │  │      CONSUMO        │──┘
│ Proposta · Item ·  │  │ Solicitação ·       │
│ Revisão · Mensagem │  │ Comparecimento ·    │
└────────────────────┘  │ Confirmação         │
                        └────────┬────────────┘
                                 │
                    ┌────────────▼────────────┐
                    │       REPUTAÇÃO         │
                    │  Avaliação · Critério   │
                    └─────────────────────────┘

        ┌─────────────────────────────────────────┐
        │   TRANSVERSAIS: Notificação · Auditoria │
        └─────────────────────────────────────────┘
```

## 3.1 Responsabilidade de cada contexto

| Contexto | Responsabilidade | Não é responsável por |
|---|---|---|
| **Identidade** | Quem é a pessoa, o que foi verificado, onde ela está, quem é seu contato de confiança | Reputação, habilidades |
| **Catálogo** | Taxonomia de proficiências, nível declarado por cada usuário e **reconhecimento da necessidade** descrita em linguagem natural | Julgar se a declaração é verdadeira |
| **Oferta** | O que é oferecido e sob quais condições | Quem possui o direito |
| **Ticket** | Quem possui o direito, em que estado, e toda a cadeia de propriedade | Como o direito foi obtido |
| **Negociação** | Como o direito muda de mãos | O que acontece depois da troca |
| **Consumo** | O agendamento, o encontro e a confirmação de que houve prestação | Julgar a qualidade |
| **Reputação** | O que as partes relataram sobre a experiência | Alterar proficiência |
| **Moderação** | Conflitos, decisões da Operação e restrições de convívio | Executar efeitos sobre tickets diretamente |
| **Notificação / Auditoria** | Comunicar eventos e preservar rastro | Regra de negócio |

> **Fronteira crítica:** Moderação **decide**; Ticket e Consumo **executam**. A Operação nunca altera um ticket diretamente — ela registra uma decisão, e o contexto Ticket aplica o efeito correspondente (`04 RF-ADM-14`). Isso mantém a máquina de estados do ticket como única autoridade sobre suas transições.

---

# 4. Modelo conceitual de dados

Os atributos listados são os essenciais ao domínio. Atributos técnicos de infraestrutura foram omitidos deliberadamente.

## 4.1 Identidade

| Entidade | Atributos essenciais | Observações |
|---|---|---|
| **Usuario** | nome, email, telefone, cpf, senha, estado_conta, criado_em | `estado_conta`: Ativa, Encerrando, Desativada, Suspensa |
| **VerificacaoContato** | usuario, tipo (email/telefone), verificado_em | O selo "Contato verificado" é derivado, não armazenado |
| **Endereco** | usuario, logradouro, numero, complemento, bairro, municipio, uf, cep, coordenada | Coordenada precisa armazenada; exposição sempre aproximada |
| **ContatoConfianca** | usuario, nome, meio_contato | **Dados de terceiro não cadastrado** — ver seção 11.3 |
| **TermoVersao** | identificador, texto, vigente_de, vigente_ate | Termo versionado, nunca editado no lugar |
| **AceiteTermo** | usuario, termo_versao, contexto, aceito_em | Contexto identifica a solicitação que originou o aceite |

**Regra:** `Usuario.cpf` é único, imutável após o cadastro e a unicidade abrange contas desativadas (`04 RF-CAD-03`, `RF-CAD-10`).

## 4.2 Catálogo

| Entidade | Atributos essenciais | Observações |
|---|---|---|
| **Categoria** | nome, ativa | Mantida pela Operação |
| **Subcategoria** | categoria, nome, ativa | |
| **Proficiencia** | subcategoria, nome, ativa, restrita | `restrita` implementa a curadoria (`04 RF-FAV-13`) |
| **ProficienciaUsuario** | usuario, proficiencia, nivel, declarada_em | `nivel`: Básico, Intermediário, Avançado, Especialista |
| **TermoPopular** | proficiencia, termo, criado_por, criado_em | Vocabulário de reconhecimento de necessidade (`02 §3.4`) |
| **BuscaNecessidade** | usuario, texto, proficiencias_reconhecidas, regiao, houve_reconhecimento, houve_resultado, momento | Ver seção 11.5 quanto ao tratamento |
| **AvisoNecessidade** | usuario, proficiencia, regiao, criado_em, atendido_em | Fila de demanda (`02 §3.5`) |

**Regra:** a restrição de categoria é atributo do catálogo, não do favor. Restringir uma categoria impede novas ofertas sem invalidar as existentes.

### O catálogo é a camada de correspondência

O Ajude.me não possui entidade de pedido. A demanda entra no sistema como **texto livre** e sai como **proficiência** — e essa tradução é responsabilidade do Catálogo, não da Descoberta.

```
"trocar a resistência do chuveiro"
              │
              ▼   TermoPopular: chuveiro → Elétrica residencial
      Proficiencia reconhecida
              │
              ▼   ProficienciaUsuario → Favor → Ticket disponível
        Resultados por proximidade
```

Consequências de modelagem:

- **`AvisoNecessidade` referencia a proficiência, nunca o texto digitado** (`04 RF-BUS-29`). O texto é o sintoma de uma pessoa; a fila é sobre a competência que falta.
- **`BuscaNecessidade` distingue dois fracassos diferentes.** `houve_reconhecimento = falso` é falha de vocabulário, corrigida pela Operação com um termo novo. `houve_resultado = falso` com reconhecimento bem-sucedido é ausência real de oferta, que só se corrige recrutando ajudantes. Guardar um único campo de "sem resultado" tornaria os dois casos indistinguíveis — e eles pedem ações opostas (`04 RF-ADM-17`).

## 4.3 Oferta

| Entidade | Atributos essenciais | Observações |
|---|---|---|
| **Favor** | ajudante, proficiencia_usuario, titulo, descricao, tempo_estimado, modalidade, area_atendimento, antecedencia_minima, validade_ticket, maximo_circulacao, trocavel, condicoes, estado, congelado_em | `estado`: Rascunho, Ativo, Desativado |
| **JanelaDisponibilidade** | favor, dia_semana, hora_inicio, hora_fim | Múltiplas por favor |

**O descritor não é armazenado.** Ele é derivado de `Favor.tempo_estimado` e `ProficienciaUsuario.nivel` no momento da exibição (`04 RF-FAV-04`, `RF-FAV-05`).

> **Consequência a decidir:** se o ajudante alterar seu nível declarado (`04 RF-PRO-05`), o descritor de favores já publicados muda retroativamente. Isso contraria P5 — condição publicada é imutável. Ver seção 14.1.

## 4.4 Ticket

| Entidade | Atributos essenciais | Observações |
|---|---|---|
| **Ticket** | favor, proprietario, estado, emitido_em, expira_em, validade_suspensa_em | Identificador único e imutável |
| **TransferenciaTicket** | ticket, de_usuario, para_usuario, origem (proposta), ocorrido_em | Cadeia completa de propriedade; apenas inserção |

**A carteira não é entidade.** É a projeção dos tickets de um usuário agrupados por estado (`04 RF-TIC-05`). Modelá-la como entidade criaria uma segunda fonte de verdade sobre propriedade.

**Vagas de circulação** também são derivadas: contagem de tickets do favor em `DISPONIVEL`, `EM_NEGOCIACAO`, `RESERVADO` ou `EM_ANALISE` (`04 RF-FAV-16`). Não deve existir contador materializado — ver seção 7.2.

## 4.5 Negociação

| Entidade | Atributos essenciais | Observações |
|---|---|---|
| **Proposta** | remetente, destinatario, estado, versao_atual, expira_em, criada_em | |
| **VersaoProposta** | proposta, numero, criada_por, criada_em | Cada revisão gera nova versão |
| **ItemProposta** | versao_proposta, ticket, lado | `lado`: Solicitado ou Contrapartida |
| **MensagemNegociacao** | proposta, autor, texto, enviada_em | |

**O versionamento da oferta é estrutural**, não histórico opcional. `04 RF-NEG-09` exige que toda revisão invalide aceites anteriores — o que só é verificável se o aceite referenciar a versão exata que foi aceita.

> **O aceite registra a versão aceita.** Se a versão corrente da proposta diferir da versão aceita, o aceite é inválido.

## 4.6 Consumo

| Entidade | Atributos essenciais | Observações |
|---|---|---|
| **Solicitacao** | ticket, solicitante, ajudante, inicio_previsto, fim_previsto, estado, janela_contestacao_ate | `fim_previsto` derivado do tempo estimado |
| **RegistroComparecimento** | solicitacao, parte, tipo (check-in/check-out), momento | Apenas inserção |
| **ManifestacaoRealizacao** | solicitacao, parte, tipo (confirma/contesta), momento | Apenas inserção |

**Regra:** `Solicitacao` guarda `ajudante` explicitamente, mesmo sendo derivável de `ticket → favor → ajudante`. A prestação é obrigação de quem emitiu o ticket, e essa vinculação não pode se perder caso o favor seja desativado.

## 4.7 Moderação

| Entidade | Atributos essenciais | Observações |
|---|---|---|
| **Ocorrencia** | tipo, autor, contra_usuario, solicitacao, descricao, origem, registrada_em | `origem`: usuário ou sistema |
| **Chamado** | solicitacao, estado, decisao, fundamento, decidido_por, decidido_em | `decisao`: Realizado, Não realizado, Inconclusivo |
| **ManifestacaoChamado** | chamado, parte, texto, enviada_em | |
| **Denuncia** | autor, alvo_usuario, alvo_favor, motivo, estado | |
| **Bloqueio** | usuario, usuario_bloqueado, criado_em | Direcional; o efeito de ocultação é mútuo |
| **Suspensao** | alvo_usuario, alvo_favor, motivo, aplicada_por, vigente_de, vigente_ate | |

## 4.8 Reputação

| Entidade | Atributos essenciais | Observações |
|---|---|---|
| **Avaliacao** | solicitacao, avaliador, avaliado, papel_avaliado, comentario, criada_em | Imutável após criação |
| **NotaCriterio** | avaliacao, criterio, nota | `nota` de 0 a 7 |
| **MotivoQualidade** | avaliacao, motivo | Obrigatório no critério Qualidade |

**A reputação é sempre calculada, nunca armazenada como estado.** Médias materializadas por desempenho são cache, e devem ser tratadas como tal — reconstruíveis a partir das avaliações a qualquer momento.

`Avaliacao.papel_avaliado` permite que a mesma pessoa acumule reputações distintas como Ajudante e como Solicitante (`04 RF-REP-01`, `RF-REP-02`), e o vínculo com `solicitacao → ticket → favor → proficiencia` sustenta a reputação segmentada por proficiência (`04 RF-REP-04`).

## 4.9 Transversais

| Entidade | Atributos essenciais |
|---|---|
| **EventoDominio** | tipo, agregado, agregado_id, ator, dados, ocorrido_em |
| **RegistroAuditoria** | ator, acao, alvo, motivo, ocorrido_em |
| **Notificacao** | destinatario, tipo, referencia, enviada_em, lida_em |
| **ParametroNegocio** | chave, valor, vigente_de, alterado_por |

---

# 5. Invariantes de domínio

Condições que **nunca** podem ser falsas. Cada uma deve ser garantida pelo ponto mais próximo do dado — preferencialmente pelo próprio armazenamento, não apenas pela camada de aplicação.

## 5.1 Ticket

| # | Invariante | Origem |
|---|---|---|
| I1 | Um ticket possui exatamente um proprietário em qualquer instante. | `02 §5` |
| I2 | Um ticket em `EM_NEGOCIACAO` está vinculado a exatamente uma proposta ativa. | `04 RF-NEG-04` |
| I3 | Um ticket em `RESERVADO` está vinculado a exatamente uma solicitação não encerrada. | `04 RF-SOL-06` |
| I4 | Tickets em circulação de um favor nunca excedem `maximo_circulacao`. | `04 RF-FAV-16` |
| I5 | Um ticket `EXPIRADO`, `CONSUMIDO`, `ENCERRADO` ou `DESCARTADO` nunca retorna a estado ativo. | `04 §7.2` |
| I6 | Um ticket em `RESERVADO` com realização agendada dentro da validade não expira. | `04 RF-TIC-09` |
| I7 | Um ticket em `EM_ANALISE` não expira. | `04 RF-TIC-15` |
| I8 | Toda mudança de proprietário gera exatamente um registro de transferência. | `04 RF-TIC-04` |

## 5.2 Oferta

| # | Invariante | Origem |
|---|---|---|
| I9 | Todo favor referencia uma proficiência declarada pelo próprio ajudante. | `02 §4.1` |
| I10 | Uma proficiência com favor ativo ou ticket em circulação não pode ser removida. | `04 RF-PRO-07` |
| I11 | Um favor com ao menos um ticket emitido não tem suas condições alteradas. | `04 RF-FAV-10`, `RF-FAV-20` |
| I12 | Favor desativado não emite tickets. | `04 RF-FAV-19` |

## 5.3 Negociação

| # | Invariante | Origem |
|---|---|---|
| I13 | Um ticket não trocável nunca aparece como contrapartida. | `04 RF-NEG-03` |
| I14 | Um ticket não participa de duas propostas ativas simultaneamente. | `04 RF-NEG-04` |
| I15 | O aceite referencia a versão da proposta vigente no instante do aceite. | `04 RF-NEG-09` |
| I16 | O aceite transfere todos os itens ou nenhum. | `04 RF-NEG-10` |
| I17 | Não existe proposta entre usuários com bloqueio em qualquer direção. | `04 RF-NEG-14` |

## 5.4 Consumo

| # | Invariante | Origem |
|---|---|---|
| I18 | Uma solicitação agendada respeita todas as condições publicadas do favor. | `04 RF-SOL-02` |
| I19 | Um ajudante não possui duas solicitações agendadas com intervalos sobrepostos. | `04 RF-SOL-23` |
| I20 | Avaliação só existe para solicitação em `REALIZADA` ou `ENCERRADA`. | `04 RF-SOL-20` |
| I21 | Uma solicitação encerra somente com as duas avaliações registradas. | `04 RF-AVA-10` |
| I22 | Cada parte registra no máximo uma manifestação de realização por solicitação. | `02 §8.4` |

## 5.5 Reputação e histórico

| # | Invariante | Origem |
|---|---|---|
| I23 | Uma avaliação nunca é alterada após a criação. | `04 RF-AVA-07` |
| I24 | Existe no máximo uma avaliação por par (solicitação, avaliador). | `02 §11` |
| I25 | Toda nota está no intervalo fechado de 0 a 7. | `02 §11.3` |
| I26 | Registros de histórico de confiança não são removidos. | `04 RF-GER-05` |
| I27 | Nenhuma avaliação altera `ProficienciaUsuario.nivel`. | `02 §3.3` |

> **I27 é uma invariante de ausência.** Não há mecanismo a construir — há um mecanismo a nunca construir. Deve constar da revisão de qualquer alteração que toque reputação.

---

# 6. Máquinas de estado

O sistema possui quatro máquinas de estado. Todas obedecem a P2 e a `04 RF-GER-02`: transições registradas e idempotentes.

| Máquina | Autoridade | Estados | Definição |
|---|---|---|---|
| **Conta** | Identidade | Ativa, Encerrando, Desativada, Suspensa | `04 §4.1` |
| **Ticket** | Ticket | Disponível, Em negociação, Reservado, Em análise, Consumido, Encerrado, Expirado, Descartado | `04 §7.1` e `§7.2` |
| **Proposta** | Negociação | Rascunho, Enviada, Em negociação, Aceita, Recusada, Retirada, Expirada | `04 §9.1` |
| **Solicitação** | Consumo | Solicitada, Agendada, Recusada, Aguardando confirmação, Em análise, Realizada, Não realizada, Inconclusiva, Cancelada (ajudante/solicitante), Encerrada | `04 §10.1` |

## 6.1 Acoplamento entre máquinas

As máquinas não são independentes. O ticket reage a eventos das outras duas:

```
Proposta.ENVIADA          →  Ticket.EM_NEGOCIACAO
Proposta.ACEITA           →  Ticket transferido, volta a DISPONIVEL
Proposta.RECUSADA/
RETIRADA/EXPIRADA         →  Ticket.DISPONIVEL

Solicitacao.AGENDADA      →  Ticket.RESERVADO
Solicitacao.REALIZADA     →  Ticket.CONSUMIDO
Solicitacao.EM_ANALISE    →  Ticket.EM_ANALISE
Solicitacao.NAO_REALIZADA/
INCONCLUSIVA              →  Ticket.DISPONIVEL
Solicitacao.CANCELADA     →  Ticket.DISPONIVEL

Avaliações completas      →  Ticket.ENCERRADO
```

> **O ticket nunca é alterado diretamente por outro contexto.** Os contextos publicam eventos; o contexto Ticket decide se a transição é válida e a executa. Uma transição inválida recebida por evento é erro, não exceção silenciosa.

---

# 7. Concorrência e integridade

Os pontos abaixo são onde o sistema falha silenciosamente se a modelagem não os tratar. Todos decorrem de invariantes da seção 5.

## 7.1 Aceite simultâneo de propostas

**Cenário:** dois usuários enviam propostas envolvendo o mesmo ticket de um terceiro. I14 impede que ambas fiquem ativas, mas o problema real é o inverso: o proprietário do ticket recebe duas propostas *distintas* que oferecem o mesmo ticket dele como item solicitado, e aceita ambas em sequência rápida.

**Exigência:** a validação de elegibilidade de cada ticket ocorre **no instante do aceite**, sob bloqueio, e não no envio da proposta (`04 §9.2`). O segundo aceite deve falhar de forma explícita, informando qual ticket ficou indisponível.

## 7.2 Emissão concorrente contra o limite de circulação

**Cenário:** o ajudante dispara duas emissões simultâneas com uma vaga disponível. Se a contagem for lida e depois gravada sem proteção, I4 é violada.

**Exigência:** a contagem de vagas é apurada sob bloqueio do favor no instante da emissão. Um contador materializado incrementado por aplicação **não** é aceitável — a contagem deve derivar dos tickets, que são a fonte de verdade.

## 7.3 Agendamento sobreposto

**Cenário:** dois portadores de tickets do mesmo ajudante solicitam o mesmo horário, e o ajudante confirma ambos. I19 é violada.

**Exigência:** a verificação de sobreposição ocorre na confirmação, sob bloqueio da agenda do ajudante, considerando `inicio_previsto` e `fim_previsto`.

## 7.4 Expiração e estados protegidos

**Cenário:** o processo de expiração roda enquanto uma solicitação está sendo agendada. O ticket expira entre a validação e o agendamento.

**Exigência:** a expiração é um processo temporal que precisa respeitar I6 e I7. Ela não pode atuar sobre tickets em `RESERVADO` com agendamento válido nem em `EM_ANALISE`, e deve competir corretamente com as transições em curso.

> **Recomendação:** tratar a expiração como avaliação de estado, não como evento agendado por ticket. Um ticket está expirado quando sua validade passou **e** ele não está protegido — condição verificável a qualquer momento, sem depender da execução pontual de um processo.

## 7.5 Confirmação tácita

**Cenário:** a janela de contestação encerra no mesmo instante em que uma parte contesta.

**Exigência:** a contestação registrada dentro da janela prevalece. A confirmação tácita é a **ausência** de contestação ao final da janela, apurada após o limite — nunca antecipada.

## 7.6 Transferência atômica

I16 exige que o aceite seja indivisível. Além da atomicidade da escrita, a operação envolve: mudança de proprietário de N tickets, N registros de transferência, mudança de estado da proposta e liberação de bloqueios.

**Exigência:** todo esse conjunto pertence a uma única unidade transacional. Efeitos externos — notificações, por exemplo — ocorrem **após** a confirmação, nunca dentro dela.

---

# 8. Eventos de domínio

Os eventos abaixo alimentam notificações, reputação e auditoria. Modelá-los explicitamente evita que essas três preocupações se espalhem pela regra de negócio.

| Contexto | Eventos |
|---|---|
| Identidade | Conta criada, contato verificado, conta em encerramento, conta encerrada, termo aceito |
| Catálogo | Proficiência declarada, nível alterado, proficiência removida; necessidade buscada, necessidade sem reconhecimento, necessidade sem oferta, aviso solicitado, aviso atendido |
| Oferta | Favor publicado, favor congelado, favor desativado |
| Ticket | Ticket emitido, transferido, reservado, consumido, encerrado, expirado |
| Negociação | Proposta enviada, revisada, aceita, recusada, retirada, expirada; mensagem enviada |
| Consumo | Solicitação enviada, agendada, recusada, cancelada; check-in, check-out; realização confirmada, contestada, tacitamente confirmada |
| Moderação | Ocorrência registrada, chamado aberto, manifestação enviada, chamado decidido, denúncia registrada, suspensão aplicada |
| Reputação | Avaliação registrada, ciclo encerrado |

**Todo evento carrega:** tipo, agregado afetado, ator, momento e dados mínimos para reconstrução.

> A trilha de auditoria (`04 RF-ADM-10`, `RF-GER-01`) é consumidora desses eventos, não uma estrutura paralela.

---

# 9. Parâmetros de negócio

P6 exige que todo prazo seja configurável. Os parâmetros abaixo correspondem às pendências de `04 §19.2` e **não devem existir como constantes no código**.

| Parâmetro | Aplicação |
|---|---|
| Janela de contestação | Consumo |
| Prazo de manifestação em chamado | Moderação |
| Prazo para avaliação antes da restrição | Reputação |
| Validade padrão da proposta | Negociação |
| Antecedência mínima para cancelamento pelo solicitante | Consumo |
| Prazo máximo da conta em Encerrando | Identidade |
| Antecedência de notificação de vencimento de ticket | Notificação |
| Intervalo tolerado para check-out ausente | Segurança |

Cada parâmetro é versionado com vigência (`ParametroNegocio`), de modo que uma alteração não reescreva o passado: uma solicitação aberta sob janela de 72h permanece regida por 72h ainda que o parâmetro mude.

> Essa versão com vigência é a diferença entre "mudar uma configuração" e "alterar retroativamente as condições de relações em curso" — o que violaria P5.

---

# 10. Integrações externas

| Integração | Finalidade | Criticidade | Requisito |
|---|---|---|---|
| **Envio de SMS** | Verificação de telefone | Alta — bloqueia o cadastro | `RF-CAD-05` |
| **Envio de e-mail** | Verificação, recuperação de senha, notificações | Alta | `RF-CAD-04`, `RF-CAD-08` |
| **Notificação push** | Eventos do loop | Média | `RF-NOT-*` |
| **Geocodificação** | Converter endereço em coordenada | Média | `RF-BUS-07` |
| **Cálculo de proximidade** | Ordenação do feed | Média | `RF-BUS-01` |
| **Armazenamento de mídia** | Foto de perfil | Baixa | `RF-CAD-09` |
| **Canal do contato de confiança** | Comunicar agendamento e ausência de check-out | **Alta — função de segurança** | `RF-SEG-02`, `RF-SEG-10` |

## 10.1 Considerações

**O canal do contato de confiança não pode falhar silenciosamente.** É o único mecanismo do MVP que atua fora da plataforma em situação de risco. Falha de entrega precisa ser detectada e tratada como incidente, não registrada em log.

**Verificação de identidade documental** (`RF-CAD-18`) exigirá integração adicional quando sair do escopo futuro. A modelagem já deve acomodar múltiplos níveis de verificação sem alteração estrutural — daí `VerificacaoContato` ser entidade separada, e não campos booleanos no usuário.

**Não há integração financeira**, coerentemente com P8 e com a decisão fundadora do MVP.

---

# 11. Privacidade e proteção de dados

O Ajude.me trata dados pessoais sensíveis em volume: CPF, endereço residencial completo, telefone, geolocalização e dados de terceiros não cadastrados. A LGPD é restrição de arquitetura, não item de conformidade posterior.

## 11.1 Classificação

| Dado | Sensibilidade | Exposição máxima |
|---|---|---|
| CPF | Alta | Nunca exposto a outro usuário |
| Senha | Alta | Nunca recuperável, apenas redefinível |
| Endereço completo | Alta | Contraparte de solicitação agendada |
| Coordenada precisa | Alta | Nunca exposta; usada apenas em cálculo |
| Telefone | Média | Contraparte de solicitação agendada |
| E-mail | Média | Nunca exposto a outro usuário |
| Nome, foto, região aproximada | Baixa | Pública |
| Avaliações e reputação | Baixa | Pública por decisão de produto |

## 11.2 Revelação por contexto

P7 e `RF-SOL-07` / `RF-SOL-21` exigem que a visibilidade dependa do estágio da relação, e que o acesso seja **revogado** no encerramento.

> **Consequência de modelagem:** a visibilidade não pode ser resolvida apenas na camada de apresentação. O acesso ao dado precisa ser autorizado no ponto de leitura, com base no estado da relação naquele instante.

Uma vez revelado, o dado não pode ser "desrevelado" da memória de quem o viu — o que reforça que a revelação deve ser tardia, mínima e registrada.

## 11.3 O contato de confiança

`ContatoConfianca` armazena nome e meio de contato de uma pessoa **que não é usuária da plataforma e nunca consentiu com esse tratamento**. Além disso, `RF-SEG-02` transmite a esse terceiro o endereço e a identificação de outro usuário.

Há duas questões distintas:

1. **Base legal para tratar os dados do contato de confiança**, que não aceitou termos.
2. **Compartilhamento de dados de um usuário com um terceiro externo** — o endereço do atendimento e a identificação da contraparte.

A finalidade é legítima e proporcional: proteção da vida e da integridade física. Mas a base legal, o texto informativo à contraparte e a retenção desses dados precisam de definição jurídica.

**Status:** 🟡 Pendente — incluir no escopo do apoio jurídico já previsto em `02 §8.2`.

> Esta é a questão de privacidade mais delicada do produto. Ela não existia enquanto a segurança presencial não fazia parte do escopo, e surgiu junto com ela.

## 11.4 Tensão entre imutabilidade e direito à eliminação

P3 e I26 determinam que o histórico de confiança não seja removido. A LGPD assegura ao titular o direito de eliminação de dados pessoais.

As duas exigências colidem diretamente. Caminhos possíveis:

| Caminho | Efeito |
|---|---|
| **Anonimização em vez de exclusão** | Preserva a integridade do histórico e desvincula a identidade. Avaliações permanecem, atribuídas a usuário removido. |
| **Retenção por obrigação legítima** | Sustenta a manutenção enquanto houver relações e prazos aplicáveis; exige fundamentação. |
| **Exclusão com quebra de histórico** | Conforme à eliminação, mas viola `02 §20.6` e inviabiliza a apuração de conflitos passados. |

**Recomendação:** anonimização, preservando os registros e removendo a vinculação identificável. A decisão exige validação jurídica.

**Status:** 🟡 Pendente de definição jurídica e de produto.

---

## 11.5 Necessidades registradas

O que uma pessoa procura revela o que lhe falta. Uma busca por necessidade pode expor condição de saúde, situação financeira, composição familiar ou vulnerabilidade — inclusive de terceiros, e não apenas de quem digita.

> *"alguém para dar banho na minha mãe acamada"* não é uma consulta. É um prontuário social.

| Dado | Sensibilidade | Exposição máxima |
|---|---|---|
| Texto original da busca | **Alta** | Nunca exposto a outro usuário |
| Proficiência reconhecida | Média | Base da fila de aviso |
| Região da busca | Média | Somente agregada |

Regras de arquitetura decorrentes de `02 §18.3`:

- A necessidade **não** compõe perfil, feed, recomendação ou qualquer superfície visível a outro usuário.
- Nenhum usuário é notificado de que outra pessoa buscou determinado favor — o que exclui, por decisão, um recurso aparentemente útil de "alguém perto precisa disso".
- A análise de cobertura consome **agregados por proficiência e região**, não registros individuais.
- `AvisoNecessidade` armazena proficiência e região, jamais o texto.

> **Consequência a decidir:** o texto original só é necessário para corrigir vocabulário (`04 RF-ADM-17`). Cumprida essa função, ele deixa de ter uso e passa a ser apenas passivo. A recomendação é reter o texto por prazo curto e preservar indefinidamente apenas o par (proficiência reconhecida, região) — mas isso precisa de decisão.

**Status:** 🟡 Retenção do texto original das buscas pendente.

---

# 12. Auditoria e retenção

| Categoria | Retenção | Fundamento |
|---|---|---|
| Transições de estado de ticket | Permanente | I8, apuração de conflitos |
| Cadeia de propriedade | Permanente | `RF-TIC-04` |
| Avaliações | Permanente | P3 |
| Ocorrências e chamados | Permanente | `02 §20.6` |
| Registros de comparecimento | Permanente | Evidência em disputa |
| Aceites de termo | Permanente | Necessidade probatória |
| Mensagens de negociação | A definir | Conteúdo pessoal sem função probatória permanente |
| Texto original das buscas | A definir | Dado sensível com utilidade curta — ver 11.5 |
| Necessidade agregada por proficiência e região | Permanente | Mapa de cobertura da rede (`02 §3.5`) |
| Termos populares | Permanente | Vocabulário de reconhecimento |
| Notificações | Curta | Sem valor histórico |
| Ações da Operação | Permanente | `RF-ADM-10` |

> **"Permanente" convive com anonimização.** Preservar o registro não exige preservar a identidade — ver seção 11.4.

**Status:** 🟡 Retenção de mensagens de negociação pendente.

---

# 13. Requisitos não funcionais

Os requisitos abaixo estão qualificados. Metas numéricas dependem de escala esperada e serão definidas com o roadmap.

## 13.1 Segurança

| # | Requisito |
|---|---|
| NF-SEG-01 | Senhas armazenadas com função de derivação apropriada, nunca reversível. |
| NF-SEG-02 | Todo tráfego cifrado em trânsito. |
| NF-SEG-03 | CPF e dados de contato cifrados em repouso. |
| NF-SEG-04 | Autorização verificada no acesso ao dado, não apenas na navegação. |
| NF-SEG-05 | Proteção contra enumeração de usuários por CPF, e-mail ou telefone. |
| NF-SEG-06 | Limitação de tentativas em autenticação, verificação e recuperação. |
| NF-SEG-07 | Nenhum dado pessoal em URL, parâmetro de consulta ou registro de log (`RF-GER-04`). |

## 13.2 Disponibilidade e resiliência

| # | Requisito |
|---|---|
| NF-DIS-01 | Indisponibilidade de integração não crítica não impede o loop principal. |
| NF-DIS-02 | Falha no envio de SMS ou e-mail é reprocessável, sem duplicar efeitos. |
| NF-DIS-03 | Falha do canal do contato de confiança é detectada e escalada. |
| NF-DIS-04 | Operações de escrita do domínio são idempotentes por identificador de comando. |

## 13.3 Desempenho

| # | Requisito |
|---|---|
| NF-DES-01 | Busca e feed respondem em tempo adequado a conexão móvel limitada. |
| NF-DES-02 | Cálculo de reputação não é executado de forma síncrona na exibição de listas. |
| NF-DES-03 | Consulta de proximidade suportada por estrutura geoespacial adequada. |

## 13.4 Acessibilidade e alcance

| # | Requisito |
|---|---|
| NF-ACE-01 | A aplicação é utilizável em aparelhos de baixo desempenho e conexão instável. |
| NF-ACE-02 | O consumo de dados é minimizado, especialmente em imagens. |
| NF-ACE-03 | A interface atende requisitos de acessibilidade em contraste, toque e leitura de tela. |

> **NF-ACE-01 e NF-ACE-02 não são refinamento.** O público-alvo declarado em `01 §4` é de baixa e média renda. Uma aplicação pesada exclui exatamente quem o produto pretende atender — o requisito de alcance é requisito de propósito.

## 13.5 Observabilidade

| # | Requisito |
|---|---|
| NF-OBS-01 | As métricas de `03 §7` são apuráveis a partir dos eventos de domínio. |
| NF-OBS-02 | Transições inválidas recusadas são registradas para diagnóstico. |
| NF-OBS-03 | Falhas de integração crítica geram alerta ativo. |

---

# 14. Tensões arquiteturais em aberto

Contradições reais entre requisitos já aprovados. Cada uma exige decisão.

## 14.1 Alteração de nível e descritor retroativo

O descritor é derivado do nível declarado (`RF-FAV-05`), e o nível é alterável (`RF-PRO-05`). Um ajudante pode publicar um favor como Básico e depois declarar-se Especialista, alterando o descritor de favores cujos tickets já circulam — o que contraria P5.

| Opção | Consequência |
|---|---|
| Congelar o nível no favor na primeira emissão | Preserva P5; cria divergência entre perfil e favor antigo |
| Manter derivação viva | Perfil sempre coerente; condição publicada muda retroativamente |
| Impedir alteração de nível com favores ativos | Preserva ambos; restringe demais o usuário |

**Recomendação:** congelar o nível no favor junto com as demais condições, exibindo-o como condição histórica daquele favor.

**Status:** 🟡 Pendente.

## 14.2 Imutabilidade do histórico e direito à eliminação

Detalhada em 11.4. **Status:** 🟡 Pendente.

## 14.3 Dados do contato de confiança

Detalhada em 11.3. **Status:** 🟡 Pendente de definição jurídica.

## 14.4 Avaliação imutável e disputa por qualidade

`RF-AVA-07` torna a avaliação imutável; a lacuna L11 (`04 §19.3`) reconhece que não há mecanismo para divergência sobre qualidade. Hoje, uma avaliação injusta é permanente e sem via de revisão.

Isso é sustentável no MVP, com base restrita. Não é sustentável em escala.

**Status:** 🟡 Pendente — relacionado a L11.

---

# 15. Decisões técnicas pendentes

Não decididas neste documento por dependerem de contexto de equipe, prazo e orçamento.

| # | Decisão | Consideração |
|---|---|---|
| A1 | **Plataforma cliente** | O público-alvo é majoritariamente móvel. Uma aplicação web responsiva reduz atrito de instalação e custo de distribuição; aplicação nativa favorece notificação e recursos de segurança presencial. |
| A2 | **Natureza do armazenamento** | As invariantes da seção 5 são majoritariamente relacionais e transacionais. Um armazenamento com garantias transacionais fortes atende diretamente I4, I14, I16 e I19. |
| A3 | **Estilo de integração entre contextos** | Os contextos podem coexistir em uma aplicação única com fronteiras internas. Separação física não é exigida pelo domínio nesta fase. |
| A4 | **Tratamento dos eventos de domínio** | Síncrono no início, com fronteira preparada para processamento assíncrono. |
| A5 | **Provedores de SMS, push e geocodificação** | Considerar custo por mensagem, dado o volume de verificação. |
| A6 | **Estratégia de expiração** | Avaliação por estado, conforme recomendação de 7.4, ou processo temporal. |
| A7 | **Mecanismo de reconhecimento de necessidade** | Termos populares curados (auditável, corrigível de imediato, sem custo de infraestrutura) ou reconhecimento semântico (maior alcance, exige infraestrutura e não é corrigível pontualmente). Os dois podem coexistir: termos primeiro, semântica como complemento quando nada for reconhecido. |

> **Observação sobre A3:** a divisão em contextos é conceitual e serve para organizar responsabilidade e linguagem. Traduzi-la prematuramente em separação física acrescentaria complexidade de coordenação — justamente onde o domínio exige atomicidade (I16).

---

# 16. Fora do escopo deste documento

| Tema | Destino |
|---|---|
| Esquema físico e migrações | Documentação técnica de implementação |
| Contratos de API | Documentação técnica de implementação |
| Telas, fluxos e design | Documento de design |
| Sequenciamento de entrega | `06 - Roadmap do Produto.md` |
| Metas numéricas de capacidade | `06 - Roadmap do Produto.md` |

---

# 17. Status do documento

| Item | Status |
|---|---|
| Princípios arquiteturais | ✅ Definidos |
| Mapa de contextos | ✅ Definido |
| Modelo conceitual de dados | ✅ Definido |
| Invariantes de domínio | ✅ 27 invariantes mapeadas |
| Máquinas de estado e acoplamento | ✅ Definidos |
| Pontos de concorrência | ✅ Mapeados |
| Eventos de domínio | ✅ Definidos |
| Parâmetros de negócio | ✅ Definidos |
| Integrações externas | ✅ Mapeadas |
| Classificação de dados pessoais | ✅ Definida |
| Reconhecimento de necessidade | ✅ Modelado no Catálogo |
| Privacidade das necessidades registradas | ✅ Regras definidas |
| Retenção do texto das buscas | 🟡 Pendente |
| Requisitos não funcionais | ✅ Qualificados |
| Metas numéricas | 🟡 Dependem do roadmap |
| Base legal do contato de confiança | 🟡 Pendente — apoio jurídico |
| Imutabilidade × direito à eliminação | 🟡 Pendente |
| Congelamento do nível no favor | 🟡 Pendente |
| Retenção de mensagens de negociação | 🟡 Pendente |
| Decisões técnicas | 🟡 6 decisões em aberto — seção 15 |
| Roadmap | 🟡 Próxima etapa (`06`) |

---

**Versão:** 1.1
**Produto:** Ajude.me
**Documento:** Arquitetura Conceitual

**Histórico de versões**

| Versão | Alterações |
|---|---|
| 1.0 | Definição inicial da arquitetura conceitual |
| 1.1 | Busca por necessidade modelada no contexto Catálogo, com `TermoPopular`, `BuscaNecessidade` e `AvisoNecessidade`. Nova seção 11.5 — privacidade das necessidades registradas. Novos eventos de domínio e nova decisão técnica A7. |
