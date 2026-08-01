# 15 - Design system institucional (sistema de Ativos)

Extraído do sistema de gestão de ativos do Colégio Notre Dame Campinas, em 31/07/2026, a partir das
capturas de tela e **dos tokens CSS de origem**, fornecidos em seguida.

Instrução que o define como norma: *"É exatamente dessa forma que nosso layout de cores, fontes,
sidebar, deve ser seguida."*

> Os valores abaixo são os do código-fonte, não estimativas. Onde eu havia chutado a partir da
> captura, o palpite está corrigido - e algumas correções foram grandes: a fonte não é Inter, a
> sidebar é bem mais escura, o item ativo é um véu translúcido e não um azul sólido, e existe um
> dourado que eu havia dado como inexistente.

---

## 1. Direção de design

**Painel operacional denso e frio, com âncora quase preta à esquerda.** Muita informação por tela,
hierarquia por peso e cor, decoração nenhuma.

- **Sidebar `#071120`** - azul tão escuro que lê como preto. Dá o eixo vertical sem precisar de
  borda.
- **Dois azuis com papéis distintos**: `--nc-primary` (`#0d2144`) é institucional, de estrutura;
  `--nc-accent` (`#1752d9`) é de **ação** - botão, link, item ativo, número em destaque.
- **Branco sobre azulado frio.** O cartão branco é a unidade de conteúdo; o fundo `#edf0f7` é o que
  o faz parecer elevado quase sem sombra.
- **Cor semântica só em estado.** Cada uma vem em trio: sólida, fundo suave e texto forte.

---

## 2. Tokens de cor

Nomenclatura de origem preservada (`--nc-*`), para que os dois sistemas falem a mesma língua.

### Marca e estrutura

| Token | Hex | Uso |
|---|---|---|
| `--nc-primary` | `#0d2144` | Navy institucional: cabeçalho de tabela, estrutura |
| `--nc-primary-mid` | `#1a3a5c` | Intermediário |
| `--nc-primary-light` | `#2563a8` | Navy claro |
| `--nc-sidebar-bg` | `#071120` | Fundo da barra lateral |
| `--nc-sidebar-text` | `#94a3b8` | Rótulo de item inativo |
| `--nc-sidebar-active` | `rgba(23, 82, 217, 0.18)` | Véu do item ativo |
| `--nc-sidebar-w` | `256px` | Largura da barra |

O item ativo **não é um azul sólido**: é o accent a 18% de opacidade sobre o fundo quase preto, o
que resolve em `#0a1d41`. Isso o mantém discreto e faz o rótulo branco render 16,6:1.

### Ação

| Token | Hex | Uso |
|---|---|---|
| `--nc-accent` | `#1752d9` | Botão primário, link, item ativo, valor em destaque |
| `--nc-gold` | `#c4943e` | Acento decorativo — **ver a restrição na seção 8** |

### Superfície e texto

| Token | Hex | Uso |
|---|---|---|
| `--nc-surface` | `#ffffff` | Cartão, linha de tabela, campo |
| `--nc-surface-2` | `#edf0f7` | Fundo da página (`body`) |
| `--nc-surface-3` | `#f1f5f9` | Superfície alternativa, zebra |
| `--nc-border` | `#c0cade` | Borda |
| `--nc-border-light` | `#e2e8f0` | Fio interno |
| `--nc-text` | `#0f172a` | Título e valor |
| `--nc-text-2` | `#475569` | Texto secundário |
| `--nc-text-muted` | `#94a3b8` | Metadado — **ver a restrição na seção 8** |

### Semânticas

Cada uma em trio: sólida, fundo de pastilha e texto de pastilha.

| Estado | Sólida | Fundo | Texto |
|---|---|---|---|
| Sucesso | `#16a34a` | `#dcfce7` | `#15803d` |
| Aviso | `#d97706` | `#fef9c3` | `#a16207` |
| Perigo | `#dc2626` | `#fee2e2` | `#b91c1c` |
| Info | `#2563a8` | `#dbeafe` | `#1d4ed8` |

### Condição do ativo

Escala própria, de cinco degraus, com o mesmo padrão fundo + texto:

| Condição | Fundo | Texto |
|---|---|---|
| Excelente | `#dcfce7` | `#15803d` |
| Boa | `#dbeafe` | `#1d4ed8` |
| Regular | `#fef9c3` | `#a16207` |
| Ruim | `#ffedd5` | `#c2410c` |
| Danificada | `#fee2e2` | `#b91c1c` |

---

## 3. Tipografia

| Token | Família |
|---|---|
| `--font-display` | **Onest** |
| `--font-body` | **Onest** |
| `--font-mono` | **JetBrains Mono** |

Corpo: `0.875rem` (14px), peso 400, `line-height: 1.5`, com `-webkit-font-smoothing: antialiased`.

O monoespaçado carrega código de ativo e número de série - onde alinhamento de dígito importa.

> **Onest e JetBrains Mono não estão no projeto.** O sistema de origem as carrega do
> `fonts.gstatic.com`. Aqui não dá: o app de campo é offline-first e o projeto já decidiu não usar
> CDN externa por privacidade. Precisam ser auto-hospedadas, como a Inter já é hoje.

---

## 4. Raio

| Token | Valor |
|---|---|
| `--nc-r-xs` | 4px |
| `--nc-r-sm` | 6px |
| `--nc-r-md` | 10px |
| `--nc-r-lg` | 14px |
| `--nc-r-xl` | 20px |

---

## 5. Sombra

Todas tingidas de navy (`rgba(13, 33, 68, …)`), nunca preto puro - é o que evita a sombra cinza-suja
sobre o fundo azulado.

```css
--nc-shadow-sm: 0 1px 3px rgba(13,33,68,.06), 0 1px 2px rgba(13,33,68,.04);
--nc-shadow-md: 0 4px 16px rgba(13,33,68,.10), 0 2px 6px rgba(13,33,68,.06);
--nc-shadow-lg: 0 8px 32px rgba(13,33,68,.14), 0 4px 12px rgba(13,33,68,.08);
```

---

## 6. Barra lateral

- Largura fixa de **256px**, `position: fixed`, altura total, rolagem própria.
- **Barra de rolagem de 3px**, polegar em `rgba(255,255,255,.1)` sobre trilho transparente. Detalhe
  pequeno e coerente: nada de scrollbar clara cortando o quase-preto.
- **Marca no topo**: versão **branca** (knockout) do brasão com a assinatura, centralizada, com
  respiro generoso.
- **Grupos** (`ATIVOS`, `OPERAÇÕES`, `ANÁLISE`): maiúsculas ~11px em `--nc-sidebar-text`, com
  chevron indicando colapso.
- **Item**: ~40px de altura, ícone de traço à esquerda, rótulo 14px.
- **Item ativo**: véu `--nc-sidebar-active` na largura toda, rótulo branco.
- Transição de largura em `cubic-bezier(0.22, 1, 0.36, 1)` — a barra colapsa.

---

## 7. Componentes

| Componente | Especificação |
|---|---|
| **Cartão de KPI** | Branco, **borda superior de 3px** na cor da semântica, rótulo em maiúsculas pequenas, valor grande na mesma cor, ícone no canto superior direito |
| **Cartão de conteúdo** | Branco, raio `lg`, borda de 1px, título 18px semibold, ação ou controle à direita |
| **Tabela** | Cabeçalho em `--nc-primary` com texto branco em maiúsculas 11px e indicador de ordenação; linhas brancas de ~60px separadas por `--nc-border-light` |
| **Célula composta** | Caixa de seleção, ladrilho de ícone (36px, raio `sm`, fundo tingido da categoria), nome semibold e código monoespaçado abaixo |
| **Pastilha de status** | Fundo suave + texto forte da semântica, ponto de 6px à esquerda, raio total |
| **Botão primário** | `--nc-accent` sólido, texto branco, raio `sm` |
| **Botão secundário** | Branco, borda `--nc-border`, texto `--nc-text` |
| **Campo e seletor** | Branco, borda, raio `sm`, ~40px de altura |
| **Segmentado** | Ativo com fundo `--nc-accent` e texto branco (`7d/30d/90d`, `25/50/100`) |
| **Paginação** | Quadrados de raio `sm`; página atual em `--nc-accent` |
| **Avatar** | Quadrado de raio `sm` em listas, círculo em tabela; iniciais brancas sobre cor sólida |
| **Barra de proporção** | Trilho `--nc-border-light`, preenchimento na cor da série, raio total, ~6px |

### Gráficos

- **Rosca** com furo grande, total ao centro e unidade abaixo em maiúsculas pequenas.
- **Legenda em lista**, não em volta do gráfico: ponto, rótulo, valor e percentual à direita. A
  legenda carrega o dado; o gráfico carrega a proporção.
- **Série temporal** em área, linha tracejada, preenchimento de baixa opacidade, grade só horizontal.

---

## 8. Contraste medido, e três limites herdados

Medido com a fórmula da WCAG, convertendo em canvas. A maior parte da paleta é folgada:

| Par | Razão |
|---|---|
| `text` / superfície | 17,85 |
| `text` / fundo de página | 15,65 |
| `text-2` / superfície | 7,58 |
| Sidebar: rótulo / fundo | 7,38 |
| Sidebar: branco / item ativo | 16,60 |
| Branco / `accent` (botão) | 6,47 |
| Branco / `primary` | 15,93 |
| Pastilhas (sucesso, aviso, perigo, info) | 4,57 a 5,49 |

**Três pares não passam, e vale decidir o que fazer com eles antes de aplicar:**

**1. `--nc-text-muted` (`#94a3b8`) rende 2,56 sobre branco** e 2,25 sobre o fundo de página. Reprova
para texto por larga margem. No sistema de origem ele carrega timestamp, placeholder e código - ou
seja, informação de verdade.
*Proposta:* usar `--nc-text-2` (`#475569`, 7,58) para qualquer texto operacional e restringir o
`muted` a elemento decorativo. No app de campo isso é regra desde o início, com teste.

**2. `--nc-gold` (`#c4943e`) rende 2,74** tanto como texto sobre branco quanto como fundo com texto
branco. Não pode carregar texto em nenhuma das duas direções.
*Proposta:* dourado só como preenchimento decorativo - fio de 3px, ponto, ícone grande. Para texto
ou ícone pequeno sobre claro, escurecer (o `gold-700` do outro documento, `#a88a52`, também não
passa; precisaria de algo perto de `#8a6f3f`).

**3. Bordas rendem 1,65 (`--nc-border`) e 1,23 (`--nc-border-light`).** Como separador decorativo
está certo; como contorno de controle, a WCAG 1.4.11 pede 3:1.
*Proposta:* manter para fios e cartões, e usar uma borda mais escura onde a borda **é** o contorno
do controle. No app de campo isso já é assim, e por um motivo extra: borda clara desaparece sob sol
direto no pátio.

Nenhum dos três é motivo para recusar a paleta - são pontos a tratar na aplicação.

---

## 8b. Decisões tomadas (31/07/2026)

| Questão | Decisão |
|---|---|
| Tipografia | **Manter Inter.** Onest e JetBrains Mono não entram. |
| Os três pares que reprovam | **Corrigir onde reprova**, conforme as propostas da seção 8. |
| App de campo | **Paleta e forma sim, densidade não** - adota cor, raio, sombra e marca; mantém corpo de 17px e alvos de 48/56px. |
| Temas do app de campo | **Manter os três**, rederivando escuro e noturno do novo par de azuis. |

Duas consequências que valem registro:

**Inter no lugar de Onest** é divergência deliberada da referência. É defensável - a Inter já está
auto-hospedada, é da mesma família geométrica sem serifa, e evita duas fontes novas no bundle de um
app offline. O custo é que os dois sistemas não ficam tipograficamente idênticos.

**Sem fonte monoespaçada**, código de ativo e matrícula perdem o alinhamento que a JetBrains Mono
daria. `font-variant-numeric: tabular-nums` na Inter cobre o alinhamento de dígito, que é o que
mais importa aqui; letras em código alfanumérico continuam de largura variável.

## 9. O que muda no Notre Guard

Aplicar isto reverte a passada anterior, feita sobre o `docs/design-system.md` (navy `#013d53` +
dourado):

| | Hoje no Notre Guard | Passa a ser |
|---|---|---|
| Primária | `#013d53` | `#0d2144` (estrutura) + `#1752d9` (ação) |
| Sidebar | `#013d53` com fio dourado no ativo | `#071120` com véu azul translúcido |
| Fundo de conteúdo | Branco | `#edf0f7` |
| Fonte | Inter | **Onest** (+ JetBrains Mono) |
| Raio | 6/8/12/16 | 4/6/10/14/20 |
| Anel de foco | Dourado 2px | A definir — o dourado perde a base |

O trabalho por superfície:

1. **Painéis** - trocar a primária no `FilamentColor`, refazer o CSS da barra lateral, trocar a
   fonte, ajustar raio e sombra.
2. **App de campo** - o tema claro migra direto. Os temas **escuro e noturno precisam ser
   rederivados**: foram construídos a partir do navy `#013d53`, e o novo par de azuis muda todas as
   inversões. Contraste tem de ser remedido nos três.
3. **Fontes** - auto-hospedar Onest e JetBrains Mono, como a Inter já está. Sem isso o app de campo
   perde a tipografia offline.
4. **Marca** - a versão branca passa a ser a principal, porque a sidebar é escura.

O que **não** muda: a marca em `currentColor` servindo todos os fundos, os alvos de toque de
48/56px do app de campo, e a regra de que vermelho preenchido pertence à emergência.

---

## 10. O que ainda não dá para afirmar

- **Estado de foco** - nenhuma captura mostra foco por teclado, e não há token para ele. Precisa ser
  definido; o `accent` é o candidato natural.
- **Hover e pressionado** - inferidos das capturas, não observados nos tokens.
- **Tema escuro dos painéis**, se existir no sistema de origem.
- **Comportamento responsivo** - as capturas são de tela larga; a barra tem transição de largura,
  então ela colapsa, mas não sei em que ponto nem para qual largura.
