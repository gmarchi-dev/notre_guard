# 14 - Identidade visual aplicada

Como `docs/design-system.md` foi aplicado às quatro superfícies do sistema: painel
administrativo, painel da supervisão (o mesmo painel, outro perfil), painel da portaria e
aplicativo de campo.

## O que se ganhou

Antes existiam **três azuis diferentes** em três telas do mesmo colégio: o painel administrativo
usava `Color::Blue` do Filament, a portaria usava `Color::Slate`, e o app de campo usava as escalas
OKLCH do Filament copiadas à mão. Nenhum deles era a cor da instituição.

Agora há uma paleta só, com o navy `#013d53` do documento como primária nas quatro superfícies, e
teste que impede a divergência voltar.

## A marca

`img/logo_notre.svg` é o melhor dos quatro arquivos disponíveis, e foi o escolhido:

| Arquivo | Por que não |
|---|---|
| `brasão completo.png` | 505 KB, raster, cor fixa - não recolore para fundo escuro |
| `brasão branco.png` | raster e knockout: só serve sobre fundo escuro |
| `logo_colégio_notre_dame.pdf` | não é formato de web |
| **`logo_notre.svg`** | **vetor, cor única, brasão e assinatura em grupos separados** |

Dele saem quatro derivados, gerados por `scripts/gerar-marca.ps1`:

- `resources/svg/marca-completa.svg` - brasão + assinatura, para o login
- `resources/svg/marca-brasao.svg` - só o brasão, para as barras de topo
- `public/icons/notre-guard.svg` - ícone da PWA
- `public/icons/notre-guard-maskable.svg` - idem, com 22% de área segura, que é o que o recorte
  do Android exige (medido: margem mínima de 112px em 512)

**A cor literal do arquivo (`#223f51`) virou `currentColor`.** É o que permite a mesma marca servir
os quatro temas do app de campo e os dois painéis sem um arquivo por fundo - e é também o que a
harmoniza com o navy da interface, em vez de deixar dois azuis quase iguais brigando na tela.

> O `#223f51` do arquivo da marca e o `#013d53` do design system **não são a mesma cor**. Adotei o
> do design system para a interface e deixei a marca herdar dele. Se a intenção for o contrário -
> a marca manda e o design system se ajusta -, é uma linha de mudança.

Os ícones da PWA passaram de PNG para SVG: nítidos em qualquer densidade, um arquivo em vez de um
por tamanho. Os PNGs anteriores eram um escudo genérico desenhado em código, azul `#2563eb`, sem
relação com o brasão. **O `apple-touch-icon` do iOS exige PNG** - enquanto a frota for Android isso
não pesa, mas entra na lista se aparecer iPhone.

## Onde o design system foi seguido à risca

- Navy `#013d53` como primária, com a escala gerada pelo `FilamentColor` nos dois painéis.
- Semânticas `#b3423a` / `#b8873a` / `#2f7a52` substituindo os vermelhos e verdes padrão do Filament.
- Anel de foco **dourado**, 2px com offset de 2px, em `gold-700` - o documento é explícito que
  `gold-500` não tem contraste para indicar foco.
- Raio de 6-8px, e botão a 6px, "não pill".
- Inter como única família.
- Dourado com parcimônia: anel de foco e o traço de 3px no topo do painel. Nenhuma tela passa perto
  dos ~10% de área que o documento estabelece como limite.

## Onde precisou divergir, e por quê

O design system foi escrito para um sistema administrativo de desktop, de tema claro. O app de campo
roda em celular, de madrugada, com luva. Quatro pontos exigiram derivação - todos medidos:

**1. Tema escuro e noturno não existem no documento.** `navy-900` é quase preto: como preenchimento
de acento sobre fundo escuro, desapareceria. No escuro o acento **inverte** - navy claro com rótulo
em navy profundo. No modo noturno inverte de novo, para que a área preenchida pare de iluminar o
rosto de quem ronda.

**2. A borda.** O `--color-border` do documento (`#dde2e4`) virou o **fio interno** aqui, não a
borda. Borda discreta some sob sol direto, e este aplicativo é usado no pátio ao meio-dia; `--border`
é derivado mais escuro de propósito.

**3. O rótulo sobre o âmbar.** Branco sobre `warning` (`#b8873a`) dá **3,2:1** - reprova. O documento
fixa o preenchimento, não o rótulo, então o rótulo cedeu: escuro sobre âmbar, 5,16:1.

**4. A emergência.** O `danger` do documento classifica gravidade num relatório. O botão de pânico
precisa gritar mais alto que tudo na tela, e a medição do modo noturno mostrou que ele tem de ser o
preenchimento mais claro da interface. Ficou num vermelho próprio, mais saturado - e continua sendo
o **único vermelho preenchido**, o que já era teste.

Além disso, o app de campo mantém corpo de **17px** contra os 14px que o documento define para
densidade administrativa, e alvos de toque de 48/56px. Luva e pouca luz não negociam.

## Contraste, medido

Nenhuma falha nos três temas do app de campo. Pior razão por tema:

| Tema | Pior par | Razão |
|---|---|---|
| Claro | anel de foco sobre superfície | 3,07 |
| Escuro | contorno de controle sobre fundo | 4,43 |
| Noturno | contorno de controle sobre fundo | 3,55 |

Texto principal fica entre 8,3 e 17,5; rótulo sobre preenchimento, sempre acima de 4,5.

No painel da portaria, o botão primário mede **6,68:1**.

**O anel de foco usa `outline-offset`**, então é desenhado fora do elemento: o contraste que importa
é contra a superfície em volta, não contra o preenchimento do botão. Medir contra o botão é erro -
foi cometido e corrigido duas vezes neste projeto.

No modo noturno a emergência mantém **6× a luminância** do preenchimento seguinte, que é a
invariante que o modo existe para proteger.

## Como os painéis recebem a identidade

Via render hook (`resources/views/filament/identidade.blade.php`), não como tema Vite do Filament:
um tema exigiria pipeline de build própria mais `filament:assets` no deploy. O bloco cobre só o que
o `FilamentColor` não alcança - forma, foco e o dourado de assinatura. Se crescer, aí o tema se
justifica.

## Pendências

- `apple-touch-icon` em PNG, se entrar iPhone na frota.
- O traço dourado de 3px do design system é usado no topo do painel; falta decidir se ele também
  marca "aprovado" em RDO fechado e ocorrência encerrada, que é o uso semântico que o documento
  descreve.
- **Nada disso foi visto com os olhos.** Todos os números acima são medição via navegador; a
  composição - se a marca respira bem na barra, se o dourado está no lugar certo - depende de
  alguém abrir as telas.
