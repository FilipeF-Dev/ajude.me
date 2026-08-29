# Ajude.me — Requisitos Funcionais

> Documento que traduz os conceitos e regras do Ajude.me em requisitos verificáveis. Enquanto o documento 02 define **o que vale**, este define **o que o sistema faz**.
>
> Base: `02 - Conceito e Regras de Negócio.md` (v2.3) e `03 - MVP.md` (v1.3).

---

# 1. Como ler este documento

## 1.1 Identificação

Cada requisito possui um identificador estável no formato:

> **RF-`MÓDULO`-`nº`**

O identificador **nunca é reaproveitado**. Requisitos removidos permanecem registrados como cancelados, para que referências antigas não apontem para conteúdo diferente do original.

## 1.2 Prioridade

| Marca | Significado |
|---|---|
| 🟢 | Escopo do MVP |
| ⚪ | Posterior ao MVP |

## 1.3 Origem

A coluna **Origem** referencia a regra que sustenta o requisito:

- `02 §5.4` → seção 5.4 do documento de Conceito e Regras de Negócio
- `03 §4.7` → seção 4.7 do documento de MVP
- `—` → requisito derivado, sem regra explícita nos documentos anteriores

Requisitos marcados com `—` merecem atenção: são consequências necessárias das regras existentes, mas nunca foram decididos explicitamente. Todos estão consolidados na seção 18.

---

# 2. Atores

| Ator | Descrição |
|---|---|
| **Visitante** | Pessoa não autenticada. |
| **Usuário** | Pessoa autenticada. Atua como Ajudante e Solicitante conforme o contexto. |
| **Ajudante** | Papel do usuário que oferece o favor. |
| **Solicitante** | Papel do usuário que possui o ticket e solicita o favor. |
| **Contato de confiança** | Terceiro indicado pelo usuário, que recebe informações de segurança sobre atendimentos presenciais. Não possui conta na plataforma. |
| **Operação** | Equipe da plataforma responsável por catálogo, curadoria, análise de ocorrências e denúncias. |
| **Sistema** | Ações automáticas executadas sem intervenção humana. |

---

# 3. Módulos

| Sigla | Módulo |
|---|---|
| `CAD` | Cadastro, identidade e conta |
| `PRO` | Proficiências |
| `FAV` | Favores |
| `TIC` | Tickets e carteira |
| `BUS` | Descoberta e busca por necessidade |
| `NEG` | Negociação e troca |
| `SOL` | Solicitação e consumo |
| `SEG` | Segurança em atendimento presencial |
| `CAN` | Cancelamentos e ocorrências |
| `AVA` | Avaliações |
| `REP` | Reputação |
| `BLO` | Bloqueio e denúncia |
| `NOT` | Notificações |
| `ADM` | Administração e curadoria |

---

# 4. CAD — Cadastro, identidade e conta

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-CAD-01 | O visitante pode criar conta informando nome, e-mail, telefone, CPF, senha e localização. | 🟢 | `02 §2.1` |
| RF-CAD-02 | O sistema valida o CPF por dígito verificador e recusa CPF inválido. | 🟢 | `03 §4.1` |
| RF-CAD-03 | O sistema impede o cadastro de CPF já associado a outra conta, inclusive contas desativadas. | 🟢 | `02 §2.1` |
| RF-CAD-04 | O sistema verifica o e-mail por código ou link de confirmação. | 🟢 | `03 §4.1` |
| RF-CAD-05 | O sistema verifica o telefone por código enviado via SMS. | 🟢 | `03 §4.1` |
| RF-CAD-06 | O sistema exibe o selo **"Contato verificado"** somente quando e-mail e telefone estiverem verificados. | 🟢 | `03 §4.1` |
| RF-CAD-07 | O usuário autentica-se com e-mail e senha. | 🟢 | `03 §4.1` |
| RF-CAD-08 | O usuário recupera o acesso por fluxo de redefinição de senha. | 🟢 | `03 §4.1` |
| RF-CAD-09 | O usuário edita seus dados de perfil, incluindo foto e localização. | 🟢 | `02 §2.1` |
| RF-CAD-10 | O usuário não pode alterar o CPF após o cadastro. | 🟢 | `—` |
| RF-CAD-11 | O usuário solicita a desativação da conta. | 🟢 | `02 §2.2` |
| RF-CAD-12 | Ao solicitar a desativação, a conta entra no estado **Encerrando** caso possua tickets em circulação. | 🟢 | `03 §4.1` |
| RF-CAD-13 | Conta em estado Encerrando não pode criar favores, emitir tickets, receber propostas nem enviar propostas. | 🟢 | `03 §4.1` |
| RF-CAD-14 | Conta em estado Encerrando permanece obrigada a atender solicitações que respeitem as condições dos tickets emitidos. | 🟢 | `03 §4.1` |
| RF-CAD-15 | O sistema encerra definitivamente a conta quando todos os tickets emitidos por ela forem consumidos ou expirados. | 🟢 | `03 §4.1` |
| RF-CAD-16 | O usuário sem tickets em circulação tem a conta desativada imediatamente. | 🟢 | `—` |
| RF-CAD-17 | O usuário informa representação de empresa/CNPJ em seu perfil. | ⚪ | `02 §19` |
| RF-CAD-18 | O sistema valida a identidade por documento e prova de vida. | ⚪ | `02 §2.1` |

## 4.1 Estados da conta

| Estado | Pode ofertar | Pode negociar | Deve atender |
|---|---|---|---|
| `ATIVA` | Sim | Sim | Sim |
| `ENCERRANDO` | Não | Não | Sim |
| `DESATIVADA` | Não | Não | Não |
| `SUSPENSA` | Não | Não | Sob análise da Operação |

---

# 5. PRO — Proficiências

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-PRO-01 | A Operação mantém o catálogo de proficiências, organizado em Categoria → Subcategoria → Proficiência. | 🟢 | `02 §3.1` |
| RF-PRO-02 | O usuário adiciona ao perfil proficiências existentes no catálogo. | 🟢 | `02 §3.1` |
| RF-PRO-03 | O usuário não pode cadastrar proficiência fora do catálogo. | 🟢 | `02 §3.1` |
| RF-PRO-04 | Ao adicionar uma proficiência, o usuário declara seu nível entre **Básico**, **Intermediário**, **Avançado** e **Especialista**. | 🟢 | `03 §4.2` |
| RF-PRO-05 | O usuário altera o nível declarado de uma proficiência. | 🟢 | `—` |
| RF-PRO-06 | O usuário remove uma proficiência do perfil. | 🟢 | `02 §3.1` |
| RF-PRO-07 | O sistema impede a remoção de proficiência que possua favor ativo vinculado ou tickets em circulação. | 🟢 | `—` |
| RF-PRO-08 | O sistema nunca altera automaticamente o nível declarado com base em avaliações. | 🟢 | `02 §3.3` |
| RF-PRO-09 | O sistema exibe o nível declarado ao lado da reputação obtida naquela proficiência. | 🟢 | `02 §6.3` |
| RF-PRO-10 | O usuário cadastra certificados vinculados a uma proficiência. | ⚪ | `02 §3.2` |
| RF-PRO-11 | O sistema distingue certificado verificado de não verificado. | ⚪ | `02 §3.2` |

> **RF-PRO-07** protege a regra de que todo favor deve estar ancorado em proficiência declarada (`02 §4.1`). Sem ele, o usuário poderia remover a proficiência e deixar favores e tickets órfãos.

---

# 6. FAV — Favores

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-FAV-01 | O usuário cria um favor vinculado obrigatoriamente a uma proficiência declarada em seu perfil. | 🟢 | `02 §4.1` |
| RF-FAV-02 | O sistema impede a criação de favor baseado em proficiência não declarada. | 🟢 | `02 §4.1` |
| RF-FAV-03 | O favor exige os campos obrigatórios definidos em `03 §4.3`. | 🟢 | `03 §4.3` |
| RF-FAV-04 | O sistema calcula e exibe o **descritor** do favor a partir do tempo estimado e do nível da proficiência vinculada. | 🟢 | `02 §6.2` |
| RF-FAV-05 | O nível de proficiência não é campo editável do favor: é herdado da proficiência vinculada até o congelamento (RF-FAV-22). | 🟢 | `02 §6.2` |
| RF-FAV-06 | O usuário define a quantidade máxima de tickets do favor e quantos deseja emitir no momento. | 🟢 | `02 §5.1` |
| RF-FAV-07 | O usuário emite tickets adicionais a qualquer momento, respeitando o limite de tickets em circulação. | 🟢 | `02 §5.1` |
| RF-FAV-08 | O usuário define o favor como **trocável** ou **não trocável**. | 🟢 | `02 §5.3` |
| RF-FAV-09 | O usuário edita o favor livremente enquanto não houver nenhum ticket emitido. | 🟢 | `03 §4.3` |
| RF-FAV-10 | O sistema congela a edição do favor a partir da emissão do primeiro ticket. | 🟢 | `03 §4.3` |
| RF-FAV-11 | O usuário desativa um favor a qualquer momento. | 🟢 | `02 §4.3` |
| RF-FAV-12 | A desativação impede novas emissões e **não invalida** tickets já emitidos. | 🟢 | `02 §4.3` |
| RF-FAV-13 | O sistema impede a criação de favor em categoria restrita pela curadoria. | 🟢 | `02 §8.3` |
| RF-FAV-14 | O usuário consulta a relação de seus favores com a situação de cada um. | 🟢 | `—` |
| RF-FAV-15 | O usuário define transferência condicional dos tickets, sujeita à sua autorização caso a caso. | ⚪ | `02 §5.3` |
| RF-FAV-16 | O sistema calcula os tickets em circulação considerando apenas os estados `DISPONIVEL`, `EM_NEGOCIACAO`, `RESERVADO` e `EM_ANALISE`. | 🟢 | `02 §5.1` |
| RF-FAV-17 | Tickets `CONSUMIDO`, `ENCERRADO`, `EXPIRADO` e `DESCARTADO` liberam vaga para nova emissão. | 🟢 | `02 §5.1` |
| RF-FAV-18 | O sistema exibe ao ajudante quantas vagas de emissão estão disponíveis no favor. | 🟢 | `—` |
| RF-FAV-19 | O sistema impede a emissão de tickets em favor desativado, ainda que haja vaga. | 🟢 | `02 §4.3` |
| RF-FAV-20 | O sistema impede a alteração da quantidade máxima após a emissão do primeiro ticket. | 🟢 | `—` |
| RF-FAV-21 | O sistema limita a validade do ticket a **1 ano**. | 🟢 | `02 §21` |
| RF-FAV-22 | O favor guarda o **nível congelado** na primeira emissão; alterações posteriores no nível declarado não afetam favores já publicados. | 🟢 | `05 §14.1` |

## 6.1 Regra de congelamento

O congelamento previsto em **RF-FAV-10** existe para sustentar o princípio de que *as condições devem ser conhecidas antes da negociação* (`02 §20`, item 2).

Após a primeira emissão, o ajudante pode apenas:

- desativar o favor;
- emitir tickets adicionais dentro do limite de circulação já definido.

Alterações de condição exigem a criação de um **novo favor**. O favor anterior permanece existindo enquanto houver tickets válidos vinculados a ele.

A quantidade máxima está entre as condições congeladas (**RF-FAV-20**): elevá-la aumenta a carga de compromissos simultâneos do ajudante e, por consequência, a concorrência pela sua agenda — o que altera as condições conhecidas por quem já possui um ticket.

## 6.2 Limite de circulação

O limite não é estoque vitalício. Ele controla quantos compromissos simultâneos o ajudante assume.

> Favor com limite 5, todos os 5 emitidos e 2 já consumidos
> → 3 tickets em circulação
> → 2 vagas disponíveis para nova emissão

A consequência prática é que um favor bem-sucedido **nunca se esgota**: à medida que os favores são prestados, novas vagas se abrem.

---

# 7. TIC — Tickets e carteira

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-TIC-01 | Cada ticket possui identificador único e imutável. | 🟢 | `02 §5` |
| RF-TIC-02 | Um ticket representa exatamente uma realização do favor. | 🟢 | `02 §5.2` |
| RF-TIC-03 | O ticket emitido é creditado na carteira do ajudante que o emitiu. | 🟢 | `—` |
| RF-TIC-04 | O sistema mantém o histórico completo de propriedade de cada ticket. | 🟢 | `03 §4.5` |
| RF-TIC-05 | A carteira apresenta os tickets segregados por situação: Disponíveis, Em negociação, Reservados, Consumidos e Expirados. | 🟢 | `02 §7` |
| RF-TIC-06 | O sistema move o ticket para a Carteira de Expirados ao atingir a validade. | 🟢 | `02 §7.1` |
| RF-TIC-07 | O ticket expirado não concede direito de solicitar o favor. | 🟢 | `02 §7.1` |
| RF-TIC-08 | O usuário mantém ou exclui tickets expirados. | 🟢 | `02 §7.1` |
| RF-TIC-09 | O sistema impede a expiração de ticket `RESERVADO` cuja realização esteja agendada para data dentro da validade. | 🟢 | `03 §4.5` |
| RF-TIC-10 | O sistema cancela a proposta correspondente quando um ticket `EM_NEGOCIACAO` atinge a validade. | 🟢 | `02 §5.4` |
| RF-TIC-11 | O usuário consulta a origem do ticket: favor, ajudante emissor e condições vigentes. | 🟢 | `—` |
| RF-TIC-12 | O usuário move tickets expirados para uma carteira de coleção. | ⚪ | `02 §22` |

## 7.1 Estados do ticket

| Estado | Significado |
|---|---|
| `DISPONIVEL` | Na carteira do proprietário, livre para uso ou troca |
| `EM_NEGOCIACAO` | Bloqueado por proposta ativa |
| `RESERVADO` | Vinculado a solicitação agendada |
| `EM_ANALISE` | Bloqueado por contestação da realização em apuração |
| `CONSUMIDO` | Favor realizado, aguardando avaliações |
| `ENCERRADO` | Ciclo completo, ambas as avaliações registradas |
| `EXPIRADO` | Validade atingida |
| `DESCARTADO` | Expirado e excluído pelo proprietário |

## 7.2 Transições

| De | Evento | Para |
|---|---|---|
| `DISPONIVEL` | Proposta enviada | `EM_NEGOCIACAO` |
| `EM_NEGOCIACAO` | Proposta recusada, retirada ou expirada | `DISPONIVEL` |
| `EM_NEGOCIACAO` | Proposta aceita | `DISPONIVEL` (novo proprietário) |
| `EM_NEGOCIACAO` | Validade atingida | `EXPIRADO` |
| `DISPONIVEL` | Solicitação agendada | `RESERVADO` |
| `RESERVADO` | Cancelamento válido | `DISPONIVEL` |
| `RESERVADO` | Realização confirmada ou tacitamente confirmada | `CONSUMIDO` |
| `RESERVADO` | Realização contestada | `EM_ANALISE` |
| `EM_ANALISE` | Análise conclui pela realização | `CONSUMIDO` |
| `EM_ANALISE` | Análise conclui pela não realização ou é inconclusiva | `DISPONIVEL` |
| `CONSUMIDO` | Ambas as avaliações registradas | `ENCERRADO` |
| `DISPONIVEL` | Validade atingida | `EXPIRADO` |
| `EXPIRADO` | Exclusão pelo proprietário | `DESCARTADO` |

**RF-TIC-13** 🟢 — O sistema recusa qualquer transição não prevista nesta tabela.

**RF-TIC-14** 🟢 — Ticket em `EM_ANALISE` não pode ser trocado, solicitado nem excluído. `02 §8.5`

**RF-TIC-15** 🟢 — O sistema impede a expiração de ticket em `EM_ANALISE`; a validade fica suspensa até a decisão. `—`

> **RF-TIC-15** é necessário para que a demora da própria plataforma não extinga o direito em disputa.

---

# 8. BUS — Descoberta e busca por necessidade

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-BUS-01 | O usuário visualiza um feed de favores disponíveis, ordenado por proximidade e data de publicação. | 🟢 | `03 §4.6` |
| RF-BUS-02 | O feed inicial é neutro e não assume papel de ajudante nem de solicitante. | 🟢 | `03 §4.6` |
| RF-BUS-03 | O usuário busca favores por texto livre. | 🟢 | `03 §4.6` |
| RF-BUS-04 | O usuário filtra por categoria e subcategoria. | 🟢 | `03 §4.6` |
| RF-BUS-05 | O usuário filtra por modalidade presencial ou remota. | 🟢 | `03 §4.6` |
| RF-BUS-06 | O usuário filtra por nível de proficiência. | 🟢 | `—` |
| RF-BUS-07 | O usuário ordena os resultados por proximidade geográfica. | 🟢 | `02 §17` |
| RF-BUS-08 | A página do favor exibe todas as condições antes de qualquer negociação. | 🟢 | `02 §4.2` |
| RF-BUS-09 | O sistema exibe o descritor do favor nos resultados de busca e no feed. | 🟢 | `02 §6.2` |
| RF-BUS-10 | O sistema oculta favores desativados e favores sem tickets disponíveis. | 🟢 | `—` |
| RF-BUS-11 | O sistema oculta favores de usuários bloqueados pelo usuário corrente, e vice-versa. | 🟢 | `02 §18.2` |
| RF-BUS-12 | O visitante não autenticado visualiza o feed, mas não acessa dados de perfil nem inicia negociação. | 🟢 | `—` |
| RF-BUS-13 | O usuário consulta o perfil público de outro usuário, com proficiências, reputação e favores ativos. | 🟢 | `03 §4.6` |
| RF-BUS-14 | O sistema recomenda favores com base no histórico do usuário. | ⚪ | `03 §4.6` |
| RF-BUS-15 | O usuário filtra por faixa de reputação. | ⚪ | `03 §4.6` |
| RF-BUS-16 | O usuário descreve sua necessidade em linguagem natural, no campo principal da tela inicial. | 🟢 | `02 §3.4` |
| RF-BUS-17 | O sistema reconhece as proficiências relacionadas à necessidade descrita. | 🟢 | `02 §3.4` |
| RF-BUS-18 | O sistema apresenta as proficiências reconhecidas **antes** dos resultados, tornando visível a interpretação. | 🟢 | `—` |
| RF-BUS-19 | O usuário confirma ou descarta cada proficiência reconhecida, refinando o resultado. | 🟢 | `—` |
| RF-BUS-20 | Os resultados apresentam apenas favores ativos com tickets disponíveis, ordenados por proximidade. | 🟢 | `02 §3.4` |
| RF-BUS-21 | Quando não há favor correspondente, o sistema informa a ausência e oferece alternativas. | 🟢 | `02 §3.5` |
| RF-BUS-22 | O usuário solicita aviso para quando alguém passar a oferecer favores da proficiência reconhecida. | 🟢 | `02 §3.5` |
| RF-BUS-23 | O sistema notifica o usuário quando um favor da proficiência aguardada passa a ter ticket disponível em sua região. | 🟢 | `02 §3.5` |
| RF-BUS-24 | O sistema oferece ampliar a área de busca quando não há resultado na região do usuário. | 🟢 | `—` |
| RF-BUS-25 | O sistema registra as buscas sem resultado, por proficiência e região, para análise de cobertura. | 🟢 | `02 §3.5` |
| RF-BUS-26 | A necessidade registrada não é pública nem exibida em qualquer perfil. | 🟢 | `02 §18.3` |
| RF-BUS-27 | Nenhum usuário é notificado de que outra pessoa procurou determinado favor. | 🟢 | `02 §18.3` |
| RF-BUS-28 | A análise de cobertura utiliza dados agregados por região e proficiência, nunca buscas individuais atribuíveis. | 🟢 | `02 §18.3` |
| RF-BUS-29 | O aviso solicitado pelo usuário vincula-se à proficiência, não ao texto digitado. | 🟢 | `02 §18.3` |
| RF-BUS-30 | Reconhecimento semântico de necessidade, além dos termos populares. | ⚪ | `02 §3.4` |

## 8.1 Busca por necessidade

A busca por necessidade substitui o pedido de ajuda como entidade. Não existe publicação de demanda: existe descrição de necessidade e reconhecimento de competência.

```
"trocar a resistência do chuveiro"
            │
            ▼  RF-BUS-17 — reconhecimento por termos populares
   ┌────────────────────┐
   │ Elétrica residencial │  ← RF-BUS-18: exibida ao usuário
   │ Reparos domésticos   │  ← RF-BUS-19: pode ser descartada
   └────────┬───────────┘
            ▼  RF-BUS-20
   Favores ativos daquela proficiência, com ticket disponível, por proximidade
```

**RF-BUS-18 não é detalhe de interface.** O reconhecimento pode errar, e um resultado inexplicado parece defeito. Mostrar a proficiência interpretada permite que o usuário entenda o resultado e o corrija — e transforma o erro de vocabulário em algo observável pela Operação.

### Ausência de resultado

Uma busca sem correspondência é o principal sinal de demanda da plataforma (`02 §3.5`). Ela produz três efeitos:

| Efeito | Requisito |
|---|---|
| O usuário entra na fila de aviso daquela proficiência | RF-BUS-22, RF-BUS-23 |
| O usuário pode ampliar a área de busca | RF-BUS-24 |
| A Operação passa a conhecer a lacuna de cobertura da região | RF-BUS-25, RF-ADM-16 |

---

# 9. NEG — Negociação e troca

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-NEG-01 | O usuário compõe uma proposta selecionando tickets desejados de outro usuário e a quantidade. | 🟢 | `02 §5.4` |
| RF-NEG-02 | O usuário compõe a contrapartida selecionando tickets da própria carteira. | 🟢 | `02 §5.4` |
| RF-NEG-03 | O sistema impede a inclusão de ticket **não trocável** na contrapartida. | 🟢 | `02 §5.4` |
| RF-NEG-04 | O sistema impede que um ticket participe de mais de uma proposta simultaneamente. | 🟢 | `02 §5.4` |
| RF-NEG-05 | Ao envio da proposta, o sistema bloqueia todos os tickets envolvidos em `EM_NEGOCIACAO`. | 🟢 | `02 §5.4` |
| RF-NEG-06 | O destinatário aceita, recusa ou abre negociação sobre a proposta. | 🟢 | `02 §5.4` |
| RF-NEG-07 | A abertura de negociação disponibiliza um chat vinculado à proposta. | 🟢 | `02 §5.4` |
| RF-NEG-08 | Qualquer uma das partes revisa a composição da oferta durante a negociação. | 🟢 | `02 §5.4` |
| RF-NEG-09 | Toda revisão da oferta invalida manifestações anteriores de aceite. | 🟢 | `—` |
| RF-NEG-10 | O aceite transfere todos os tickets envolvidos de forma atômica. | 🟢 | `02 §5.4` |
| RF-NEG-11 | O sistema libera os tickets bloqueados quando a proposta é recusada, retirada ou expirada. | 🟢 | `02 §5.4` |
| RF-NEG-12 | O remetente retira a proposta enquanto ela não tiver sido aceita. | 🟢 | `02 §5.4` |
| RF-NEG-13 | A proposta expira automaticamente após o prazo de validade. | 🟢 | `02 §5.4` |
| RF-NEG-14 | O sistema impede o envio de proposta a usuário bloqueado ou que tenha bloqueado o remetente. | 🟢 | `02 §18.2` |
| RF-NEG-15 | O sistema impede o envio de nova proposta por usuário com avaliação pendente além do prazo. | 🟢 | `02 §11.4` |
| RF-NEG-16 | O sistema impede proposta cujo remetente ou destinatário esteja com conta Encerrando, Desativada ou Suspensa. | 🟢 | `03 §4.1` |
| RF-NEG-17 | O usuário consulta o histórico de propostas enviadas e recebidas. | 🟢 | `—` |
| RF-NEG-18 | A contrapartida pode incluir valor em dinheiro. | ⚪ | `02 §15` |
| RF-NEG-19 | O sistema impede a inclusão de ticket **não trocável** também entre os tickets solicitados. | 🟢 | `02 §5.4` |
| RF-NEG-20 | O sistema exige ao menos um ticket solicitado e admite contrapartida vazia. | 🟢 | `02 §5.4` |
| RF-NEG-21 | Após uma revisão, o aceite cabe à parte que **não** a criou. | 🟢 | `02 §5.4` |

## 9.1 Estados da proposta

| Estado | Significado |
|---|---|
| `RASCUNHO` | Em composição, não enviada |
| `ENVIADA` | Aguardando resposta |
| `EM_NEGOCIACAO` | Chat aberto, oferta revisável |
| `ACEITA` | Tickets transferidos |
| `RECUSADA` | Encerrada pelo destinatário |
| `RETIRADA` | Encerrada pelo remetente |
| `EXPIRADA` | Prazo esgotado |

## 9.2 Atomicidade

**RF-NEG-10** exige que a transferência seja indivisível. O sistema deve garantir que, diante de falha em qualquer ponto da operação, nenhum ticket mude de proprietário e todos retornem ao estado imediatamente anterior.

A verificação de elegibilidade de cada ticket — disponibilidade, validade e condição de troca — ocorre **no instante do aceite**, e não apenas no envio da proposta.

---

# 10. SOL — Solicitação e consumo

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-SOL-01 | O portador do ticket solicita o favor informando data e horário pretendidos. | 🟢 | `02 §8` |
| RF-SOL-02 | O sistema valida a solicitação contra as condições publicadas: dia, horário, antecedência mínima, validade e área de atendimento. | 🟢 | `02 §8` |
| RF-SOL-03 | O sistema impede o envio de solicitação que viole qualquer condição publicada. | 🟢 | `02 §8` |
| RF-SOL-04 | O ajudante confirma a solicitação, que passa a `AGENDADA`. | 🟢 | `02 §8` |
| RF-SOL-05 | O ajudante somente recusa solicitação que viole as condições publicadas, informando o motivo. | 🟢 | `02 §8` |
| RF-SOL-06 | O agendamento move o ticket para `RESERVADO`. | 🟢 | `03 §4.5` |
| RF-SOL-07 | O agendamento libera às partes o telefone e o endereço de atendimento. | 🟢 | `02 §18.1` |
| RF-SOL-08 | Após a data prevista, o sistema abre a **janela de contestação** e convida ambas as partes a confirmar a realização. | 🟢 | `02 §8.4` |
| RF-SOL-09 | A confirmação por ambas as partes encerra a janela imediatamente e move o ticket para `CONSUMIDO`. | 🟢 | `02 §8.4` |
| RF-SOL-10 | O sistema considera o favor realizado por **confirmação tácita** quando a janela se encerra sem contestação, independentemente de haver confirmações. | 🟢 | `02 §8.4` |
| RF-SOL-11 | Qualquer das partes contesta a realização durante a janela, sem exigência de justificativa prévia. | 🟢 | `02 §8.4` |
| RF-SOL-12 | A contestação move a solicitação para `EM_ANALISE`, bloqueia o ticket e abre um **chamado** para a Operação. | 🟢 | `02 §8.5` |
| RF-SOL-13 | O sistema notifica ambas as partes antes do encerramento da janela de contestação. | 🟢 | `02 §8.4` |
| RF-SOL-14 | O sistema solicita a versão de cada parte sobre o ocorrido, dentro de prazo determinado. | 🟢 | `02 §8.5` |
| RF-SOL-15 | A ausência de manifestação de uma das partes não impede o prosseguimento da análise. | 🟢 | `02 §8.5` |
| RF-SOL-16 | A decisão pela realização move o ticket para `CONSUMIDO` e registra ocorrência contra quem contestou sem fundamento. | 🟢 | `02 §8.5` |
| RF-SOL-17 | A decisão pela não realização devolve o ticket a `DISPONIVEL` e registra ocorrência contra o ajudante. | 🟢 | `02 §8.5` |
| RF-SOL-18 | A decisão inconclusiva devolve o ticket a `DISPONIVEL` sem registrar ocorrência contra nenhuma das partes. | 🟢 | `02 §8.5` |
| RF-SOL-19 | O sistema comunica a decisão e seu fundamento a ambas as partes. | 🟢 | `02 §8.5` |
| RF-SOL-20 | As avaliações são liberadas apenas quando o ticket atinge `CONSUMIDO`, seja por confirmação, confirmação tácita ou decisão da Operação. | 🟢 | `02 §11.1` |
| RF-SOL-21 | O encerramento revoga o acesso aos dados de contato liberados no agendamento. | 🟢 | `02 §18.1` |
| RF-SOL-22 | O usuário consulta suas solicitações em andamento, como ajudante e como solicitante. | 🟢 | `—` |
| RF-SOL-23 | O sistema impede o agendamento de solicitações sobrepostas para o mesmo ajudante no mesmo intervalo. | 🟢 | `—` |
| RF-SOL-24 | O sistema impede que o ajudante solicite o próprio favor, ainda que possua o ticket. | 🟢 | `02 §8` |
| RF-SOL-25 | O sistema exige que o intervalo previsto inteiro caiba dentro de uma janela de disponibilidade publicada. | 🟢 | `02 §8` |
| RF-SOL-26 | O sistema impede solicitação cuja data prevista ultrapasse a validade do ticket. | 🟢 | `02 §8` |
| RF-SOL-27 | O sistema recusa solicitação de favor presencial quando falta localização em qualquer das duas contas. | 🟢 | `02 §8` |
| RF-SOL-28 | A solicitação de favor cujo favor foi desativado após a emissão continua sendo aceita. | 🟢 | `02 §4.3` |

## 10.1 Estados da solicitação

| De | Evento | Para |
|---|---|---|
| — | Solicitação enviada | `SOLICITADA` |
| `SOLICITADA` | Confirmação do ajudante | `AGENDADA` |
| `SOLICITADA` | Violação de condição | `RECUSADA` |
| `SOLICITADA` | Cancelamento pelo solicitante | `CANCELADA_SOLICITANTE` |
| `AGENDADA` | Cancelamento pelo ajudante | `CANCELADA_AJUDANTE` |
| `AGENDADA` | Cancelamento pelo solicitante | `CANCELADA_SOLICITANTE` |
| `AGENDADA` | Data alcançada | `AGUARDANDO_CONFIRMACAO` |
| `AGUARDANDO_CONFIRMACAO` | Ambos confirmam | `REALIZADA` |
| `AGUARDANDO_CONFIRMACAO` | Janela encerrada sem contestação | `REALIZADA` (tácita) |
| `AGUARDANDO_CONFIRMACAO` | Contestação por qualquer parte | `EM_ANALISE` |
| `EM_ANALISE` | Decisão: favor realizado | `REALIZADA` |
| `EM_ANALISE` | Decisão: favor não realizado | `NAO_REALIZADA` |
| `EM_ANALISE` | Decisão: inconclusiva | `INCONCLUSIVA` |
| `REALIZADA` | Ambas as avaliações | `ENCERRADA` |
| `NAO_REALIZADA` | — | Terminal; ticket devolvido |
| `INCONCLUSIVA` | — | Terminal; ticket devolvido |

## 10.2 Confirmação da realização

A regra que rege o encerramento é simples:

> **O silêncio confirma. Somente a contestação ativa impede o encerramento.**

| Situação ao final da janela | Resultado |
|---|---|
| Ambas as partes confirmam | `REALIZADA` |
| Uma confirma, a outra silencia | `REALIZADA` por confirmação tácita |
| Ambas silenciam | `REALIZADA` por confirmação tácita |
| Qualquer parte contesta | `EM_ANALISE` |

A confirmação bilateral encerra a janela imediatamente, sem aguardar o prazo (**RF-SOL-09**).

**Não há avaliação em solicitação `NAO_REALIZADA` ou `INCONCLUSIVA`.** O favor não aconteceu, e avaliar uma experiência inexistente distorceria a reputação de ambos. O registro do episódio ocorre por ocorrência, não por avaliação.

---

# 11. SEG — Segurança em atendimento presencial

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-SEG-01 | O usuário cadastra um contato de confiança com nome e meio de contato. | 🟢 | `02 §8.2` |
| RF-SEG-02 | O sistema comunica ao contato de confiança o local, o horário e a identificação da contraparte quando um atendimento presencial é agendado. | 🟢 | `02 §8.2` |
| RF-SEG-03 | O sistema exibe o termo de ciência de riscos no aceite do atendimento presencial. | 🟢 | `02 §8.2` |
| RF-SEG-04 | O sistema registra o aceite do termo com data, hora e **versão do termo aceita**. | 🟢 | `—` |
| RF-SEG-05 | Ambas as partes registram o **check-in** no início do atendimento presencial. | 🟢 | `02 §8.1` |
| RF-SEG-06 | Ambas as partes registram o **check-out** ao término do atendimento. | 🟢 | `02 §8.1` |
| RF-SEG-07 | O sistema armazena data e hora de cada registro de comparecimento. | 🟢 | `02 §8.1` |
| RF-SEG-08 | O acionamento de emergência fica disponível entre o check-in e o check-out. | 🟢 | `02 §8.2` |
| RF-SEG-09 | O registro de comparecimento compõe a evidência utilizada na confirmação de realização e na análise de ocorrências. | 🟢 | `02 §8.1` |
| RF-SEG-10 | O sistema notifica o contato de confiança caso o check-out não ocorra em intervalo razoável após o término previsto. | 🟢 | `—` |
| RF-SEG-11 | **Ambas** as partes aceitam o termo, e o aceite é registrado por atendimento, não por conta. | 🟢 | `02 §8.2` |
| RF-SEG-12 | O sistema impede solicitar e agendar favor presencial sem o aceite do termo vigente. | 🟢 | `02 §8.2` |

> **RF-SEG-04** existe por necessidade probatória: um termo de ciência sem registro de qual versão foi aceita não sustenta a finalidade para a qual existe.
>
> **RF-SEG-10** é a consequência natural do check-out. Um registro de saída que ninguém observa não protege ninguém — a ausência do check-out é justamente o sinal relevante.

---

# 12. CAN — Cancelamentos e ocorrências

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-CAN-01 | O ajudante cancela uma prestação agendada mediante justificativa obrigatória. | 🟢 | `02 §9.1` |
| RF-CAN-02 | O sistema notifica o solicitante sobre o cancelamento e sua justificativa. | 🟢 | `02 §9.1` |
| RF-CAN-03 | O solicitante manifesta aprovação ou reprovação da justificativa. | 🟢 | `02 §9.1` |
| RF-CAN-04 | A manifestação do solicitante não impede a devolução do ticket, servindo como registro. | 🟢 | `02 §9.1` |
| RF-CAN-05 | O cancelamento devolve o ticket ao estado `DISPONIVEL`. | 🟢 | `02 §9.1` |
| RF-CAN-06 | O sistema registra a ocorrência no histórico do usuário que cancelou. | 🟢 | `02 §9.1` |
| RF-CAN-07 | O solicitante cancela a solicitação a qualquer momento; o sistema não bloqueia o cancelamento. | 🟢 | `02 §9.2` |
| RF-CAN-08 | O cancelamento com **24 horas ou mais** de antecedência não gera ocorrência nem penalidade. | 🟢 | `02 §9.2` |
| RF-CAN-15 | O cancelamento com menos de 24 horas registra ocorrência contra quem cancelou. | 🟢 | `02 §9.2` |
| RF-CAN-16 | O sistema informa ao solicitante, antes de confirmar o cancelamento, se ele gerará ocorrência. | 🟢 | `—` |
| RF-CAN-17 | A justificativa é obrigatória no cancelamento pelo ajudante e opcional no cancelamento pelo solicitante. | 🟢 | `02 §9.2` |
| RF-CAN-18 | O solicitante cancela também a solicitação ainda não confirmada, sem reserva de ticket a desfazer. | 🟢 | `02 §9.2` |
| RF-CAN-09 | O sistema mantém contador de cancelamentos por usuário e por papel. | 🟢 | `03 §4.10` |
| RF-CAN-10 | Ambas as partes registram ocorrência relacionada à prestação do favor. | 🟢 | `02 §10` |
| RF-CAN-11 | As ocorrências são encaminhadas à fila de análise da Operação. | 🟢 | `03 §4.10` |
| RF-CAN-12 | O sistema aplica penalidades automáticas por cancelamentos recorrentes. | ⚪ | `02 §9.1` |
| RF-CAN-13 | As partes anexam evidências a uma disputa. | ⚪ | `02 §14` |
| RF-CAN-14 | O sistema oferece processo formal de disputa com decisão registrada. | ⚪ | `02 §14` |

---

# 13. AVA — Avaliações

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-AVA-01 | O sistema libera a avaliação somente após a confirmação da realização. | 🟢 | `02 §11.1` |
| RF-AVA-02 | A avaliação utiliza escala de 0 a 7 estrelas. | 🟢 | `02 §11.3` |
| RF-AVA-03 | O solicitante avalia o ajudante nos critérios Comunicação, Qualidade e Satisfação. | 🟢 | `02 §11.3` |
| RF-AVA-04 | O ajudante avalia o solicitante nos critérios Cordialidade, Comunicação e Condições. | 🟢 | `02 §11.3` |
| RF-AVA-05 | O critério Qualidade exige a seleção de motivos pré-determinados. | 🟢 | `02 §11.3` |
| RF-AVA-06 | As notas por critério são obrigatórias e o comentário em texto é opcional. | 🟢 | `02 §11.3` |
| RF-AVA-07 | A avaliação é imutável após o envio. | 🟢 | `02 §11.1` |
| RF-AVA-08 | A avaliação é pública. | 🟢 | `02 §11.1` |
| RF-AVA-09 | A avaliação é vinculada ao favor específico que a originou. | 🟢 | `02 §11.2` |
| RF-AVA-10 | O ciclo do favor só é encerrado quando ambas as avaliações forem registradas. | 🟢 | `02 §11.4` |
| RF-AVA-11 | O sistema impede o envio de novas propostas por usuário com avaliação pendente além do prazo. | 🟢 | `02 §11.4` |
| RF-AVA-12 | O sistema notifica o usuário sobre avaliação pendente antes de aplicar a restrição. | 🟢 | `—` |
| RF-AVA-13 | A avaliação não altera o nível de proficiência declarado. | 🟢 | `02 §11.2` |

---

# 14. REP — Reputação

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-REP-01 | O perfil exibe a média geral do usuário como Ajudante. | 🟢 | `03 §4.12` |
| RF-REP-02 | O perfil exibe a média geral do usuário como Solicitante. | 🟢 | `03 §4.12` |
| RF-REP-03 | O perfil exibe as médias individuais por critério. | 🟢 | `02 §11.3` |
| RF-REP-04 | O perfil exibe a reputação segmentada por proficiência. | 🟢 | `02 §11.2` |
| RF-REP-05 | O perfil exibe a quantidade de favores prestados e recebidos. | 🟢 | `03 §4.12` |
| RF-REP-06 | O perfil exibe o tempo de participação na plataforma. | 🟢 | `03 §4.12` |
| RF-REP-07 | O perfil exibe o nível declarado ao lado da reputação obtida na mesma proficiência. | 🟢 | `02 §6.3` |
| RF-REP-08 | O perfil indica quando ainda não há avaliações suficientes para compor média. | 🟢 | `—` |
| RF-REP-09 | O sistema aplica limites de oferta conforme o nível de confiança do usuário. | ⚪ | `02 §13` |

---

# 15. BLO — Bloqueio e denúncia

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-BLO-01 | O usuário bloqueia outro usuário. | 🟢 | `02 §18.2` |
| RF-BLO-02 | O bloqueio oculta mutuamente perfis e favores das partes. | 🟢 | `02 §18.2` |
| RF-BLO-03 | O bloqueio impede o envio de propostas entre as partes. | 🟢 | `02 §18.2` |
| RF-BLO-04 | O usuário desfaz um bloqueio. | 🟢 | `—` |
| RF-BLO-05 | O bloqueio não constitui avaliação negativa nem ocorrência. | 🟢 | `02 §18.2` |
| RF-BLO-06 | O bloqueio **não interrompe** solicitação já agendada nem invalida tickets já transferidos entre as partes. | 🟢 | `—` |
| RF-BLO-07 | O usuário denuncia um usuário ou um favor, informando o motivo. | 🟢 | `02 §18.2` |
| RF-BLO-08 | As denúncias são encaminhadas à fila de análise da Operação. | 🟢 | `02 §18.2` |
| RF-BLO-09 | O usuário bloqueia endereços ou regiões. | ⚪ | `03 §4.13` |
| RF-BLO-10 | A recusa decorrente de bloqueio não revela a existência do bloqueio nem quem o fez. | 🟢 | `02 §18.2` |
| RF-BLO-11 | A denúncia não bloqueia automaticamente o alvo. | 🟢 | `02 §18.2` |

> **RF-BLO-06** resolve um conflito real entre duas regras: o bloqueio é direito do usuário, mas o ticket já emitido não pode desaparecer arbitrariamente (`02 §20`, item 3). A relação já contratada prossegue; o bloqueio impede apenas relações novas.

---

# 16. NOT — Notificações

| ID | Evento notificado | Destinatário | Prio |
|---|---|---|---|
| RF-NOT-01 | Proposta recebida | Destinatário | 🟢 |
| RF-NOT-02 | Proposta aceita, recusada ou retirada | Remetente | 🟢 |
| RF-NOT-03 | Proposta prestes a expirar | Ambos | 🟢 |
| RF-NOT-04 | Nova mensagem no chat de negociação | Contraparte | 🟢 |
| RF-NOT-05 | Solicitação recebida | Ajudante | 🟢 |
| RF-NOT-06 | Solicitação agendada ou recusada | Solicitante | 🟢 |
| RF-NOT-07 | Cancelamento de prestação | Contraparte | 🟢 |
| RF-NOT-08 | Atendimento presencial agendado | Contato de confiança | 🟢 |
| RF-NOT-09 | Lembrete de check-in | Ambos | 🟢 |
| RF-NOT-10 | Lembrete de check-out | Ambos | 🟢 |
| RF-NOT-11 | Check-out não registrado após o término previsto | Contato de confiança | 🟢 |
| RF-NOT-12 | Confirmação de realização pendente | Ambos | 🟢 |
| RF-NOT-13 | Avaliação pendente | Parte em atraso | 🟢 |
| RF-NOT-14 | Restrição por avaliação pendente aplicada | Usuário restrito | 🟢 |
| RF-NOT-15 | Ticket próximo do vencimento | Proprietário | 🟢 |
| RF-NOT-16 | Ticket expirado | Proprietário | 🟢 |
| RF-NOT-18 | Proficiência aguardada passa a ter ticket disponível na região | Usuário na fila de aviso | 🟢 |

**RF-NOT-17** 🟢 — O sistema não emite notificações de marketing, engajamento ou recomendação no MVP.

---

# 17. ADM — Administração e curadoria

> Este módulo não estava previsto nos documentos anteriores. Ele decorre de decisões já tomadas: catálogo mantido pela plataforma, curadoria de categorias, análise manual de ocorrências e base restrita no lançamento. Sem ele, essas decisões não são executáveis.

| ID | Requisito | Prio | Origem |
|---|---|---|---|
| RF-ADM-01 | A Operação mantém o catálogo de categorias, subcategorias e proficiências. | 🟢 | `02 §3.1` |
| RF-ADM-02 | A Operação define quais categorias ficam restritas pela curadoria. | 🟢 | `02 §8.3` |
| RF-ADM-03 | A Operação consulta e trata a fila de ocorrências. | 🟢 | `03 §4.10` |
| RF-ADM-04 | A Operação consulta e trata a fila de denúncias. | 🟢 | `02 §18.2` |
| RF-ADM-05 | A Operação consulta o histórico completo de um ticket, incluindo cadeia de propriedade. | 🟢 | `02 §20` |
| RF-ADM-06 | A Operação suspende um usuário ou um favor, com registro de motivo. | 🟢 | `—` |
| RF-ADM-07 | A Operação publica e versiona o termo de ciência de riscos. | 🟢 | `—` |
| RF-ADM-08 | A Operação administra convites de acesso durante a fase de base restrita. | 🟢 | `—` |
| RF-ADM-09 | A Operação acompanha as métricas definidas em `03 §7`. | 🟢 | `03 §7` |
| RF-ADM-10 | Toda ação da Operação é registrada em trilha de auditoria com autor, data e motivo. | 🟢 | `—` |
| RF-ADM-11 | A Operação consulta a fila de **chamados** abertos por contestação de realização. | 🟢 | `02 §8.5` |
| RF-ADM-12 | O chamado apresenta à Operação a manifestação de cada parte, o registro de comparecimento e o histórico de ocorrências de ambas. | 🟢 | `02 §8.5` |
| RF-ADM-13 | A Operação decide o chamado entre **realizado**, **não realizado** e **inconclusivo**, registrando o fundamento. | 🟢 | `02 §8.5` |
| RF-ADM-14 | O sistema aplica automaticamente os efeitos da decisão sobre o ticket e sobre os registros de ocorrência. | 🟢 | `02 §8.5` |
| RF-ADM-15 | A Operação mantém os **termos populares** associados a cada proficiência. | 🟢 | `02 §3.4` |
| RF-ADM-16 | A Operação consulta as necessidades não atendidas, agregadas por proficiência e região. | 🟢 | `02 §3.5` |
| RF-ADM-17 | A Operação identifica buscas que não reconheceram nenhuma proficiência, para corrigir o vocabulário. | 🟢 | `02 §3.4` |
| RF-ADM-18 | A Operação consulta a quantidade de usuários aguardando aviso por proficiência e região. | 🟢 | `02 §3.5` |
| RF-ADM-19 | Existe no máximo um chamado por solicitação. | 🟢 | `02 §8.5` |
| RF-ADM-20 | A suspensão de um favor impede novas emissões e **não** invalida tickets já emitidos. | 🟢 | `02 §20.3` |
| RF-ADM-21 | A Operação decide o chamado sem depender do esgotamento do prazo de manifestação. | 🟢 | `02 §8.5` |

> **RF-ADM-17 é o mecanismo de correção da busca.** Uma necessidade que não reconhece proficiência alguma é falha de vocabulário, não ausência de oferta — e as duas situações exigem respostas opostas: incluir um termo, ou recrutar um ajudante. Sem distinguir uma da outra, a análise de cobertura (RF-ADM-16) mede a coisa errada.

> **Nota terminológica:** *ticket* designa exclusivamente o direito de receber um favor. O registro de atendimento da Operação denomina-se **chamado**. A distinção é obrigatória em toda a interface e no modelo de dados — usar "ticket" para suporte tornaria ambíguo o conceito central do produto.

---

# 18. Requisitos derivados — decisões implícitas

Os requisitos abaixo não decorrem de regra explícita nos documentos 02 e 03. São consequências necessárias das decisões já tomadas, mas representam escolhas que ainda não foram deliberadas. **Cada um deles é uma decisão pendente disfarçada de requisito.**

| Requisito | Decisão implícita que precisa ser confirmada |
|---|---|
| RF-PRO-07 | O que acontece ao remover proficiência com favores ativos? Optamos por impedir a remoção. |
| RF-FAV-14 / RF-TIC-11 | Nível de detalhe do histórico exposto ao usuário. |
| RF-NEG-09 | Revisão da oferta invalida aceites anteriores — evita aceite sobre composição diferente da exibida. |
| RF-SOL-23 | Ajudante não pode ter dois atendimentos sobrepostos. Não havia regra sobre agenda. |
| RF-FAV-20 | Quantidade máxima integra as condições congeladas do favor. |
| RF-TIC-15 | Validade suspensa enquanto o ticket estiver em análise. |
| RF-SOL-20 | Avaliação não é liberada em solicitação não realizada ou inconclusiva. |
| RF-BUS-18 | A proficiência reconhecida é exibida ao usuário, tornando a interpretação auditável. |
| RF-BUS-19 | O usuário pode descartar uma proficiência reconhecida indevidamente. |
| RF-BUS-24 | Ampliação da área de busca oferecida quando não há resultado na região. |
| RF-SEG-04 | Versionamento do termo de ciência, por necessidade probatória. |
| RF-SEG-10 | Ausência de check-out dispara alerta ao contato de confiança. |
| RF-BLO-06 | Bloqueio não desfaz relação já agendada nem invalida tickets transferidos. |
| RF-CAD-10 | CPF imutável após o cadastro. |
| RF-CAD-16 | Desativação imediata quando não há tickets em circulação. |
| RF-ADM-01 a 10 | Existência de um módulo administrativo e de trilha de auditoria. |

---

# 19. Lacunas identificadas

Questões que a redação deste documento expôs e que **não podem ser resolvidas aqui**, por serem decisões de negócio:

## 19.1 Lacunas resolvidas

| # | Lacuna | Resolução |
|---|---|---|
| L1 | Tickets consumidos contam para a quantidade máxima? | **Não.** O limite é de circulação simultânea e a vaga é liberada. `02 §5.1`, RF-FAV-16 e RF-FAV-17 |
| L3 | Quem arbitra a divergência na confirmação? | **A Operação, por chamado**, com manifestação de ambas as partes e três desfechos possíveis. `02 §8.5`, RF-SOL-12 a RF-SOL-19 |
| L7 | O que ocorre se ninguém confirmar a realização? | **Confirmação tácita.** O silêncio confirma; apenas a contestação ativa impede o encerramento. `02 §8.4`, RF-SOL-10 |
| L8 | Favor congelado pode ter a quantidade máxima aumentada? | **Não.** A quantidade máxima integra as condições congeladas. RF-FAV-20 |

## 19.2 Parâmetros temporais — definidos na Fase 0

| # | Parâmetro | Valor | Regra |
|---|---|---|---|
| L2 | Antecedência para cancelamento pelo solicitante | **24 h** — não bloqueia, registra ocorrência | `02 §9.2` |
| L4 | Prazo para avaliar antes da restrição | **7 dias** | `02 §11.4` |
| L5 | Validade da proposta de troca | **7 dias**, fixa pela plataforma | `02 §5.4` |
| L6 | Prazo máximo da conta em Encerrando | **Sem prazo**; validade do ticket limitada a 1 ano | `02 §2.2` |
| L9 | Duração da janela de contestação | **72 h** | `02 §8.4` |
| L10 | Prazo de manifestação das partes na análise | **5 dias** | `02 §8.5` |

Todos são parâmetros versionados com vigência, nunca constantes de código (`05 §9`).

## 19.3 Lacuna remanescente

| # | Lacuna | Por que importa |
|---|---|---|
| L11 | **Disputa por qualidade do favor prestado** | A análise por chamado (`02 §8.5`) resolve apenas *se o favor aconteceu*. Não resolve o caso em que ambas as partes concordam que houve prestação, mas divergem sobre o resultado. Hoje esse conflito só se expressa na avaliação, que é imutável e pública. |

> **Nenhuma lacuna bloqueia a modelagem conceitual.** L11 é decisão de produto e pode ser tratada após o MVP; as demais são parâmetros.

---

# 20. Regras transversais

| ID | Requisito | Prio |
|---|---|---|
| RF-GER-01 | Toda transição de estado é registrada com autor, data, hora e evento originador. | 🟢 |
| RF-GER-02 | Transições de estado são idempotentes: a repetição do mesmo comando não produz efeito adicional. | 🟢 |
| RF-GER-03 | Datas e horários são tratados no fuso do usuário e armazenados em formato absoluto. | 🟢 |
| RF-GER-04 | Nenhum dado de contato trafega em URL ou parâmetro de consulta. | 🟢 |
| RF-GER-05 | Registros que compõem histórico de confiança — avaliações, ocorrências, cancelamentos, comparecimentos — não são excluídos, conforme `02 §20`, item 6. | 🟢 |
| RF-GER-06 | A exclusão de ticket expirado remove o ticket da carteira, mas preserva seu registro histórico. | 🟢 |

> **RF-GER-05** e **RF-GER-06** convivem: o usuário exclui da sua vista, o sistema preserva para auditoria. A distinção precisa ser explícita na política de privacidade.

---

# 21. Fora do escopo deste documento

| Tema | Documento |
|---|---|
| Requisitos não funcionais (desempenho, disponibilidade, segurança técnica) | `05 - Arquitetura Conceitual.md` |
| Modelo de dados e entidades | `05 - Arquitetura Conceitual.md` |
| Integrações externas | `05 - Arquitetura Conceitual.md` |
| Interface, telas e fluxos de navegação | Documento de design |
| Sequenciamento de entrega | `06 - Roadmap do Produto.md` |

---

# 22. Status do documento

| Item | Status |
|---|---|
| Convenções e rastreabilidade | ✅ Definidas |
| Atores | ✅ Definidos |
| CAD — Cadastro e conta | ✅ Especificado |
| PRO — Proficiências | ✅ Especificado |
| FAV — Favores | ✅ Especificado |
| TIC — Tickets e carteira | ✅ Especificado |
| BUS — Descoberta e busca por necessidade | ✅ Especificado |
| NEG — Negociação e troca | ✅ Especificado |
| SOL — Solicitação e consumo | ✅ Especificado |
| SEG — Segurança presencial | ✅ Especificado |
| CAN — Cancelamentos e ocorrências | ✅ Especificado |
| AVA — Avaliações | ✅ Especificado |
| REP — Reputação | ✅ Especificado |
| BLO — Bloqueio e denúncia | ✅ Especificado |
| NOT — Notificações | ✅ Especificado |
| ADM — Administração e curadoria | ✅ Especificado |
| Lacunas bloqueantes | ✅ Resolvidas — seção 19.1 |
| Parâmetros temporais | 🟡 6 valores a definir — seção 19.2 |
| Disputa por qualidade | 🟡 Pendente — seção 19.3 |
| Requisitos derivados | 🟡 Pendentes de confirmação — seção 18 |
| Arquitetura conceitual | 🟡 Próxima etapa (`05`) |

---

**Versão:** 1.6
**Produto:** Ajude.me
**Documento:** Requisitos Funcionais

**Histórico de versões**

| Versão | Alterações |
|---|---|
| 1.0 | Especificação inicial dos requisitos funcionais |
| 1.6 | Novos RF-SEG-11, RF-SEG-12, RF-BLO-10, RF-BLO-11 e RF-ADM-19 a RF-ADM-21, decorrentes de `02 §8.2`, `§18.2` e `§20.3` na versão 2.6. |
| 1.5 | Novos RF-SOL-24 a RF-SOL-28 e RF-CAN-17 e RF-CAN-18, decorrentes de `02 §8` e `§9.2` na versão 2.5. A tabela de §10.1 passa a prever `SOLICITADA → CANCELADA_SOLICITANTE`, exigido por RF-CAN-07: o solicitante cancela a qualquer momento, e isso inclui antes da confirmação do ajudante. |
| 1.4 | Novos requisitos RF-NEG-19, RF-NEG-20 e RF-NEG-21, decorrentes de `02 §5.4` na versão 2.4: trocabilidade vale para os dois lados da proposta, contrapartida pode ser vazia, e o aceite após revisão cabe à outra parte. |
| 1.3 | **Fase 0 fechada.** Parâmetros temporais com valor (§19.2). Cancelamento pelo solicitante deixa de ser bloqueado e passa a registrar ocorrência quando em cima da hora — RF-CAN-07, RF-CAN-08, RF-CAN-15 e RF-CAN-16. Validade do ticket limitada a 1 ano e nível congelado no favor — RF-FAV-21 e RF-FAV-22. |
| 1.2 | Busca por necessidade — RF-BUS-16 a RF-BUS-30 e nova seção 8.1. Manutenção de termos populares e análise de cobertura no módulo ADM — RF-ADM-15 a RF-ADM-18. Nova notificação RF-NOT-18. |
| 1.1 | Resolução das lacunas L1, L3, L7 e L8. Novo estado de ticket `EM_ANALISE`. Confirmação tácita e janela de contestação (RF-SOL-08 a RF-SOL-20). Análise por chamado (RF-ADM-11 a RF-ADM-14). Limite de circulação com liberação de vaga (RF-FAV-16 a RF-FAV-20). |
