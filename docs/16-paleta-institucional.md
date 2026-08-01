# 16 - Paleta institucional

Definida em 31/07/2026. **Substitui** `docs/design-system.md` e
`docs/15-design-system-extraido.md` como fonte de cor do sistema.

## Âncoras de marca

| | Hex |
|---|---|
| Navy institucional | `#013d53` |
| Dourado institucional | `#cfb276` |

## Rampas

| | | | | | | | |
|---|---|---|---|---|---|---|---|
| **Teal** | `#D0EBFC` | `#7ACBF7` | `#36A6DB` | `#2983AD` | `#1B5E7E` | `#0D394E` | `#031823` |
| **Quente** | `#F2EDE6` | `#D5C4AC` | `#AE9E88` | `#8A7E6B` | `#655B4E` | `#3F3930` | `#1D1A14` |
| **Dourado** | `#FEFBF5` | `#F0CE90` | `#CDA75F` | `#A5864B` | `#7C6437` | `#524222` | `#2C220F` |
| **Vermelho** | `#FCF3F2` | `#F0C2BA` | `#E68D7F` | `#DB5446` | `#A43D32` | `#6C261E` | `#39100C` |
| **Cinza** | `#EDEFF0` | `#C2C9CC` | `#9BA3A8` | `#7B8285` | `#595F61` | `#383B3D` | `#191B1C` |

Os tons médios das rampas de teal e dourado (`#0D394E` e `#CDA75F`) são **vizinhos das âncoras, não
idênticos**. As âncoras mandam onde a marca aparece; as rampas dão os degraus.

---

## O caráter da paleta

**Neutro quente, não cinza.** O fundo do tema claro é `#F2EDE6` e o cartão é branco. É o que dá a
leitura de papel e distingue o sistema de um painel administrativo genérico — a rampa de cinza
existe, mas fica para gráfico e dado, não para a casca.

**Dourado com parcimônia.** Ele marca "onde você está" na barra lateral e serve de fio de
assinatura. Não carrega informação.

**Teal escuro como estrutura.** `#031823` na barra lateral, `#013d53` no cabeçalho de tabela e nas
ações. É o que amarra os dois como uma coisa só.

---

## Três decisões que a medição impôs

**1. O dourado de marca não carrega texto — nem recebe.** `#cfb276` rende **2,2** contra branco nas
duas direções. Ele é preenchimento decorativo: fio, ponto, ícone grande. Onde é preciso um dourado
legível, entra o degrau escuro da rampa, `#7C6437` (5,62 sobre branco).

**2. O anel de foco usa o dourado escuro, não o de marca.** Mesma razão. Mede 5,62 sobre branco e
4,83 sobre o neutro quente. O anel é desenhado **fora** do elemento pelo `outline-offset`, então o
contraste que importa é contra a superfície em volta, não contra o preenchimento do botão.

**3. A emergência não usa o `#DB5446` da rampa.** Com rótulo branco ele dá **3,91**. O degrau
escolhido mede 4,99 e continua sendo **6,7×** mais claro que o acento no modo noturno — que é a
invariante que o modo noturno existe para proteger.

## Uma cor fora do material fornecido

**A rampa não traz verde**, e estado de sucesso precisa de um: "conforme" é a resposta mais dada do
aplicativo, e o RDO marca ronda completa. Foi derivado `#2F6B4C` (claro) / `#6FBF95` (escuro), no
mesmo registro abafado das outras rampas. É a única cor que não veio do material, e está marcada no
código onde aparece.

Se preferir outro verde — ou usar o teal como "ok" e não ter verde nenhum —, é um token.

---

## Contraste medido

**Zero falhas nos três temas do app de campo.** Pior razão por tema:

| Tema | Pior razão |
|---|---|
| Claro | 3,42 |
| Escuro | 4,14 |
| Noturno | 3,59 |

No painel: botão primário **6,68**; barra lateral com rótulo inativo em **12,36**, ícone em 8,05,
item ativo em 11,71 e fio dourado em 5,73.

Método: cada cor é pintada num canvas 1×1 para forçar a conversão a sRGB, e a razão sai da fórmula
da WCAG. Ler os números do `getComputedStyle` direto daria resultado errado, porque o navegador
devolve `oklch()` computado.

---

## Onde cada superfície pega a paleta

| Superfície | Como |
|---|---|
| App de campo | `resources/css/field/tokens.css` — três temas, o único arquivo com cor literal |
| Painel administrativo | `FilamentColor` (primária) + `resources/views/filament/identidade.blade.php` |
| Painel da portaria | idem |
| Marca e ícones | `currentColor` — um arquivo serve todos os fundos |

Os ícones da PWA já estavam com `#013d53` no fundo, então não precisaram ser regerados.

## Invariantes travadas por teste

- As âncoras aparecem no app de campo e nos dois painéis.
- O dourado de marca **nunca** é usado como anel de foco.
- O neutro é quente, não cinza, nas três superfícies.
- O item ativo da barra lateral se distingue por **mais que cor** (fio dourado).
- Vermelho preenchido continua exclusivo da emergência.
- A marca herda `currentColor` — fixá-la a deixaria invisível dentro da barra escura.

## O que continua sem base para afirmar

**Nada disso foi visto com os olhos.** Todos os números são medição via navegador; a composição — se
o neutro quente respira bem, se o dourado está discreto na medida — depende de alguém abrir as
telas.
