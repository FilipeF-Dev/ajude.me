# Ajude.me — Estado do Projeto

> Documento de continuidade. Serve para que qualquer pessoa — ou qualquer sessão de trabalho — entenda em minutos onde o projeto está, o que já foi decidido e o que não deve ser redecidido.
>
> **Atualize este documento sempre que uma decisão estrutural for tomada.**

**Última atualização:** 25 de agosto de 2026

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
| Fase 0 do roadmap (fundação) | 🟡 Não iniciada — 16 pendências |
| Código | ⬜ Nada escrito |
| Repositório | 🟡 Iniciado com `origin` configurado — **sem nenhum commit ainda** |

**Equipe:** uma pessoa. Isso é restrição de planejamento, não detalhe — pesa sobre a carga de operação manual prevista nas fases 2 e 3 do `06`.

---

# 4. Documentação

Toda a série está escrita e sincronizada entre si. Cada documento referencia os anteriores por seção.

| Documento | Versão | Conteúdo |
|---|---|---|
| `01 - Visão do Produto.md` | 1.0 | Identidade, propósito, princípios |
| `02 - Conceito e Regras de Negócio.md` | 2.2 | Conceitos e regras — **a fonte de verdade** |
| `03 - MVP.md` | 1.2 | Escopo da primeira versão e dívidas assumidas |
| `04 - Requisitos Funcionais.md` | 1.2 | 205 requisitos rastreáveis, 14 módulos |
| `05 - Arquitetura Conceitual.md` | 1.1 | Contextos, entidades, 27 invariantes |
| `06 - Roadmap do Produto.md` | 1.0 | Seis fases com portões de evidência |

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

## 6.1 Bloqueiam a Fase 0 (afetam modelo de dados ou legalidade)

| Pendência | Onde |
|---|---|
| Congelamento do nível de proficiência no favor | `05 §14.1` |
| Anonimização versus exclusão de dados pessoais | `05 §11.4` |
| Base legal do contato de confiança | `05 §11.3` |
| Retenção do texto original das buscas | `05 §11.5` |
| Retenção de mensagens de negociação | `05 §12` |
| Sete decisões técnicas (plataforma, armazenamento, reconhecimento de necessidade, etc.) | `05 §15` |

## 6.2 Parâmetros sem valor definido

Janela de contestação · prazo de manifestação em chamado · prazo para avaliar · validade da proposta · antecedência de cancelamento · prazo da conta encerrando. Todos em `04 §19.2`.

**Regra:** são parâmetros versionados com vigência, nunca constantes no código (`05 §9`).

## 6.3 Trabalho jurídico a contratar

Termo de ciência de riscos · escopo do acionamento de emergência · natureza jurídica da troca sem dinheiro · base legal do contato de confiança · política de privacidade · termos de uso.

## 6.4 Lacuna de produto

**Disputa por qualidade** (`04 L11`): a análise por chamado resolve *se o favor aconteceu*, não o caso em que ambos concordam que aconteceu mas divergem sobre o resultado. Vence na Fase 4.

---

# 7. Design

## 7.1 Telas prontas

Canvas em três páginas, 16 telas:

| Página | Telas |
|---|---|
| **Encontrar e trocar** | Início · Busca por necessidade · Busca sem resposta · Página do favor · Montar proposta · Negociação · Carteira |
| **Oferecer ajuda** | 4 passos: habilidade → a ajuda → quando e onde → os tickets |
| **Receber a ajuda** | Pedir o favor · Combinado · Durante o atendimento · Aconteceu? · Avaliação |

**Faltam:** perfil (reputação por proficiência), lista de conversas.

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

# 10. Próximo passo recomendado

**Fase 0 do roadmap.** Nenhuma linha de código antes das pendências de 6.1 estarem fechadas — todas afetam modelo de dados ou legalidade, e resolvê-las depois custa retrabalho estrutural.

Em paralelo, a escolha da **comunidade-piloto** (`06 §4.5`) determina a qualidade de toda a evidência das fases seguintes.
