# Ajude.me — Conceito e Regras de Negócio

> Documento que define os principais conceitos e regras de funcionamento do Ajude.me. As regras classificadas como pendentes representam decisões operacionais que ainda deverão ser detalhadas sem alterar o conceito definido.

---

## 1. Conceitos fundamentais

| Conceito | Definição |
|---|---|
| **Usuário** | Pessoa cadastrada na plataforma. |
| **Ajudante** | Usuário que oferece um favor. |
| **Solicitante** | Usuário que busca ou solicita ajuda. |
| **Favor** | Ajuda que um usuário se dispõe a prestar. |
| **Ticket** | Representação do direito de receber determinado favor. |
| **Carteira** | Local onde ficam os tickets pertencentes ao usuário. |
| **Proposta** | Oferta formal de troca enviada por um usuário a outro, contendo o que se deseja receber e o que se oferece em contrapartida. |
| **Troca** | Operação na qual tickets são transferidos entre usuários. |
| **Contrapartida** | Conjunto de tickets oferecidos em uma proposta em retribuição ao que se deseja receber. |
| **Descritor** | Par formado pelo tempo estimado e pelo nível de proficiência de um favor, utilizado como referência de equivalência nas trocas. |
| **Ocorrência** | Registro formal de um evento anormal relacionado à prestação de um favor. |
| **Consumo** | Utilização de um ticket para solicitar o favor representado por ele. |
| **Proficiência** | Habilidade declarada pelo usuário, associada a um nível de domínio. |
| **Necessidade** | Aquilo que o usuário precisa resolver, expresso em linguagem natural. |
| **Termo popular** | Palavra ou expressão do cotidiano associada a uma proficiência, usada para reconhecer a necessidade. |
| **Certificação** | Evidência documental apresentada pelo usuário para fortalecer a confiabilidade de uma proficiência. |
| **Reputação** | Indicador construído a partir das experiências e avaliações de outros usuários. |

### Regra fundamental

Todo usuário pode atuar simultaneamente como **Ajudante** e **Solicitante**.

O usuário não possui um papel fixo dentro da plataforma. Ele pode oferecer ajuda em determinado momento e solicitar ajuda em outro.

---

# 2. Cadastro e identidade

## 2.1 Perfil

Cada pessoa física poderá possuir **apenas um perfil associado ao seu CPF**.

O CPF será obrigatório para o cadastro de pessoa física.

O CNPJ não será obrigatório para a criação do perfil pessoal.

A identidade do usuário deverá ser validada para aumentar a confiabilidade e a segurança da plataforma. O CPF não será utilizado somente para impedir duplicidade.

### Representação empresarial

Um usuário pessoa física poderá informar que representa uma empresa/CNPJ.

A empresa será tratada como uma informação adicional relacionada ao perfil do usuário, e não como um segundo perfil independente no modelo inicial.

Exemplo conceitual:

> **Usuário:** João Silva  
> **Identidade:** Verificada  
> **Representação:** Empresa XYZ Ltda.

O foco principal do produto continuará sendo a participação de pessoas físicas.

---

## 2.2 Desativação da conta

O usuário poderá desativar sua conta.

A desativação deverá impedir novas atividades incompatíveis com uma conta inativa, como a criação de novos favores ou emissão de novos tickets.

Tickets que já estejam em circulação não poderão simplesmente desaparecer, pois representam direitos adquiridos por outros usuários.

### Tickets em circulação durante a desativação

Quando o ajudante desativar sua conta e possuir tickets em circulação, deverá existir um mecanismo de proteção aos proprietários desses tickets.

As alternativas conceituais definidas são:

- concessão de uma bonificação/compensação aos proprietários; ou
- concessão de um prazo para que os tickets sejam consumidos.

### Regra do MVP

Sem dinheiro no MVP, não existe compensação possível. A proteção é o **prazo**, e ela é integral:

- A conta entra em **Encerrando** e não realiza novas atividades.
- Continua obrigada a atender solicitações dentro das condições dos tickets emitidos.
- Encerra definitivamente quando o último ticket for consumido ou expirar.

**Não há prazo máximo.** Encurtá-lo destruiria tickets válidos, contra o princípio 3 da seção 20. O que mantém isso tolerável é a **validade máxima de 1 ano** para qualquer ticket (seção 21).

A bonificação volta a ser possível quando existir mecanismo de valor, junto do modelo de dinheiro (seção 15).

---

# 3. Habilidades, proficiências e certificações

## 3.1 Habilidades e proficiências

O usuário poderá adicionar e remover habilidades de seu perfil.

As proficiências disponíveis serão previamente cadastradas na plataforma e poderão possuir diferentes níveis de domínio.

O usuário escolherá suas proficiências e o nível correspondente.

Um usuário não poderá oferecer um favor baseado em uma habilidade que não esteja cadastrada em seu perfil.

---

## 3.2 Certificações

O usuário poderá cadastrar certificados relacionados às suas proficiências.

A certificação tem como objetivo fornecer evidência adicional sobre a capacidade declarada pelo usuário.

Inicialmente, poderão existir dois estados:

- **Certificado não verificado**
- **Certificado verificado**

Sempre que tecnicamente possível, o Ajude.me deverá buscar mecanismos para verificar a autenticidade do certificado.

**Status:** 🟡 Método operacional de verificação pendente de definição.

---

## 3.3 Proficiência não é reputação

A proficiência declarada não será automaticamente alterada pelas avaliações recebidas.

A plataforma deverá manter separados:

**Proficiência**
> O que o usuário afirma saber fazer.

**Certificação**
> Evidência documental relacionada à proficiência.

**Reputação**
> Evidência construída através das experiências reais de outros usuários.

Certificados não garantem, por si só, competência prática. Da mesma forma, uma boa reputação não deverá alterar automaticamente o nível de proficiência declarado.

A reputação poderá, entretanto, ser utilizada para identificar ajudantes com histórico positivo.

---

## 3.4 Busca por necessidade

O Ajude.me **não possui pedido de ajuda como entidade**. Ninguém publica uma demanda à espera de resposta.

Em vez disso, o usuário descreve sua necessidade em linguagem natural, e o sistema apresenta os favores das proficiências relacionadas.

> **O usuário informa o sintoma. O sistema encontra a competência.**

### Exemplo

> O usuário digita: *"trocar a resistência do chuveiro"*
> O sistema reconhece a proficiência: **Elétrica residencial**
> O sistema apresenta: os favores ativos daquela proficiência, com tickets disponíveis, próximos ao usuário

Observe que o usuário **não sabe** que precisa de um eletricista — ele sabe que o chuveiro não esquenta. Traduzir uma coisa na outra é o trabalho que o sistema realiza, e é o que distingue esta busca de uma busca por texto.

### Termos populares

Cada proficiência do catálogo poderá reunir os termos do cotidiano que a ela remetem.

> **Elétrica residencial** → chuveiro, disjuntor, tomada queimada, curto, lâmpada, fiação

A manutenção desses termos cabe à Operação, junto com o próprio catálogo (seção 3.1). Uma busca que não encontra o que deveria é uma falha de vocabulário, corrigível pela inclusão do termo.

Essa abordagem foi escolhida por ser auditável, corrigível de imediato e viável em qualquer aparelho. Mecanismos de reconhecimento semântico poderão ampliar o alcance posteriormente, sem alterar o conceito.

**Status:** ✅ Definido. O reconhecimento por termos populares curados foi confirmado na Fase 0 (`05 §15`, decisão A7).

---

## 3.5 Necessidade não atendida

Uma busca que não encontra nenhum favor correspondente **não é um resultado vazio**. É informação.

O usuário poderá pedir para ser avisado quando alguém passar a oferecer ajuda naquela proficiência.

Para a plataforma, o conjunto dessas buscas revela **quais proficiências faltam em cada região** — dado que orienta a formação da rede e que não existiria se a necessidade não fosse expressa.

> Uma rede de trocas depende de densidade e de complementaridade. A necessidade não atendida é o mapa do que falta.

O tratamento desse dado observa as regras de privacidade da seção 18.

---

# 4. Favores

Um favor representa uma ajuda que um usuário se dispõe a prestar.

## 4.1 Criação de um favor

Somente um usuário que possua pelo menos uma proficiência compatível poderá criar um favor.

Todo favor deverá estar associado a pelo menos uma das proficiências cadastradas no perfil do ajudante.

Não será permitido oferecer um favor baseado em uma habilidade que não esteja cadastrada como proficiência.

---

## 4.2 Características do favor

Um favor poderá possuir, entre outras informações:

- Categoria;
- Subcategoria;
- Proficiência relacionada;
- Validade do ticket, limitada a **1 ano** (seção 21);
- Tempo estimado para realização;
- Dias disponíveis;
- Horários disponíveis;
- Localização ou área de atendimento;
- Prazo mínimo para prestação;
- Prazo máximo para prestação;
- Descrição;
- Exceções;
- Avisos;
- Condições;
- Quantidade máxima de tickets;
- Condição de transferência dos tickets.

As condições do favor deverão estar disponíveis ao usuário antes da negociação ou aquisição do ticket.

---

## 4.3 Desativação do favor

O ajudante poderá desativar um favor.

A desativação impedirá a geração de novos tickets para aquele favor.

Entretanto, tickets que já tenham sido emitidos deverão continuar válidos e o favor correspondente deverá continuar sendo prestado dentro das condições estabelecidas.

Portanto:

> **Desativar um favor não cancela automaticamente os tickets já emitidos.**

---

# 5. Tickets

O ticket é o elemento central da operação do Ajude.me.

Ele representa o direito de receber determinado favor.

## 5.1 Relação entre favor e ticket

Um favor poderá possuir uma quantidade máxima de tickets.

O ajudante definirá:

- quantidade máxima de tickets que aquele favor poderá gerar;
- quantidade de tickets que deseja disponibilizar naquele momento.

Os tickets não precisam ser emitidos todos de uma vez.

### A quantidade máxima é de circulação, não vitalícia

A quantidade máxima limita quantos tickets daquele favor podem estar **em circulação simultaneamente**, e não quantos poderão ser emitidos ao longo da existência do favor.

| Situação do ticket | Ocupa vaga |
|---|---|
| Disponível | Sim |
| Em negociação | Sim |
| Reservado | Sim |
| Em análise | Sim |
| Consumido | Não |
| Expirado | Não |
| Descartado | Não |

Um ticket consumido, expirado ou descartado **libera a vaga correspondente**.

O ajudante poderá emitir novos tickets a qualquer momento — durante ou após a circulação dos anteriores — desde que o total em circulação não ultrapasse o limite definido e o favor permaneça ativo.

O limite funciona, portanto, como controle de **carga de trabalho simultânea**, e não como estoque finito. Ele existe para impedir que o ajudante assuma mais compromissos do que consegue honrar.

### Exemplo

> Favor: Consulta de informática  
> Limite máximo: 5 tickets  
> Tickets emitidos: 2  
> Tickets ainda disponíveis para emissão: 3

### Exemplo com liberação de vaga

> Favor: Consulta de informática
> Limite máximo: 5 tickets
> Tickets emitidos: 5
> Tickets consumidos: 2
> Tickets em circulação: 3
> Tickets ainda disponíveis para emissão: 2

---

## 5.2 Unidade de utilização

A regra básica será:

> **1 ticket = 1 realização de um favor.**

Um ticket não poderá representar múltiplas realizações independentes do mesmo favor.

---

## 5.3 Transferência

Um ticket poderá circular entre usuários sem necessariamente ser consumido.

O criador do favor poderá definir se os tickets daquele favor serão:

- **Trocáveis**;
- **Não trocáveis**;
- ou, conforme evolução das regras, sujeitos a condições específicas de transferência.

Essa configuração será definida no momento da criação do favor.

---

## 5.4 Negociação e troca

A transferência de tickets entre usuários ocorre por meio de uma **proposta**.

### Fluxo

1. O interessado visualiza a carteira ou a vitrine de tickets de outro usuário.
2. Seleciona o ticket desejado e a quantidade, compondo uma proposta.
3. Monta a **contrapartida**, escolhendo tickets da própria carteira.
4. Envia a proposta.
5. O destinatário poderá **aceitar**, **recusar** ou **abrir negociação**.
6. A negociação ocorre em um **chat vinculado à proposta**, no qual a oferta poderá ser revisada por qualquer uma das partes.
7. Aceita a proposta, os tickets são transferidos entre as carteiras.

### Regras

- Tickets envolvidos em uma proposta ficam **bloqueados** e não poderão participar simultaneamente de outra proposta.
- A proposta possuirá prazo de validade. Esgotado o prazo, os tickets são liberados.
- Tickets marcados como **não trocáveis** não circulam por proposta — nem como contrapartida, nem como item solicitado. A marca governa a transferência do ticket (seção 5.3), e não o lado em que ele aparece na oferta.
- A proposta exige ao menos um ticket solicitado. A **contrapartida pode ser vazia**: pedir sem ter o que oferecer é o caso de quem ainda não entrou na rede de troca, e é justamente para ele que a plataforma existe (seção 1).
- A transferência é **atômica**: ou todos os tickets envolvidos mudam de carteira, ou nenhum muda.
- Um ticket cuja validade seja atingida durante a negociação expira, e a proposta correspondente é cancelada.
- Revisada a oferta, a decisão passa à **outra parte**: quem revisou não aceita a própria revisão. É o que impede que uma revisão seguida de aceite imediato transforme a negociação em imposição.

### Estados da proposta

| Estado | Significado |
|---|---|
| `RASCUNHO` | Em composição, ainda não enviada |
| `ENVIADA` | Aguardando resposta do destinatário |
| `EM_NEGOCIACAO` | Chat aberto, oferta pode ser revisada |
| `ACEITA` | Tickets transferidos |
| `RECUSADA` | Encerrada pelo destinatário |
| `RETIRADA` | Encerrada pelo remetente |
| `EXPIRADA` | Prazo de resposta esgotado |

### Participação de dinheiro

A eventual composição de uma contrapartida com dinheiro depende do modelo operacional financeiro, ainda pendente de definição (seção 15).

Enquanto esse modelo não existir, a contrapartida é composta **exclusivamente por tickets**.

---

# 6. Valor e equivalência do favor

> **Revisão:** esta seção substitui integralmente o modelo anterior, baseado em valor por hora.

## 6.1 Por que o tempo não basta

O tempo mede esforço, não conhecimento.

O que caracteriza um bom ajudante não é saber apertar um parafuso, mas saber **qual** parafuso apertar. Um diagnóstico de trinta minutos feito por quem domina o assunto pode resolver o que horas de tentativa e erro não resolveriam.

Um modelo baseado exclusivamente em duração produz um efeito perverso:

> **Quanto mais competente o ajudante, menos ele "vale"** — porque competência se manifesta como rapidez.

Qualquer referência de equivalência do Ajude.me precisa carregar as duas dimensões: **quanto custa** e **o que exige**.

---

## 6.2 Regra

> **O Ajude.me não precifica o favor. Ele o descreve.**
>
> **Descritor do favor = tempo estimado × nível da proficiência vinculada**

Não existe unidade de valor, saldo, moeda ou acúmulo. O ajudante não define um valor para sua hora de trabalho.

Os dois eixos do descritor derivam de informações que já existem no modelo:

- **Tempo estimado** — atributo obrigatório do favor (seção 4.2).
- **Nível de proficiência** — declarado no perfil (seção 3.1). Como todo favor é obrigatoriamente vinculado a uma proficiência, o favor **herda** o nível. O nível não é um campo próprio do favor.

### Exemplo

| Favor | Descritor |
|---|---|
| Diagnóstico elétrico | ≈ 30min · **Especialista** |
| Ajuda em mudança | ≈ 4h · **Básico** |
| Aula de matemática | ≈ 2h · **Avançado** |

Nenhum dos lados precisa que a plataforma declare qual favor vale mais. Ambos observam o descritor e negociam.

Esta regra é a expressão operacional do princípio fundador de que **a necessidade dita o valor do serviço**. O que impede uma negociação não é a ausência de um número, e sim a assimetria de informação: quem recebe uma proposta precisa de descrição suficiente para julgar se a troca lhe é justa.

---

## 6.3 Integridade do nível declarado

Como o nível de proficiência passa a compor a referência de equivalência, ele adquire incentivo para ser inflado.

A proteção é a exposição, e não a correção automática:

- O nível declarado é exibido junto à reputação obtida **naquela proficiência específica**.
- Quem se declara em nível superior ao que pratica acumula avaliações negativas exatamente onde a declaração é falsa.
- Nenhuma avaliação altera automaticamente o nível declarado, conforme a seção 3.3.

A divergência entre nível declarado e reputação obtida é, ela própria, informação relevante para quem avalia uma troca.

---

## 6.4 Alternativas avaliadas e descartadas

| Alternativa | Motivo do descarte |
|---|---|
| **Cada usuário define o valor da própria hora** | Constitui moeda interna de fato, trazendo precificação, arbitragem, inflação e acúmulo — e convertendo o Ajude.me no marketplace que a visão do produto rejeita. |
| **Valor fixo: toda hora equivale a uma hora** | Ignora conhecimento e penaliza o especialista por ser rápido. |
| **Média de valor entre favores semelhantes** | A média é justamente a operação que **elimina a variância** entre um bom e um mau ajudante — a informação que mais importa. Adicionalmente: inexiste dado no lançamento; a métrica torna-se circular, pois passa a alimentar-se de si mesma e a congelar o comportamento dos primeiros usuários; e um número exibido pela plataforma **é** um preço, com todos os efeitos de moeda interna. Há ainda a implicação de a plataforma passar a afirmar publicamente quanto vale determinada hora de trabalho, o que exige avaliação jurídica prévia. |

---

## 6.5 Evolução prevista — Trocas semelhantes

Aproveitar o histórico coletivo de trocas é desejável; reduzi-lo a um escalar não é.

A forma viável preserva os exemplos em vez de mediá-los:

> **Trocas semelhantes:** este favor já foi trocado por → *aula de inglês (2h)*, *conserto de bicicleta*, *corte de cabelo*

Trata-se de ancoragem por preferência revelada, baseada em trocas efetivamente realizadas, sem criar unidade de valor. Enquanto não houver histórico, o recurso simplesmente não é exibido.

**Status:** 🟡 "Trocas semelhantes" previsto para evolução posterior. A unidade formal de valor deixa de ser pendência: foi **descartada por decisão de produto**.

---

# 7. Carteira de tickets

Cada usuário possuirá uma carteira para administrar seus tickets.

A carteira deverá permitir distinguir, no mínimo:

- Tickets disponíveis;
- Tickets envolvidos em operações;
- Tickets reservados;
- Tickets consumidos;
- Tickets expirados.

---

## 7.1 Tickets expirados

Quando um ticket atingir sua validade, ele não será destruído.

O ticket será transferido para uma **Carteira de Tickets Expirados**.

O usuário poderá manter ou excluir tickets expirados.

Tickets expirados não representam mais o direito de consumir o favor correspondente.

A manutenção desses tickets permitirá futuramente a criação de uma mecânica de **colecionáveis**, incluindo tickets raros ou associados a pessoas públicas, influenciadores, artistas, eventos e experiências especiais.

Assim, um ticket poderá possuir dois valores conceituais distintos:

- **Valor de utilização:** enquanto válido;
- **Valor histórico/colecionável:** após expirado.

---

# 8. Solicitação e consumo do favor

Possuir um ticket concede ao usuário o direito de solicitar o favor correspondente, desde que a solicitação respeite as condições estabelecidas.

As condições podem incluir:

- Data;
- Horário;
- Localização;
- Área de atendimento;
- Disponibilidade;
- Prazo;
- Outras condições previamente informadas.

O ajudante deverá respeitar uma solicitação que esteja de acordo com as condições do ticket.

Três consequências da medição contra a condição publicada, decididas na construção do contexto Consumo:

- **O atendimento inteiro cabe dentro de uma janela publicada.** Aceitar um início dentro da janela e um fim depois dela empurraria o ajudante para além do que ele se dispôs a atender — e o tempo estimado é condição publicada como qualquer outra.
- **A data escolhida não ultrapassa a validade do ticket.** Agendar para depois do vencimento seria prometer o exercício de um direito que já terá acabado.
- **Ninguém solicita a si mesmo o favor que oferece.** O ticket pode voltar à carteira de quem o emitiu por uma troca; consumi-lo ali não moveria ajuda nenhuma de lugar.

O favor **não** precisa continuar ativo para ser solicitado: desativar impede novas emissões e não invalida ticket já emitido (seção 4.3).

O ajudante deverá informar previamente o prazo mínimo e/ou máximo para a prestação do favor.

Essas informações estarão disponíveis antes da negociação do ticket, evitando alegações posteriores de desconhecimento das condições.

---

## 8.1 Registro de comparecimento

A prestação presencial de um favor deverá ser delimitada por um registro de início e de fim, efetuado por ambas as partes.

Esse registro cumpre duas funções simultâneas:

1. **Evidência de comparecimento**, utilizada na confirmação da realização e na análise de ocorrências e disputas (seções 10 e 14);
2. **Delimitação da janela de atendimento**, durante a qual os mecanismos de segurança permanecem ativos.

---

## 8.2 Segurança em atendimento presencial

O favor presencial coloca duas pessoas que não se conhecem no mesmo espaço físico, frequentemente na residência de uma delas. Trata-se do risco mais concreto da plataforma, e ele existe desde o primeiro atendimento realizado.

### Princípio

> **Termo de ciência é documento, e documento não socorre ninguém.**

O termo protege a plataforma juridicamente, o que é necessário, mas **não reduz o risco do usuário**. Proteção jurídica e mecanismo operacional são complementares e devem coexistir.

### Mecanismos

| Mecanismo | Função |
|---|---|
| **Registro de comparecimento** | Delimita a janela do atendimento e produz evidência objetiva (seção 8.1). |
| **Contato de confiança** | Pessoa indicada pelo usuário que recebe automaticamente local, horário e identificação da contraparte quando um atendimento presencial é agendado. |
| **Acionamento de emergência** | Disponível durante a janela do atendimento. |
| **Termo de ciência de riscos** | Exibido no **aceite do atendimento presencial**, e não no cadastro. |

O momento de exibição do termo é regra, não detalhe de implementação: um termo apresentado no cadastro não é lido, e portanto não cumpre sua função de conscientização.

**Status:** 🟡 Redação do termo e escopo do acionamento de emergência pendentes de apoio jurídico.

---

## 8.3 Curadoria de categorias de risco

A plataforma poderá restringir a oferta de determinadas categorias de favor enquanto os mecanismos de verificação de identidade e certificação não estiverem consolidados.

> O termo de ciência cobre o risco jurídico. A curadoria cobre o risco de o evento acontecer.

Merecem restrição inicial os favores que envolvam risco físico relevante, cuidado de pessoas vulneráveis ou acesso a residência sem contexto prévio.

A existência do termo de ciência **não substitui** a curadoria.

---

## 8.4 Confirmação da realização

Após a data prevista, ambas as partes serão convidadas a confirmar que o favor foi realizado.

Abre-se então uma **janela de contestação**, durante a qual qualquer das partes poderá contestar a realização.

### Regra

> **O silêncio confirma. Somente a contestação ativa impede o encerramento.**

| Situação ao final da janela | Resultado |
|---|---|
| Ambas as partes confirmam | Favor **realizado** |
| Uma confirma, a outra silencia | Favor **realizado** por confirmação tácita |
| Ambas silenciam | Favor **realizado** por confirmação tácita |
| Qualquer parte contesta | Segue para **análise** (seção 8.5) |

A confirmação tácita existe porque o encerramento não pode depender da diligência das duas partes. Um ciclo que permanece aberto indefinidamente prende o ticket, impede as avaliações e trava a reputação de ambos.

A plataforma parte da premissa de boa-fé: presume-se que o favor combinado aconteceu, salvo manifestação em contrário.

### Proteções

- Ambas as partes serão notificadas antes do encerramento da janela.
- A contestação é gratuita, não exige justificativa prévia e está disponível durante toda a janela.
- O registro de comparecimento (seção 8.1) constitui evidência independente da confirmação.

**Status:** 🟡 Duração da janela de contestação pendente de definição.

---

## 8.5 Divergência e análise

Havendo contestação, a prestação entra em **análise** e é aberto um **chamado** para apuração pela Operação.

> **Nota terminológica:** o termo *ticket* é reservado ao direito de receber um favor. O registro de atendimento da Operação denomina-se **chamado**, evitando ambiguidade.

### Procedimento

1. A prestação passa ao estado de análise e o ticket correspondente fica bloqueado, não podendo ser trocado nem solicitado.
2. O sistema solicita a versão de cada parte sobre o ocorrido, dentro de um prazo.
3. A parte que não se manifestar no prazo não impede o prosseguimento: a análise ocorre com os elementos disponíveis.
4. A Operação analisa as manifestações, o registro de comparecimento e o histórico das partes.
5. A decisão é registrada e comunicada a ambas.

### Desfechos

| Decisão | Efeito sobre o ticket | Registro |
|---|---|---|
| **Favor realizado** | Consumido; avaliações liberadas | Ocorrência registrada contra quem contestou sem fundamento |
| **Favor não realizado** | Devolvido ao solicitante | Ocorrência registrada contra o ajudante |
| **Inconclusivo** | Devolvido ao solicitante | Nenhuma ocorrência registrada |

O desfecho **inconclusivo** favorece o portador do ticket sem atribuir culpa a ninguém. A escolha decorre do princípio de que um ticket emitido representa direito adquirido e não deve ser extinto por falta de prova (seção 20, item 3). A ausência de registro de ocorrência preserva a reputação de ambas as partes quando não há elementos para responsabilizar nenhuma delas.

**Status:** 🟡 Prazo de manifestação das partes pendente de definição.

---

# 9. Cancelamentos

O Ajude.me deverá possuir regras de cancelamento para os dois lados da relação.

## 9.1 Cancelamento pelo ajudante

O ajudante poderá solicitar o cancelamento de uma prestação.

O cancelamento deverá:

1. Possuir uma justificativa obrigatória;
2. Notificar o solicitante;
3. Permitir que o solicitante aprove ou não a justificativa;
4. Registrar a ocorrência no histórico do ajudante;
5. Liberar novamente o ticket para consumo.

A aprovação do solicitante não impedirá a devolução do ticket à disponibilidade. Ela servirá como elemento de registro e avaliação da ocorrência.

Cancelamentos recorrentes poderão gerar consequências ao ajudante.

Após três ocorrências, o usuário poderá entrar em um processo de análise ou sofrer alguma penalidade.

**Status:** 🟡 Penalidades e critérios de aplicação pendentes de definição.

---

## 9.2 Cancelamento pelo solicitante

O solicitante poderá cancelar uma solicitação a qualquer momento.

A antecedência de **24 horas** separa o cancelamento sem consequência daquele que registra ocorrência — ela não impede o cancelamento. Bloquear o cancelamento não faz ninguém comparecer: apenas converte um aviso em ausência sem aviso, que prejudica mais o ajudante.

Quando o cancelamento respeitar a antecedência:

- o ticket retorna à carteira do solicitante;
- o favor volta a poder ser solicitado;
- nenhuma penalidade deverá ser aplicada automaticamente ao ajudante.

Cancelamentos recorrentes pelo solicitante também poderão gerar ocorrências e consequências.

A lógica de justificativas e registros deverá funcionar de forma equivalente à aplicada aos ajudantes, **com uma diferença**: a justificativa é obrigatória para o ajudante e opcional para o solicitante. Exigir texto de quem desiste é obstáculo ao cancelamento, e esta seção proíbe criar obstáculo. O registro da ocorrência independe de haver justificativa.

O solicitante cancela **em qualquer estado da solicitação**, inclusive antes de o ajudante confirmar. Nesse caso não há reserva a desfazer: o ticket nunca chegou a sair da carteira.

---

# 10. Comparecimento e ocorrências

O sistema deverá permitir registrar ocorrências relacionadas à prestação do favor.

O ajudante poderá registrar uma ocorrência quando o solicitante não cumprir suas obrigações.

O solicitante também poderá registrar uma ocorrência quando o ajudante não cumprir suas obrigações.

As ocorrências poderão posteriormente ser analisadas para:

- disputas;
- reputação;
- aplicação de penalidades;
- identificação de comportamentos recorrentes.

---

# 11. Avaliações

A avaliação será parte obrigatória do encerramento de uma experiência de favor.

## 11.1 Características

As avaliações:

- serão obrigatórias;
- serão públicas;
- não poderão ser alteradas posteriormente;
- deverão representar a experiência relacionada ao favor realizado.

O objetivo da impossibilidade de alteração é reduzir conflitos posteriores e tentativas de manipulação das avaliações.

---

## 11.2 Relação entre avaliação e tipo de favor

Um usuário poderá avaliar a experiência relacionada a um determinado tipo de ticket/favor.

Exemplo:

> João consumiu um favor de eletricista → avalia a experiência daquele favor.

Se posteriormente João consumir um favor de encanador oferecido pelo mesmo usuário, essa experiência será avaliada separadamente.

A avaliação não deverá alterar diretamente a proficiência do ajudante.

Ela contribuirá para a construção da **reputação**.

---

## 11.3 Escala e critérios

A avaliação utilizará escala de **0 a 7 estrelas**.

A avaliação é **particionada**: cada parte avalia critérios específicos da outra, e não uma nota única. A partir desses critérios são apresentadas médias por critério e uma média geral no perfil.

Como usuários podem atuar nos dois papéis, os conjuntos de critérios são distintos e ambos se aplicam à mesma pessoa em momentos diferentes.

### Avaliação do Ajudante

| Critério | Significado |
|---|---|
| **Comunicação** | Facilidade de contato e clareza na interação. |
| **Qualidade** | Qualidade da ajuda efetivamente prestada. |
| **Satisfação** | Satisfação geral em relação ao favor. |

### Avaliação do Solicitante

| Critério | Significado |
|---|---|
| **Cordialidade** | Educação e hospitalidade. |
| **Comunicação** | Facilidade de contato e clareza na interação. |
| **Condições** | Condições reais em que o favor foi prestado. |

O critério **Qualidade** deverá ser acompanhado de motivos pré-determinados, de forma a reduzir acusações genéricas e infundadas.

As notas por critério são obrigatórias. O comentário em texto é opcional.

---

## 11.4 Obrigatoriedade

A avaliação é obrigatória para ambas as partes.

Enquanto uma das partes não avaliar, o ciclo do favor permanece em aberto e não é encerrado.

A obrigatoriedade será garantida por **restrição de novas operações**: o usuário com avaliação pendente além do prazo estabelecido não poderá enviar novas propostas de troca.

O objetivo é assegurar o cumprimento sem recorrer a penalidades punitivas.

**Status:** 🟡 Prazo limite para avaliação pendente de definição.

---

# 12. Confiança e reputação

A confiança no Ajude.me deverá ser construída através de diferentes fontes de evidência.

Conceitualmente:

> **Identidade verificada + Proficiência + Certificações + Histórico + Avaliações + Ocorrências**

Nenhuma dessas informações deverá ser tratada isoladamente como prova absoluta de competência.

A reputação poderá ser utilizada para identificar ajudantes com bom histórico e poderá futuramente participar das regras que determinam limites e privilégios dentro da plataforma.

---

# 13. Limites e confiabilidade dos favores

O sistema poderá estabelecer limites para a criação ou disponibilização de determinados favores de acordo com o nível de confiança do ajudante.

Um usuário com poucas evidências de confiabilidade poderá não possuir os mesmos privilégios de um usuário com histórico consolidado.

Possíveis fatores:

- Avaliações;
- Quantidade de favores realizados;
- Certificações verificadas;
- Tempo de participação;
- Taxa de cancelamento;
- Histórico de ocorrências;
- Reputação.

A relação exata entre esses fatores e os limites de valor/quantidade ainda será definida.

**Status:** 🟡 Regras de limites e níveis de confiança pendentes de definição.

---

# 14. Disputas e evidências

O Ajude.me poderá possuir mecanismos de disputa para situações em que uma das partes alegue que o favor não foi realizado corretamente.

O sistema deverá permitir o registro de evidências da prestação do favor quando aplicável.

Exemplos:

- Fotografias;
- Documentos;
- Comprovantes;
- Registros de conclusão;
- Outros tipos de evidência.

A evidência não será necessariamente obrigatória para todos os tipos de favor, pois diferentes serviços possuem diferentes formas de comprovação.

As disputas poderão ser analisadas com base nas informações disponíveis.

O procedimento de análise por chamado, definido na seção 8.5, aplica-se à contestação de realização. Ele constitui a forma mais simples de disputa e serve de base para as modalidades mais elaboradas previstas nesta seção.

O que permanece pendente é a disputa sobre **qualidade** do favor prestado — situação em que ambas as partes reconhecem que a prestação ocorreu, mas divergem quanto ao resultado.

**Status:** 🟡 Disputa por qualidade e regime probatório pendentes de definição. Contestação de realização definida na seção 8.5.

---

# 15. Dinheiro

O Ajude.me permitirá a utilização de dinheiro nas operações entre usuários.

O dinheiro poderá coexistir com os tickets dentro do modelo de troca.

O mecanismo exato ainda deverá ser definido, incluindo:

- Compra direta de tickets;
- Dinheiro como complemento de uma troca;
- Limitações para operações financeiras;
- Regras de proteção;
- Taxas;
- Intermediação.

**Status:** 🟡 Modelo operacional de dinheiro pendente de definição.

---

# 16. Monetização

A utilização de dinheiro poderá fazer parte do modelo de monetização do Ajude.me.

Também foi considerada a possibilidade de planos pagos que ofereçam mecanismos adicionais de proteção ou garantia.

Um possível conceito é permitir que determinadas modalidades ofereçam compensação ou reembolso caso um favor não seja realizado.

Esse mecanismo ainda não faz parte das regras definitivas do produto.

**Status:** 🟡 Modelo de monetização e proteção pendente de definição.

---

# 17. Localização e área de atendimento

O usuário poderá definir informações relacionadas à sua localização e à área em que está disposto a prestar favores.

O favor poderá possuir características específicas relacionadas a:

- Localização;
- Distância;
- Área de atendimento;
- Modalidade de prestação;
- Disponibilidade.

Essas informações serão utilizadas para ajudar o sistema a encontrar usuários compatíveis com a necessidade apresentada (seção 3.4).

---

# 18. Privacidade e compartilhamento de informações

Informações de contato e localização não deverão ficar públicas indiscriminadamente.

Telefone, endereço e informações semelhantes deverão ser disponibilizados somente quando houver uma relação de prestação de ajuda que justifique esse compartilhamento.

---

## 18.1 Momento do compartilhamento

O acesso aos dados de contato acompanha o avanço da relação entre os usuários:

| Momento | Dados visíveis |
|---|---|
| Antes da proposta | Nome, foto, região aproximada e reputação |
| Durante a negociação | Comunicação restrita ao chat interno |
| Após o agendamento da solicitação | Telefone e endereço de atendimento |
| Após o encerramento | Retorno ao estágio anterior à negociação |

Endereço exato e telefone **nunca** serão públicos.

Uma exceção se aplica ao **contato de confiança** indicado pelo usuário (seção 8.2), que recebe local e horário do atendimento agendado por finalidade de segurança.

---

## 18.2 Bloqueio e denúncia

O usuário poderá **bloquear** outro usuário.

O bloqueio impedirá o envio de propostas entre as partes e ocultará mutuamente seus perfis e favores.

O usuário poderá ainda **denunciar** um usuário ou um favor, gerando registro para análise da plataforma.

O bloqueio é uma decisão individual e não constitui, por si só, avaliação negativa nem ocorrência.

---

## 18.3 Necessidades registradas

O que uma pessoa procura revela o que lhe falta.

Buscas por necessidade podem expor situação de saúde, condição financeira, composição familiar, vulnerabilidade ou dependência — por vezes de terceiros, e não apenas de quem digita.

Regras:

- A necessidade registrada **não é pública** e não aparece em nenhum perfil.
- Nenhum usuário é notificado de que outro procurou determinado favor.
- A análise de necessidades não atendidas (seção 3.5) utiliza dados **agregados por região e proficiência**, nunca buscas individuais atribuíveis.
- O aviso de disponibilidade solicitado pelo usuário vincula-se à proficiência, não ao texto digitado.

### Retenção

O texto original da busca é apagado após **90 dias**. Permanecem apenas a proficiência reconhecida, a região e se houve resultado — que é o que alimenta o mapa de cobertura (seção 3.5).

O texto serve a uma única finalidade: permitir que a Operação descubra qual termo faltava no vocabulário. Cumprida essa função, ele é o dado mais sensível do produto e o de utilidade mais curta.

**Status:** ✅ Definido — Fase 0.

---

# 19. Conta empresarial

No modelo inicial, empresas não serão participantes independentes da plataforma.

Uma pessoa física poderá informar que representa uma empresa/CNPJ, mas os favores continuarão vinculados ao perfil da pessoa física.

Exemplo:

> **João Silva**  
> Proficiência: Suporte de TI  
> Representa: Empresa XYZ Ltda.

O objetivo é manter o foco do Ajude.me na participação individual, especialmente considerando o público principal de baixa e média renda.

---

# 20. Princípios gerais das regras de negócio

As regras do Ajude.me deverão preservar os seguintes princípios:

1. **Um ticket representa uma oportunidade real de receber ajuda.**
2. **Condições de um favor devem ser conhecidas antes da negociação.**
3. **Tickets já emitidos não devem desaparecer arbitrariamente.**
4. **Ajudantes e solicitantes devem possuir responsabilidades equivalentes.**
5. **Reputação não substitui certificação, e certificação não garante competência prática.**
6. **O sistema deve preservar histórico suficiente para investigar conflitos.**
7. **A experiência de ajuda deve permanecer mais importante do que a mecânica financeira.**
8. **As regras devem priorizar transparência, segurança e confiança.**
9. **A plataforma deve evitar burocracia desnecessária para realizar um favor.**
10. **Um usuário pode ajudar e receber ajuda, não existindo uma divisão permanente entre prestador e cliente.**
11. **A plataforma descreve o favor; ela não o precifica.**
12. **Mecanismo de segurança e proteção jurídica são complementares — nenhum substitui o outro.**

---

# 21. Decisões pendentes

| Tema | Status |
|---|---|
| Método de validação de identidade | 🟡 Pendente |
| Método de verificação de certificados | 🟡 Pendente |
| Penalidades por cancelamentos recorrentes | 🟡 Pendente |
| Limites baseados em reputação/confiabilidade | 🟡 Pendente |
| Base legal do contato de confiança | 🟡 Pendente — apoio jurídico |
| Modelo operacional de dinheiro | 🟡 Pendente |
| Modelo de monetização | 🟡 Pendente |
| Modelo de garantia/proteção | 🟡 Pendente |
| Disputa por qualidade do favor prestado | 🟡 Pendente |
| Regras completas de evidências | 🟡 Pendente |

## Parâmetros temporais

Valores definidos para o MVP. Todos são **parâmetros versionados com vigência**, nunca constantes de código: uma alteração vale para relações novas e não reescreve as que já estão em curso.

| Parâmetro | Valor | Regra | Por quê |
|---|---|---|---|
| Janela de contestação | **72 horas** | Seção 8.4 | Curto demais penaliza quem não abre o aplicativo todo dia; longo demais prende o ticket |
| Manifestação das partes na análise | **5 dias** | Seção 8.5 | Maior que a contestação, porque exige contar uma versão dos fatos |
| Avaliação pendente antes da restrição | **7 dias** | Seção 11.4 | Suficiente para não punir esquecimento, curto para não travar o ciclo do outro |
| Validade da proposta de troca | **7 dias** | Seção 5.4 | Os tickets ficam bloqueados enquanto ela existe |
| Antecedência para cancelamento pelo solicitante | **24 horas** | Seção 9.2 | Ver a regra revista abaixo |
| Validade máxima do ticket | **1 ano** | Seção 4.2 | Limita por quanto tempo uma conta pode ficar em encerramento |
| Prazo máximo da conta em Encerrando | **Sem prazo** | Seção 2.2 | A conta encerra quando os tickets forem consumidos ou expirarem. Encurtar esse prazo destruiria tickets válidos, contra o princípio 3 da seção 20. A validade máxima de 1 ano é o que mantém isso tolerável |

### Revisão da regra de cancelamento pelo solicitante

A antecedência de 24 horas **não impede** o cancelamento. Cancelar dentro da janela é permitido e registra uma ocorrência.

> Bloquear o cancelamento não faz a pessoa comparecer — apenas transforma um cancelamento avisado em ausência sem aviso, que é pior para o ajudante.

A regra deixa de ser permissão e passa a ser consequência: cancelar com folga não gera registro; cancelar em cima da hora gera.

O mesmo raciocínio se aplica ao ajudante (seção 9.1), que já cancelava mediante justificativa e registro.
| Redação do termo de ciência de riscos | 🟡 Pendente — apoio jurídico |
| Escopo do acionamento de emergência | 🟡 Pendente — apoio jurídico |
| Natureza jurídica da troca sem dinheiro | 🟡 Pendente — apoio jurídico |

## Decisões encerradas nesta versão

| Tema | Resolução |
|---|---|
| Unidade formal para valor/hora | ❌ **Descartada** — substituída pelo descritor de dois eixos (seção 6) |
| Dados compartilhados após a solicitação | ✅ **Definido** — seção 18.1 |
| Natureza da quantidade máxima de tickets | ✅ **Definido** — limite de circulação simultânea, seção 5.1 |
| Todos os parâmetros temporais | ✅ **Definidos** — seção 21, Fase 0 |
| Compensação quando o ajudante encerra a conta | ✅ **Definido** — proteção por prazo integral, seção 2.2 |
| Mecanismo de reconhecimento de necessidade | ✅ **Definido** — termos populares curados, seção 3.4 |
| Retenção do texto original das buscas | ✅ **Definido** — 90 dias, seção 18.3 |
| Ausência de confirmação da realização | ✅ **Definido** — confirmação tácita, seção 8.4 |
| Divergência na confirmação da realização | ✅ **Definido** — análise por chamado, seção 8.5 |

---

# 22. Conceitos futuros

Os seguintes conceitos foram identificados, mas não fazem parte das regras fundamentais neste momento:

- Tickets colecionáveis;
- Tickets raros;
- Tickets relacionados a influenciadores, artistas e pessoas públicas;
- Bonificações por encerramento de conta;
- Planos pagos;
- Garantias e mecanismos de proteção;
- Mecanismos avançados de negociação financeira;
- Trocas semelhantes (histórico coletivo de equivalências — seção 6.5).

Esses conceitos poderão ser desenvolvidos posteriormente sem alterar a essência do modelo.

---

# 23. Status do documento

| Item | Status |
|---|---|
| Conceitos fundamentais | ✅ Definido |
| Identidade | ✅ Definido conceitualmente |
| Habilidades e proficiências | ✅ Definido |
| Busca por necessidade | ✅ Conceito definido |
| Necessidade não atendida | ✅ Definido |
| Certificações | 🟡 Definido conceitualmente |
| Favores | ✅ Definido |
| Tickets | ✅ Definido |
| Negociação e troca | ✅ Definido |
| Valor e equivalência | ✅ Definido |
| Carteira | ✅ Definido |
| Expiração | ✅ Definido |
| Solicitação | ✅ Definido conceitualmente |
| Confirmação da realização | ✅ Definido — confirmação tácita |
| Divergência e análise por chamado | ✅ Definido |
| Registro de comparecimento | ✅ Definido |
| Segurança em atendimento presencial | ✅ Mecanismos definidos |
| Termo de ciência de riscos | 🟡 Pendente — apoio jurídico |
| Cancelamentos | ✅ Definido conceitualmente |
| Avaliações | ✅ Definido — escala e critérios |
| Bloqueio e denúncia | ✅ Definido |
| Reputação | ✅ Definido conceitualmente |
| Disputas | 🟡 Definido conceitualmente |
| Dinheiro | 🟡 Definido conceitualmente |
| Monetização | 🟡 Definido conceitualmente |
| Localização | ✅ Definido conceitualmente |
| Privacidade | ✅ Definido conceitualmente |
| Regras operacionais detalhadas | 🟡 Próxima etapa |

---

**Versão:** 2.5
**Produto:** Ajude.me
**Documento:** Conceito e Regras de Negócio

---

## Histórico de versões

| Versão | Alterações |
|---|---|
| 1.0 | Definição inicial dos conceitos e regras de negócio |
| 2.5 | Seção 8 — três consequências da medição contra a condição publicada, decididas na construção do contexto Consumo: o atendimento inteiro cabe na janela, a data não ultrapassa a validade do ticket, e ninguém solicita a si mesmo. Explicitado que favor desativado continua atendendo ticket já emitido. Seção 9.2 — justificativa obrigatória para o ajudante e opcional para o solicitante, e cancelamento admitido também antes da confirmação. |
| 2.4 | Seção 5.4 — três regras que a construção da fatia Negociação obrigou a decidir: ticket não trocável não circula por proposta em nenhum dos dois lados; a contrapartida pode ser vazia, exigido ao menos um ticket solicitado; e quem revisa a oferta devolve a decisão à outra parte. |
| 2.3 | **Fase 0 fechada.** Todos os parâmetros temporais receberam valor (seção 21). Validade do ticket limitada a 1 ano. Encerramento de conta sem prazo máximo, com proteção integral ao portador. Cancelamento pelo solicitante deixa de ser bloqueado e passa a registrar ocorrência quando em cima da hora. Retenção do texto das buscas definida em 90 dias. |
| 2.2 | Novas seções 3.4 e 3.5 — busca por necessidade e necessidade não atendida. Nova seção 18.3 — privacidade das necessidades registradas. Novos conceitos: Necessidade e Termo popular. |
| 2.1 | Seção 5.1 — a quantidade máxima de tickets passa a ser limite de circulação simultânea, com liberação de vaga. Nova seção 8.4 — confirmação da realização por silêncio. Nova seção 8.5 — divergência e análise por chamado. Seção 14 restringida à disputa por qualidade. Consolidação dos parâmetros temporais na seção 21. |
| 2.0 | Seção 6 reescrita: o valor deixa de ser calculado por hora e passa a ser expresso pelo descritor de dois eixos (tempo × nível de proficiência). Nova seção 5.4 — Negociação e troca. Novas seções 8.1, 8.2 e 8.3 — comparecimento, segurança presencial e curadoria de categorias. Novas seções 11.3 e 11.4 — escala, critérios e obrigatoriedade da avaliação. Novas seções 18.1 e 18.2 — momento do compartilhamento de dados, bloqueio e denúncia. Novos conceitos fundamentais e princípios 11 e 12. **Incorporação e encerramento do rascunho `CoreV1`.** |

---

## Nota sobre o rascunho CoreV1

O rascunho original `CoreV1` foi integralmente revisado nesta versão.

O conteúdo ainda pertinente foi incorporado a este documento:

| Conteúdo | Destino |
|---|---|
| Escala de 0 a 7 e critérios de avaliação | Seção 11.3 |
| Proposta, contrapartida e chat de negociação | Seção 5.4 |
| Bloqueio de usuários | Seção 18.2 |
| Carteira de coleção e tickets colecionáveis | Seção 22 — Conceitos futuros |
| Feed, busca e filtros | Documento de Requisitos Funcionais |

O seguinte conteúdo foi **descartado**:

| Conteúdo | Motivo |
|---|---|
| Aplicação iniciar no "perfil profissional" | Conflita com a regra fundamental de que o usuário não possui papel fixo (seção 1) |
| Nomes alternativos do produto | Nome definido: **Ajude.me** |

> **A partir desta versão, o `CoreV1` deixa de ser fonte de referência.** Este documento, em conjunto com `01 - Visão do Produto.md`, constitui a base conceitual do Ajude.me.