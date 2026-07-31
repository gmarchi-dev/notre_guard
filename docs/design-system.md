# Design System — Gestão de Pessoas
### Colégio Notre Dame Campinas

Referência visual completa: `design-system-preview.html`

---

## 1. Direção de design

**Heritage institucional discreta.** A combinação azul-marinho profundo + dourado é a mesma linguagem de brasões e selos acadêmicos tradicionais (não por acaso, é a dupla de cores do próprio Notre Dame nos EUA) — usamos isso a favor, evitando o clichê de "admin dashboard SaaS genérico".

- **Navy** carrega peso e autoridade: estrutura, dados sensíveis, ações primárias.
- **Gold** é usado com moderação: destaque pontual, hierarquia, nunca como cor de fundo grande nem como texto corrido sobre fundo claro (contraste insuficiente).
- **Neutros** cobrem ~90% da superfície. Se uma tela usa mais de ~10% de área em dourado, passou do ponto.

---

## 2. Tokens de cor

```css
:root {
  /* Marca */
  --color-navy-900: #013d53;   /* cor primária — headers, botões primários, texto de destaque */
  --color-navy-700: #0a5570;   /* hover/estado ativo de elementos navy */
  --color-navy-100: #e4edf0;   /* fundo sutil (cards selecionados, linha ativa de tabela) */

  --color-gold-500: #cfb276;   /* accent — bordas, ícones ativos, badges, hairlines */
  --color-gold-300: #e3d3b3;   /* fundo sutil de badge/hover em superfícies claras */
  --color-gold-700: #a88a52;   /* texto/ícone dourado sobre fundo claro (contraste AA) */

  /* Neutros — base para textos e superfícies */
  --color-ink-900: #171a1c;    /* texto principal */
  --color-ink-600: #52606b;    /* texto secundário */
  --color-surface-0: #ffffff;
  --color-surface-50: #f7f8f8;
  --color-surface-100: #eef0f1;
  --color-border: #dde2e4;

  /* Semânticas — mesma lógica Alta/Média/Baixa do AD-DeltaMonitor, para consistência entre sistemas */
  --color-danger: #b3423a;     /* Alta severidade / reprovado / atenção crítica */
  --color-warning: #b8873a;    /* Média severidade / pendente aprovação — deliberadamente distinto do gold de marca */
  --color-success: #2f7a52;    /* Baixa severidade / aprovado / ativo */
}
```

| Token | Hex | Uso |
|---|---|---|
| `navy-900` | `#013d53` | Cor primária — headers, botões, texto de destaque |
| `navy-700` | `#0a5570` | Hover/estado ativo |
| `navy-100` | `#e4edf0` | Fundo sutil (cards/linha selecionada) |
| `gold-500` | `#cfb276` | Accent — bordas, badges, hairlines |
| `gold-300` | `#e3d3b3` | Fundo sutil de badge/hover |
| `gold-700` | `#a88a52` | Texto/ícone dourado com contraste AA; **também usado no focus ring** |
| `ink-900` | `#171a1c` | Texto principal |
| `ink-600` | `#52606b` | Texto secundário |
| `surface-0` | `#ffffff` | Fundo base |
| `surface-50` | `#f7f8f8` | Fundo de página/seções |
| `surface-100` | `#eef0f1` | Hover de superfícies neutras |
| `border` | `#dde2e4` | Bordas padrão |
| `danger` | `#b3423a` | Alta severidade / reprovado |
| `warning` | `#b8873a` | Média severidade / pendente |
| `success` | `#2f7a52` | Baixa severidade / aprovado / ativo |

---

## 3. Tipografia

Único family: **Inter** (pesos 400/500/600/700, variable font).

| Papel | Peso | Tamanho | Uso |
|---|---|---|---|
| Display | 700 | 28–32px, `letter-spacing: -0.01em` | Títulos de página, nome do módulo |
| Heading | 600 | 18–20px | Títulos de card/seção |
| Body | 400 | 14px | Texto padrão, tabelas (densidade admin) |
| Body forte | 500 | 14px | Labels, valores destacados |
| Caption | 400 | 12px | Legendas, metadados, timestamps |
| Dados numéricos | 500, `font-variant-numeric: tabular-nums` | 14px | Salários, IDs, colunas numéricas |

Sem itálico decorativo — hierarquia vem de peso e cor, não de estilo.

---

## 4. Elemento de assinatura

**Badge de senioridade** como linguagem visual recorrente — reaproveitada em cards de colaborador, nós do organograma e colunas de tabela. A intensidade da cor comunica o nível, não só o texto:

- `JR` — fundo `navy-100`, texto `navy-900`, sem borda
- `PL` — fundo `navy-900`, texto branco, sem borda
- `SR` — fundo `navy-900`, texto branco, **borda de 1px `gold-500`**

O traço dourado de 3px no topo (`border-top: 3px solid var(--color-gold-500)`) é a assinatura do sistema — reutilizado em:
- Card de colaborador nível SR
- Cards de KPI que representam "aprovado" / "no topo"
- Painel de cenário promovido
- Cabeçalho de ficha de movimentação aprovada

Ou seja: **dourado sinaliza consistentemente "o que atingiu o nível mais alto/aprovado"**, em qualquer contexto do sistema — dá uma linguagem única sem depender de ícones extras.

---

## 5. Componentes e padrões de layout

| Componente | Especificação |
|---|---|
| **Organograma — nó ocupado** | Card branco, `border: 1px solid var(--color-border)`, `border-radius: 8px`, sombra `0 1px 2px rgba(1,61,83,0.06)` |
| **Organograma — vaga em aberto** | Mesmo card, borda **tracejada** `gold-500`, fundo `surface-50`, sem sombra — diferencia visualmente sem precisar de legenda |
| **Botão primário** | Fundo `navy-900`, hover `navy-700`, texto branco, `border-radius: 6px` (não pill) |
| **Botão secundário/ghost** | Borda `border`, texto `ink-900`, hover fundo `surface-100` |
| **Tabelas densas** | Zebra sutil (`surface-50`), cabeçalho `navy-900` com texto branco, uppercase 12px, `letter-spacing: 0.04em` |
| **Card de cenário (workforce planning)** | Traço dourado superior só no cenário ativo/selecionado; demais ficam neutros |
| **Ícones** | Heroicons (padrão Filament/Blade UI Kit), cor `ink-600`; ícones de ação primária em `navy-900` |
| **Raio de borda geral** | 6–8px em toda a aplicação — `rounded-full` só em avatares e badges de senioridade |
| **Foco de teclado** | Outline 2px em `gold-700` (não `gold-500` — contraste insuficiente para indicar foco), `offset: 2px`, visível em todos os elementos interativos |

---

## 6. Implementação técnica (Laravel + Filament + Tailwind)

1. Definir os tokens da seção 2 como variáveis CSS em `resources/css/design-tokens.css`, importado antes do Tailwind.
2. Estender `tailwind.config.js` para expor as cores como classes utilitárias (`bg-navy-900`, `text-gold-700` etc.), referenciando as CSS variables — nunca hardcodar hex direto nas classes, para manter uma única fonte de verdade.
3. Customizar o tema do Filament via `FilamentColor::register()` no `PanelServiceProvider`, mapeando `Color::hex('#013d53')` como cor primária do painel — assim os componentes nativos do Filament (botões, badges de status, navegação) já nascem consistentes, sem sobrescrever cada componente manualmente.
4. Carregar Inter via `@fontsource/inter` (self-hosted) ou variable font local — evitar CDN externo por padrão, dado o contexto de instituição de ensino com requisitos de LGPD/privacidade já mapeados no restante do projeto.
5. Aplicar o design system já na Fase 1 (fundação) do desenvolvimento — mais barato configurar uma vez no início do que retrabalhar telas depois.

---

## 7. Acessibilidade — checklist rápido

- [ ] Contraste de texto sobre `gold-500` verificado (usar `gold-700` para texto/ícone sobre fundos claros)
- [ ] Foco de teclado visível em todos os elementos interativos (`gold-700`, nunca `gold-500`)
- [ ] Nenhuma informação transmitida só por cor (badges de severidade têm texto, não só cor de fundo)
- [ ] `border-left`/`border-top` de destaque nunca combinados com `border-radius` nos quatro cantos (evitar recorte visual estranho)
