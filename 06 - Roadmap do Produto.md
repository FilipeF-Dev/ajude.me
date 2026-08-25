# Ajude.me — Roadmap do Produto

> Documento que define a ordem de construção do Ajude.me e as condições que separam uma etapa da seguinte. Ele não promete datas: define **portões**.
>
> Base: `01` a `05`, nas versões vigentes.

---

# 1. Objetivo e forma deste documento

Este roadmap organiza o produto em fases sequenciais. Cada fase possui:

- **Entregas** — o que passa a existir;
- **Decisões que a antecedem** — o que precisa estar resolvido antes de começar;
- **Portão de saída** — a evidência necessária para avançar.

## 1.1 Por que não há datas

Prazo é função de equipe, orçamento e dedicação — informações que não constam de nenhum documento desta série. Estimar datas sem esses dados produziria ficção com aparência de plano.

As fases são sequenciadas por **dependência e evidência**, não por calendário. Quando a capacidade de execução estiver definida, as datas podem ser derivadas deste documento sem alterá-lo.

## 1.2 Portões

Um portão não é uma cerimônia. É a resposta a uma pergunta:

> **O que precisa ser verdade para que investir na próxima fase não seja aposta?**

Um portão não atingido significa permanecer na fase, ajustar, ou reconhecer que a hipótese estava errada — nunca prosseguir por inércia.

---

# 2. A restrição que organiza tudo

O Ajude.me não é um aplicativo de serviços. É uma **rede de troca**, e redes de troca têm um problema que aplicativos de serviço não têm.

## 2.1 A dupla coincidência de desejos

Em uma transação com dinheiro, basta que uma pessoa queira o serviço. Em uma troca, é preciso que **duas pessoas queiram simultaneamente o que a outra oferece**.

A probabilidade disso acontecer depende de densidade: quantas pessoas, com quais habilidades, a que distância umas das outras.

> Mil usuários espalhados por um país inteiro produzem uma rede vazia.
> Cem usuários no mesmo bairro produzem uma rede que funciona.

## 2.2 Consequência para o roadmap

Isto inverte a intuição usual de lançamento. **A prioridade não é crescer, é concentrar.**

Toda fase inicial deve ser deliberadamente limitada a uma geografia pequena e a uma comunidade com necessidades sobrepostas. Expandir cedo dilui a densidade e produz a pior evidência possível: um produto que parece não funcionar quando na verdade nunca teve massa crítica para funcionar.

Essa restrição converge com a decisão já tomada de operar em base restrita e convidada (`03 §6`), que deixa de ser apenas mitigação de risco de segurança e passa a ser **estratégia de liquidez**.

## 2.3 O papel do ticket transferível

A transferibilidade do ticket (`02 §5.3`) existe justamente para atacar a dupla coincidência: quem recebe um ticket não precisa querer aquele favor — pode usá-lo em trocas futuras.

Isso só se realiza com densidade. Sem rede, o ticket transferível é apenas um vale nominal.

> **A métrica que revela se o conceito funciona é a profundidade de circulação** — quantas vezes um ticket muda de mãos antes de ser consumido. Ver seção 10.3.

---

# 3. Visão geral

| Fase | Nome | Pergunta que responde |
|---|---|---|
| **0** | Fundação | As decisões pendentes estão fechadas? |
| **1** | Construção do núcleo | O loop completo existe e funciona? |
| **2** | Piloto fechado | Pessoas reais trocam favores sem dinheiro? |
| **3** | Expansão controlada | Funciona sem a equipe fundadora presente? |
| **4** | Abertura | A infraestrutura de confiança sustenta escala? |
| **5** | Evolução do modelo | O que o produto se torna depois de validado? |

```
 Fase 0          Fase 1           Fase 2            Fase 3           Fase 4         Fase 5
FUNDAÇÃO  ──▶  NÚCLEO   ──▶   PILOTO      ──▶   EXPANSÃO   ──▶   ABERTURA  ──▶  EVOLUÇÃO
decisões      o loop        1 comunidade      3 a 5           identidade      dinheiro
fechadas      existe        convidada         comunidades     verificada      monetização
                                                              limites         colecionáveis
   │             │               │                 │              │              │
   ▼             ▼               ▼                 ▼              ▼              ▼
 nada         loop           tese da           opera sem     segurança      modelo
 ambíguo      ponta a        troca sem         curadoria     em escala      sustentável
 no papel     ponta          dinheiro          artesanal
```

---

# 4. Fase 0 — Fundação

> **Nenhuma linha de código antes desta fase terminar.** Todas as pendências abaixo afetam o modelo de dados ou a legalidade da operação. Resolvê-las depois custa retrabalho estrutural.

## 4.1 Decisões de negócio

| Item | Origem |
|---|---|
| Janela de contestação | `04 L9` |
| Prazo de manifestação em chamado | `04 L10` |
| Prazo para avaliação antes da restrição | `04 L4` |
| Validade padrão da proposta de troca | `04 L5` |
| Antecedência mínima para cancelamento pelo solicitante | `04 L2` |
| Prazo máximo da conta em Encerrando | `04 L6` |

Todos são parâmetros versionados (`05 §9`), não constantes. Escolher um valor inicial imperfeito é aceitável; **não escolher não é**, porque a estrutura de parâmetros precisa existir desde o início.

## 4.2 Decisões estruturais

| Item | Origem | Por que bloqueia |
|---|---|---|
| Congelamento do nível de proficiência no favor | `05 §14.1` | Altera o modelo de dados do favor |
| Anonimização versus exclusão de dados pessoais | `05 §11.4` | Define como o histórico é preservado |
| Base legal do contato de confiança | `05 §11.3` | Condiciona a existência da entidade |
| Retenção de mensagens de negociação | `05 §12` | Define política de armazenamento |

## 4.3 Decisões técnicas

As seis decisões de `05 §15`: plataforma cliente, natureza do armazenamento, integração entre contextos, tratamento de eventos, provedores e estratégia de expiração.

## 4.4 Trabalho jurídico

| Item | Origem |
|---|---|
| Redação do termo de ciência de riscos | `02 §8.2` |
| Escopo do acionamento de emergência | `02 §8.2` |
| Natureza jurídica da troca sem dinheiro | `02 §8.5` |
| Base legal e informação ao contato de confiança | `05 §11.3` |
| Política de privacidade e tratamento de dados | `05 §11` |
| Termos de uso | — |

> A consulta jurídica pode correr em paralelo à Fase 1. Ela bloqueia a **Fase 2**, não a construção — exceto pela decisão sobre anonimização, que é estrutural e pertence à Fase 0.

## 4.5 Definição da comunidade-piloto

Escolher **uma** comunidade concreta: um bairro, um conjunto habitacional, uma associação, uma instituição de ensino, um coletivo.

Critérios:

- proximidade geográfica real entre os participantes;
- diversidade de habilidades — uma comunidade homogênea não gera trocas;
- existência de vínculo ou confiança prévia, que compensa a ausência de verificação de identidade;
- alguém dentro da comunidade disposto a articular.

> Esta escolha determina a qualidade de toda a evidência das fases seguintes. Uma comunidade mal escolhida invalida o piloto sem invalidar o produto — e é impossível distinguir os dois casos depois.

## 4.6 Portão de saída

- Todos os parâmetros com valor inicial definido.
- Quatro decisões estruturais fechadas.
- Seis decisões técnicas fechadas.
- Termo, termos de uso e política de privacidade em redação.
- Comunidade-piloto identificada, com articulador local.
- Catálogo inicial de proficiências esboçado, refletindo as habilidades reais daquela comunidade.

---

# 5. Fase 1 — Construção do núcleo

**Objetivo:** existir o loop completo de `03 §3`, ponta a ponta, sem atalhos.

## 5.1 Entregas

| Bloco | Conteúdo |
|---|---|
| Identidade | Cadastro, verificação de contato, perfil, desativação com estado Encerrando |
| Catálogo | Proficiências, níveis, curadoria de categorias restritas |
| Oferta | Criação, congelamento, desativação de favores |
| Ticket | Emissão, carteira, estados, limite de circulação, expiração |
| Negociação | Proposta, contrapartida, chat, revisão versionada, aceite atômico |
| Consumo | Solicitação, validação de condições, agendamento, confirmação e contestação |
| Segurança | Check-in/check-out, contato de confiança, acionamento de emergência, termo |
| Moderação | Ocorrências, chamados, denúncias, bloqueio |
| Reputação | Avaliação bilateral, critérios, médias |
| Descoberta | Feed, busca, filtros, proximidade |
| Operação | Módulo administrativo `ADM` completo |
| Notificações | Eventos do loop |

## 5.2 O que costuma ser subestimado

**O módulo administrativo não é acessório.** Sem curadoria, fila de chamados e trilha de auditoria, o piloto não pode ser operado. Ele integra a Fase 1 (`04 §17`).

**A segurança presencial não é posterior.** Ela protege o primeiro encontro presencial, que acontece na primeira semana do piloto.

**O sistema de convites** (`04 RF-ADM-08`) é pré-requisito do piloto fechado.

## 5.3 Ordem sugerida de construção

```
Identidade + Catálogo
        │
        ▼
     Oferta ──▶ Ticket ──▶ Carteira
                  │
        ┌─────────┴─────────┐
        ▼                   ▼
   Negociação           Consumo
        │                   │
        └─────────┬─────────┘
                  ▼
        Segurança + Moderação
                  ▼
             Reputação
                  ▼
        Descoberta + Notificações
```

Descoberta por último é deliberado: com base convidada e pequena, as primeiras trocas acontecem por conversa, não por busca. Construir busca cedo otimiza o que ainda não é o gargalo.

## 5.4 Portão de saída

- Duas pessoas percorrem o loop completo sem intervenção técnica.
- Todas as invariantes de `05 §5` verificadas, com atenção aos pontos de concorrência de `05 §7`.
- Módulo administrativo operável por quem não construiu o sistema.
- Métricas de `03 §7` apuráveis a partir dos eventos.
- Parâmetros alteráveis sem alteração de código.

---

# 6. Fase 2 — Piloto fechado

**Objetivo:** verificar se pessoas reais trocam habilidade por habilidade, sem dinheiro.

Esta é a fase que justifica todo o resto. As anteriores constroem; esta descobre.

## 6.1 Escopo operacional

| Dimensão | Definição |
|---|---|
| Alcance | Uma comunidade, acesso por convite |
| Geografia | Concentrada — deslocamento a pé ou trajeto curto |
| Curadoria | Manual, categoria por categoria |
| Suporte | Assistido, com contato direto da equipe |
| Análise de chamados | Manual (`03 D3`) |

## 6.2 Pré-requisitos

- Termo de ciência, termos de uso e política de privacidade **publicados**.
- Base legal do contato de confiança **definida**.
- Categorias de risco físico e cuidado de pessoas vulneráveis **bloqueadas** (`02 §8.3`).
- Canal de emergência testado ponta a ponta, incluindo falha de entrega (`05 §10.1`).

## 6.3 O que observar além dos números

Números dizem se algo aconteceu; conversas dizem por quê. Nesta fase, o contato direto com participantes vale mais do que qualquer painel.

Perguntas a responder:

- As pessoas entendem o que é um ticket sem explicação?
- Alguém tentou usar a plataforma como se fosse marketplace?
- O que impediu quem se cadastrou e não ofertou nada?
- Como as pessoas decidem se uma troca é justa, na ausência de preço?
- Alguém sentiu insegurança em um atendimento presencial?

> A quarta pergunta testa diretamente o descritor de dois eixos (`02 §6`). Se as pessoas ignorarem o descritor e negociarem por outro critério, o modelo de equivalência precisa ser revisto.

## 6.4 Portão de saída

| # | Critério |
|---|---|
| G2.1 | A maioria dos participantes ativos criou ao menos um favor — a rede não é só de demanda |
| G2.2 | Trocas ocorrem sem articulação da equipe fundadora |
| G2.3 | A reciprocidade cresce ao longo de coortes mensais consecutivas (`03 H5`) |
| G2.4 | Favores agendados são efetivamente realizados, com contestação rara |
| G2.5 | Ao menos alguns tickets circulam mais de uma vez antes do consumo |
| G2.6 | Nenhum incidente de segurança grave em atendimento presencial |
| G2.7 | A carga de operação manual é sustentável no volume observado |

**G2.3 e G2.5 são os critérios centrais.** Os demais podem ser corrigidos com produto; estes dois testam a tese.

---

# 7. Fase 3 — Expansão controlada

**Objetivo:** verificar se o produto funciona sem a presença da equipe fundadora e fora da comunidade original.

## 7.1 Estratégia

Replicar o modelo em **três a cinco comunidades**, uma por vez, mantendo a concentração geográfica de cada uma.

O erro a evitar é abrir o cadastro. O objetivo não é somar usuários — é verificar se a receita de densidade é reproduzível.

Cada nova comunidade é uma **replicação do experimento**, não uma expansão de mercado. Se a segunda comunidade funcionar sem o envolvimento que a primeira teve, o modelo é transferível.

## 7.2 Entregas

| Item | Origem |
|---|---|
| Redução da operação manual — triagem, priorização, automação parcial | `03 D3` |
| Monitoramento da divergência entre nível declarado e reputação | `03 D7` |
| Descoberta amadurecida, agora que a busca é gargalo real | `04 BUS` |
| Métricas de liquidez por comunidade | `06 §10` |
| Materiais de apoio à articulação local | — |

## 7.3 Portão de saída

| # | Critério |
|---|---|
| G3.1 | Comunidades novas atingem liquidez comparável à do piloto |
| G3.2 | O tempo até a primeira troca de um novo participante diminui a cada comunidade |
| G3.3 | A operação manual não cresce proporcionalmente aos usuários |
| G3.4 | Reciprocidade e realização se mantêm fora da comunidade original |
| G3.5 | Nenhum incidente de segurança grave |

> **G3.3 é o portão que mais frequentemente falha.** Análise manual de chamados e curadoria de categorias crescem com o volume. Se a curva não se achatar nesta fase, a Fase 4 amplifica um problema em vez de resolvê-lo.

---

# 8. Fase 4 — Abertura

**Objetivo:** substituir a confiança artesanal por infraestrutura de confiança, permitindo o cadastro aberto.

Até aqui, a segurança vinha do vínculo prévio da comunidade e da curadoria manual. Abrir o cadastro remove as duas — e exige que algo as substitua.

## 8.1 Quitação das dívidas do MVP

| Dívida | Entrega |
|---|---|
| `D1` Sem validação documental de identidade | Verificação documental e prova de vida (`04 RF-CAD-18`) |
| `D2` Sem certificação verificada | Cadastro e verificação de certificados (`04 RF-PRO-10`, `RF-PRO-11`) |
| `D4` Sem limites por reputação | Limites de oferta por nível de confiança (`04 RF-REP-09`) |
| `D7` Nível autodeclarado inflado | Ação sobre divergência sistemática, com base nos dados da Fase 3 |

**A ordem importa.** `D4` depende de dados históricos que só existem após as fases 2 e 3 — implementá-lo antes produziria limites arbitrários, exatamente o que `03 §4.12` recusou.

## 8.2 Outras entregas

| Item | Origem |
|---|---|
| Reabertura gradual das categorias restritas | `02 §8.3` |
| Processo formal de disputa com evidências | `04 RF-CAN-13`, `RF-CAN-14` |
| Penalidades automáticas por recorrência | `04 RF-CAN-12` |
| Tratamento da disputa por qualidade | `04 L11`, `05 §14.4` |
| Bloqueio por endereço ou região | `04 RF-BLO-09` |

> **A lacuna L11 vence nesta fase.** Avaliação imutável e pública é sustentável em base restrita, onde o convívio regula o comportamento. Em cadastro aberto, uma avaliação injusta e permanente sem via de revisão vira problema real.

## 8.3 Portão de saída

| # | Critério |
|---|---|
| G4.1 | Identidade verificada disponível e adotada pela base ativa |
| G4.2 | Limites por reputação calibrados sobre dados reais |
| G4.3 | Categorias de risco reabertas com salvaguardas proporcionais |
| G4.4 | Processo de disputa operando dentro de prazo previsível |
| G4.5 | Indicadores de segurança estáveis após a abertura |

---

# 9. Fase 5 — Evolução do modelo

**Objetivo:** decidir o que o produto se torna, com a tese já validada ou refutada.

## 9.1 A decisão sobre dinheiro

A decisão fundadora do MVP (`03 §1.1`) adiou o dinheiro para não contaminar a validação. Nesta fase, a validação existe — e a decisão pode ser tomada com evidência.

| Resultado das fases 2 e 3 | Leitura |
|---|---|
| A troca pura funciona com liquidez saudável | O dinheiro entra como complemento opcional, sem substituir o mecanismo |
| A troca funciona apenas em nichos específicos | O dinheiro pode ser o que viabiliza as demais categorias |
| A troca pura não funciona | O dinheiro não resolve — o produto precisa ser repensado (ver seção 11) |

**Em nenhum cenário o dinheiro deve introduzir saldo, crédito ou unidade acumulável.** A restrição de modelagem de `05 §P8` permanece: qualquer mecanismo financeiro é meio de pagamento entre pessoas, nunca moeda interna da plataforma.

## 9.2 Demais entregas

| Item | Origem |
|---|---|
| Modelo operacional de dinheiro | `02 §15` |
| Monetização e planos | `02 §16` |
| Garantias e proteção | `02 §16` |
| Trocas semelhantes | `02 §6.5`, `03 D5` |
| Tickets colecionáveis e raros | `02 §22` |
| Compensação no encerramento de conta | `03 D6` |
| Conta empresarial | `02 §19` |
| Transferência condicional de tickets | `04 RF-FAV-15` |
| Recomendação personalizada | `04 RF-BUS-14` |

**Trocas semelhantes** só é viável aqui: depende de histórico coletivo suficiente, que não existe antes da Fase 3.

**Compensação no encerramento** depende de algum mecanismo de valor, e portanto da decisão sobre dinheiro.

---

# 10. Métricas

## 10.1 Como definir os limiares

As métricas de `03 §7` medem as hipóteses corretas, mas **não possuem limiar absoluto conhecido**. Não existe referência publicada para redes de troca de favores sem dinheiro em comunidades de baixa renda no Brasil.

Fixar números agora seria inventar. A abordagem adequada:

| Etapa | Ação |
|---|---|
| Fase 1 | Instrumentar todas as métricas; nenhuma meta |
| Fase 2, primeiro mês | Estabelecer a linha de base observada |
| Fase 2, meses seguintes | Definir metas como **direção sobre coortes**, não valores absolutos |
| Fase 3 | Converter em limiares absolutos, agora ancorados em dados reais |

> **Critério direcional** significa: a coorte de participantes que entrou este mês apresenta indicador melhor do que a do mês anterior. Isso é verificável sem benchmark externo e resiste ao viés dos primeiros usuários, que são sempre os mais entusiasmados.

## 10.2 Indicadores por fase

| Indicador | Fase 2 | Fase 3 | Fase 4 |
|---|---|---|---|
| Participantes com ao menos um favor | ● | ● | ● |
| Propostas por participante ativo | ● | ● | ● |
| Taxa de aceite de propostas | ● | ● | ● |
| Consumos concluídos sobre tickets transferidos | ● | ● | ● |
| Reciprocidade — prestou e recebeu | ● | ● | ● |
| Tempo até a primeira troca | ● | ● | ● |
| Taxa de cancelamento por papel | ● | ● | ● |
| Taxa de contestação de realização | ● | ● | ● |
| Chamados por cem favores realizados | | ● | ● |
| Liquidez — tickets que encontram troca | | ● | ● |
| Profundidade de circulação | ● | ● | ● |
| Cobertura de proficiências na comunidade | ● | ● | |
| Divergência entre nível declarado e reputação | | ● | ● |
| Carga de operação manual por usuário ativo | | ● | ● |

## 10.3 Profundidade de circulação

Este indicador merece destaque porque testa o conceito central do produto.

> **Quantas vezes um ticket muda de mãos antes de ser consumido?**

| Observação | Leitura |
|---|---|
| Sempre 1 — quem recebe consome | O ticket é um vale nominal; a transferibilidade não está em uso |
| Frequentemente 2 ou mais | O ticket funciona como instrumento de troca e a rede tem densidade real |

Se a profundidade permanecer em 1, a transferibilidade — que é a resposta do produto à dupla coincidência de desejos (seção 2.3) — não está operando. Isso não invalida o produto, mas indica que ele funciona como catálogo de favores, e não como rede de circulação. É uma diferença estratégica relevante.

## 10.4 O que não medir

Instalações, cadastros totais, acessos e tempo de sessão **não são indicadores de sucesso** deste produto e não devem orientar decisões.

O sucesso do Ajude.me é medido em problemas resolvidos porque alguém se dispôs a ajudar (`01 §9`). Um usuário que resolveu sua necessidade e ficou meses sem abrir o aplicativo é caso de sucesso, não de evasão.

---

# 11. Critérios de descontinuidade

Um roadmap honesto define o que faria parar, não apenas o que faria continuar.

| Situação | Leitura |
|---|---|
| Após a Fase 2, a esmagadora maioria só busca ajuda e não oferece nada | A tese da reciprocidade não se sustenta; o produto é um pedido de serviço gratuito com etapas extras |
| Propostas são enviadas mas quase nunca aceitas | A equivalência sem preço não funciona na prática; o modelo de valor precisa ser refeito antes de qualquer expansão |
| Trocas são fechadas mas os favores não se realizam | O compromisso sem custo não gera obrigação; o produto precisa de mecanismo de garantia antes de escalar |
| Incidente grave de segurança em atendimento presencial | Suspensão imediata dos presenciais e reavaliação completa das salvaguardas |
| A Fase 3 não reproduz a Fase 2 em nenhuma comunidade nova | O sucesso do piloto era da comunidade, não do produto |

> Nenhuma dessas situações significa necessariamente encerrar o projeto. Significam que **prosseguir sem mudar seria desperdício**. A informação obtida em cada caso é valiosa e aponta o que precisa ser diferente.

---

# 12. Mapa de pendências por fase

Consolidação de tudo que está em aberto na documentação, com a fase em que deve ser resolvido.

| Pendência | Origem | Fase |
|---|---|---|
| Parâmetros temporais (6 itens) | `04 §19.2` | 0 |
| Congelamento do nível no favor | `05 §14.1` | 0 |
| Anonimização versus exclusão | `05 §11.4` | 0 |
| Retenção de mensagens de negociação | `05 §12` | 0 |
| Decisões técnicas (6 itens) | `05 §15` | 0 |
| Termo de ciência e emergência | `02 §8.2` | 0 → publicado na 2 |
| Natureza jurídica da troca | `02 §8.5` | 0 |
| Base legal do contato de confiança | `05 §11.3` | 0 → bloqueia a 2 |
| Metas numéricas | `05 §13` | 2 → 3 |
| Redução da operação manual | `03 D3` | 3 |
| Divergência de nível declarado | `03 D7` | 3 |
| Validação documental de identidade | `03 D1` | 4 |
| Certificações verificadas | `03 D2` | 4 |
| Limites por reputação | `03 D4` | 4 |
| Disputa por qualidade | `04 L11` | 4 |
| Processo formal de disputa e evidências | `02 §14` | 4 |
| Penalidades automáticas | `02 §9.1` | 4 |
| Modelo operacional de dinheiro | `02 §15` | 5 |
| Monetização e garantias | `02 §16` | 5 |
| Trocas semelhantes | `03 D5` | 5 |
| Compensação no encerramento | `03 D6` | 5 |

---

# 13. Riscos

| # | Risco | Fase | Mitigação |
|---|---|---|---|
| R1 | Densidade insuficiente para gerar trocas | 2 | Concentração geográfica; comunidade com habilidades diversas |
| R2 | Rede assimétrica — muita demanda, pouca oferta | 2 | Convite condicionado à declaração de ao menos uma proficiência |
| R3 | Uso como marketplace gratuito | 2 | Linguagem, ausência de preço, reciprocidade visível no perfil |
| R4 | Incidente de segurança em atendimento presencial | 2 em diante | Curadoria de categorias, contato de confiança, comparecimento registrado |
| R5 | Operação manual satura | 3 | Automação parcial e triagem antes da expansão |
| R6 | Sucesso atribuído à comunidade, não ao produto | 3 | Replicação em comunidades distintas entre si |
| R7 | Abertura sem infraestrutura de confiança | 4 | Portão G4.1 a G4.5 |
| R8 | Dinheiro descaracterizar o produto | 5 | Restrição de modelagem `05 P8` mantida permanentemente |
| R9 | Passivo de dados pessoais | 0 em diante | Definição jurídica na Fase 0; minimização e revelação por contexto |

> **R2 merece atenção específica.** É a falha mais provável e a mais silenciosa: a rede parece crescer enquanto se torna inutilizável. O indicador de reciprocidade (`03 H5`) é o alarme, e por isso ele é observado desde a primeira coorte.

---

# 14. Fora deste roadmap

| Tema | Motivo |
|---|---|
| Datas e cronograma | Dependem de capacidade de execução ainda não definida |
| Custo e orçamento | Dependem das decisões técnicas da Fase 0 |
| Estratégia de marketing | Incompatível com base convidada até a Fase 4 |
| Modelo societário e captação | Fora do escopo da documentação de produto |

---

# 15. Documentação seguinte

Este documento encerra a série planejada em `01 §10`. A partir daqui, a documentação deixa de ser conceitual:

| Documento | Momento |
|---|---|
| Design e fluxos de interface | Fase 0 → 1 |
| Especificação técnica e modelo físico | Fase 0 → 1 |
| Manual de operação e curadoria | Fase 1 → 2 |
| Guia de implantação em nova comunidade | Fase 2 → 3 |

---

# 16. Status do documento

| Item | Status |
|---|---|
| Estrutura em fases e portões | ✅ Definida |
| Restrição de densidade | ✅ Definida |
| Fase 0 — Fundação | ✅ Definida |
| Fase 1 — Construção do núcleo | ✅ Definida |
| Fase 2 — Piloto fechado | ✅ Definida |
| Fase 3 — Expansão controlada | ✅ Definida |
| Fase 4 — Abertura | ✅ Definida |
| Fase 5 — Evolução do modelo | ✅ Definida |
| Método de definição de métricas | ✅ Definido |
| Critérios de descontinuidade | ✅ Definidos |
| Mapa de pendências por fase | ✅ Consolidado |
| Riscos | ✅ Mapeados |
| Datas e cronograma | 🟡 Dependem da capacidade de execução |
| Metas numéricas | 🟡 Definidas nas fases 2 e 3 |
| Comunidade-piloto | 🟡 A identificar — Fase 0 |

---

**Versão:** 1.0
**Produto:** Ajude.me
**Documento:** Roadmap do Produto
