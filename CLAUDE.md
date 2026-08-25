# Ajude.me

Plataforma de troca de favores entre pessoas, onde habilidade vira moeda. Público: baixa e média renda no Brasil.

**Leia `00 - Estado do Projeto.md` antes de qualquer coisa.** Ele traz o estado atual, os links, as decisões já tomadas e as que continuam abertas.

## Fonte de verdade

`02 - Conceito e Regras de Negócio.md` é a autoridade sobre regras. Os documentos `03` a `06` derivam dele e referenciam suas seções.

O conteúdo de `_arquivo/` está superado e **não é referência**.

## Restrições que não se negociam sem decisão explícita

- **Sem dinheiro no MVP.** Nenhum pagamento, taxa ou intermediação.
- **A plataforma descreve o favor, não o precifica.** Não deve existir campo de saldo, crédito ou valor acumulável — nem no modelo de dados.
- **Ticket emitido não desaparece** por desativação, encerramento de conta ou bloqueio.
- **Condição publicada é imutável.** O favor congela na primeira emissão.
- **Não existe pedido de ajuda como entidade** — existe busca por necessidade mapeada para proficiências.
- **Nada de glassmorphism, aero ou blur** no design: custa desempenho em aparelho de entrada e destrói contraste sob sol.

## Vocabulário

Use: Favor, Ticket, Carteira, Proposta, Contrapartida, Ajudante, Solicitante, Proficiência, Descritor, Necessidade, Ocorrência, Chamado.

Nunca use: serviço, preço, comprar, vender, contratar, profissional, cliente.

*Ticket* é só o direito de receber um favor. Atendimento da Operação chama-se **chamado**.

## Convenção de trabalho

Decisão tomada volta aos documentos afetados **na mesma sessão**, com a versão do documento incrementada e o histórico de versões atualizado. O `04` e o `05` derivam do `02`; drift entre eles é o modo de falha mais provável do projeto.

Idiomas: documentação e interface em português do Brasil.
