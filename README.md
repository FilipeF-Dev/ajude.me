# Ajude.me

Plataforma de troca de favores: quem sabe fazer algo oferece, e com isso
consegue a ajuda de que precisa. A habilidade vira moeda, sem dinheiro no
meio. O público é quem não pode pagar por serviços.

> Precisar de ajuda → encontrar alguém → negociar → receber ajuda → ajudar outra pessoa

## Onde está

A documentação de produto está completa, e a **Fase 1** do código está
fechada: identidade, oferta, tickets, negociação, atendimento, moderação,
reputação, descoberta e um painel de métricas por coorte para o piloto.
O detalhe de cada parte está em [00 - Estado do Projeto](00%20-%20Estado%20do%20Projeto.md).

## Documentação

O produto foi especificado antes do código. Cada regra implementada cita a
seção ou o requisito de onde veio.

| | |
|---|---|
| [01 - Visão do Produto](01%20-%20Visão%20do%20Produto.md) | O problema, o propósito e os princípios |
| [02 - Conceito e Regras de Negócio](02%20-%20Conceito%20e%20Regras%20de%20Negócio.md) | Tickets, favores, negociação e o vocabulário do domínio |
| [03 - MVP](03%20-%20MVP.md) | O recorte do piloto e as hipóteses a medir |
| [04 - Requisitos Funcionais](04%20-%20Requisitos%20Funcionais.md) | Os requisitos, numerados |
| [05 - Arquitetura Conceitual](05%20-%20Arquitetura%20Conceitual.md) | Contextos, agregados e invariantes |
| [06 - Roadmap do Produto](06%20-%20Roadmap%20do%20Produto.md) | Fases, ordem de construção e critérios de saída |
| [`design/`](design) | As telas do fluxo principal |

## Stack

Laravel 13 · PHP 8.4 · PostgreSQL 16 com PostGIS · Filament 5 · Livewire · Docker

A aplicação está em [`plataforma/`](plataforma), com instruções para rodar.
