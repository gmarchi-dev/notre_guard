# 06 - PWA de campo

Acesso: `/campo`. Instalável na tela inicial. Aparelho corporativo, um por posto/turno.

## Arquivos

| Arquivo | Papel |
|---|---|
| `resources/views/field/app.blade.php` | Casca (~50 linhas) |
| `resources/views/field/screens/*.blade.php` | Uma tela por arquivo |
| `resources/views/field/partials/*.blade.php` | Barra de topo, rodapé, toasts, sheet, pânico, faixas |
| `resources/css/field.css` | Índice de `@layer` e `@import` |
| `resources/css/field/tokens.css` | **Único** arquivo com valor literal de cor ou medida |
| `resources/css/field/components/*.css` | Um componente por arquivo |
| `resources/js/field/app.js` | Estado, navegação e fluxo |
| `resources/js/field/{db,sync,api,scanner,geo}.js` | Lógica, sem acoplamento visual |
| `resources/fonts/inter/` | Inter variável, subset latin (48 KB) |
| `public/sw.js` | Service worker (casca offline) |

## Fluxo

Login por matrícula → assumir posto → iniciar ronda → ler pontos (QR ou manual, ou pular com
justificativa) → checklist por ponto → encerrar ronda → encerrar turno. Ocorrência e emergência
ficam disponíveis a qualquer momento.

O indicador da barra de topo e uma linha na tela inicial abrem a **fila**, que lista o que está
no aparelho: aguardando envio, tentando de novo e recusado, com o motivo devolvido pelo servidor.

### Encerrar turno e sair são coisas diferentes

**Encerrar turno** fecha o turno e mantém o vigilante logado - o aparelho é corporativo e fica com
o posto. **Sair** revoga o token e apaga o IndexedDB inteiro.

O "Sair" fica **sempre visível**, inclusive em serviço. Antes só aparecia sem turno aberto, e isso
tinha uma consequência que só aparece na operação: quem entrasse com a matrícula errada precisava
**encerrar um turno que não era dele** para conseguir sair - e aquele turno ia para o RDO como se
tivesse acontecido.

Com turno aberto, sair não decide nada sozinho. Abre uma escolha:

| Opção | O que faz |
|---|---|
| **Encerrar turno e sair** | enfileira o `shift.end`, **espera a entrega**, depois apaga a sessão |
| **Sair e deixar o turno aberto** | confirmação reforçada; o turno fica aberto até alguém encerrar pelo painel |
| **Continuar em serviço** | cancela |

Dois cuidados que valem como requisito:

- **O encerramento é entregue antes de a fila ser apagada.** Sem isso, enfileirar o `shift.end` e
  sair em seguida jogaria fora o próprio evento que acabou de ser criado. Se a entrega falhar, o
  aviso diz exatamente o que está em risco: *"Sem rede: o fim do turno e mais N registro(s)
  continuam no aparelho. Sair apaga tudo, e o turno permanece aberto no servidor."*
- **Ronda em andamento bloqueia**, mesma regra do botão de encerrar turno: fechar uma ronda por
  tabela esconderia pontos que ninguém leu.

## Sistema visual

**Alinhado ao painel administrativo.** A tipografia é a Inter, e as cores saem das mesmas escalas
OKLCH do Filament (`vendor/filament/support/src/Colors/Color.php`) - o painel e o aplicativo têm a
mesma paleta, não "um azul parecido".

**Claro e escuro.** A ronda é noturna, mas a portaria trabalha ao sol. Os dois temas convivem em
`light-dark()`, declarados uma vez só; o interruptor é o `color-scheme` do `:root`, com override
manual persistido em `localStorage['ng.theme']` e aplicado por script inline **antes** do bundle -
o Alpine carrega diferido, e um flash branco de madrugada custa a visão noturna do vigilante.

O claro usa **branco puro** e bordas mais escuras que o habitual: sob sol direto quem decide
legibilidade é luminância absoluta, e a borda "discreta" padrão simplesmente desaparece.

No escuro o botão primário **inverte** - azul claro com texto quase preto. Isso levou o contraste
de 3,6:1 para 5,5:1; escurecer o azul original teria piorado.

**Modo noturno - um terceiro tema, não um "escuro mais escuro".** O tema escuro comum é de alto
contraste: texto Slate-50 sobre Slate-950, botão primário em Blue-400 preenchido, disco do próximo
ponto em azul cheio. Lê-se muito bem, e às duas da manhã custa a visão escotópica de quem volta a
olhar o pátio - a recuperação leva minutos.

O que emite luz não é o texto, que é traço fino: são as **áreas preenchidas**. Por isso aqui os
preenchimentos se invertem (fundo escuro, rótulo colorido) em vez de o texto simplesmente escurecer.
Medido no navegador, em luminância absoluta:

| | escuro | noturno | redução |
|---|---|---|---|
| preenchimento de acento | 0,348 | 0,025 | **−93 %** |
| preenchimento de sucesso | 0,540 | 0,036 | **−93 %** |
| texto | 0,954 | 0,349 | −63 % |
| **emergência** | 0,170 | 0,170 | **0 %** |

A medição expôs algo que ninguém tinha notado: **no tema escuro o botão de pânico é um dos
elementos preenchidos mais escuros da tela** - três vezes mais escuro que o marcador de um ponto
já registrado. No modo noturno ele passa a ser, de longe, o mais claro, com quase sete vezes a
luminância do preenchimento seguinte. Não foi efeito colateral: a emergência é o único token que o
modo noturno **não** redefine, e há teste garantindo isso.

A troca é manual e permanente (`Sistema / Claro / Escuro / Noturno`), nunca automática por horário:
mudar a tela sozinho no meio do turno faz o vigilante achar que o aplicativo travou.

O controle fica **na barra de topo**, atrás de um sheet - não no conteúdo. A área central é do
turno, da ronda e da ocorrência; um ajuste de aparência no meio dela concorre com o que o vigilante
está fazendo. Em subfluxo a marca cede o lugar: a seta dá o caminho de volta e o `<h1>` logo abaixo
já diz onde se está. Sheet em vez de ciclar por toque porque são quatro opções, e ciclar obrigaria a passar
pelas outras três para chegar na desejada. O botão existe **antes do login** de propósito: escolher
o modo noturno é exatamente o que se quer fazer ao pegar o aparelho no início do turno da noite.
Há teste garantindo que o controle não volte para o conteúdo.

O `<meta theme-color>` acompanha a escolha, convertido para sRGB - os tokens são `oklch()`, que a
barra de status do iOS não lê.

**Contraste verificado por número**, não por olhômetro: todos os pares de token passam de 4,5:1 nos
três temas, medidos no navegador (ver seção de verificação). Essa medição também encontrou dois
defeitos antigos: `--border-strong` dava **2,66:1** no escuro, abaixo dos 3:1 que a WCAG 1.4.11 pede
para contorno de controle (agora 4,23), e `--divider` **não existia no fallback** para WebView sem
`light-dark()` - lá os fios internos de todo agrupamento simplesmente não eram desenhados. Ambos
viraram teste.

**Vermelho preenchido é exclusivo da emergência**, e isso é teste, não comentário. "Encerrar
turno" é uma ação destrutiva de contorno, com rótulo em vermelho - antes era um bloco vermelho da
mesma largura, 4px abaixo do botão de pânico.

**Alvos de toque:** 56px na ação primária, 48px no restante, 64px em linha de lista. O degrau de
44px foi extinto.

## Densidade e forma

Uma segunda passada, depois de o app rodar: as telas estavam corretas em alvo de toque e erradas em
peso visual. Tudo tinha 343px de largura, tudo era um bloco empilhado, e nada se distinguia de nada.

**Alvo de toque não é peso visual** - é a chave desta etapa. Toda área de toque continua em 48/56px;
o que mudou foi o desenho em volta.

| | antes | depois |
|---|---|---|
| raio do botão | 14px (proporção 0,25 contra a altura) | **12px, numa escala revista** |
| caixa de texto | 108px fixos | **76px, crescendo com o conteúdo** |
| cartão do próximo ponto | 250px | **182px** |
| "Posto / em serviço desde" | 114px, empilhado | **77px, lado a lado** |
| tela de ocorrência | 998px | **754px** |

O que mudou, e por quê:

- **Cantos revistos.** Um retângulo de 343x56 com raio 14 lê-se como laje, por mais correto que
  esteja o alvo. A primeira correção foi ao outro extremo - arredondamento total, forma de estádio -
  e ficou macia demais para uma ferramenta de trabalho. A escala parou no meio: **8 / 12 / 16 / 20**,
  com `--radius-pill` reservado ao que é pastilha por natureza (contador, segmentos de progresso,
  alça do sheet) e círculo verdadeiro só onde a forma é um círculo (emergência, botão de ícone,
  disco de posição, pontos da escala). Há teste travando a escala e proibindo forma de estádio em
  botão, pastilha de sincronia, escala de gravidade e indicador de aba.

  Os painéis administrativo e da portaria **não entram nessa conta**: usam os raios do próprio
  Filament, medidos entre 4 e 12px - já mais contidos que o app de campo. Alterá-los exigiria um
  tema Vite do Filament, com pipeline de build própria.
- **`field-sizing: content`** na caixa de texto, com piso de duas linhas e teto de 40dvh. Os 108px
  fixos eram espaço reservado para um texto que quase sempre tem uma linha. Mesma linha de base do
  resto do app (Chrome 123+); onde não existir, o campo fica fixo, não quebrado.
- **Composição horizontal.** `.facts` põe dois fatos curtos lado a lado. Era a única coluna do app
  sem nenhuma quebra horizontal, e é a quebra que dá ritmo à leitura.
- **Fim das caixas dentro de caixas.** A instrução do próximo ponto virou texto com fio à esquerda,
  em vez de superfície aninhada dentro do cartão: 24px a menos, sem perder hierarquia.
- **O botão de nova medida saiu da linha de distância** e foi para o canto do cartão. Dentro de uma
  linha de texto, ele impunha seus 48px ao bloco inteiro - 23% da altura do cartão para uma ação
  secundária.
- **A ocorrência virou um formulário contínuo.** Três seções tituladas custavam ~96px numa tela que
  já se chama "Registrar ocorrência", e duas delas tinham um campo só.
- **Hierarquia tipográfica.** O título da tela subiu de 22 para 26px: contra os 17px do corpo, a
  diferença anterior não chegava a criar níveis.

### Ícones desenhados, não digitados

Os ícones eram glifos de texto - `⌂ ◎ ✎ ↑ ⚠ ‹ ›`. Dependiam da fonte do sistema: mudavam de
desenho, de peso e de largura entre Android e iOS, e, o que mais importa, **não tinham caixa
previsível**. Agora são um sprite SVG inline (`partials/icons.blade.php`), traço de 2px em grade de
24, um só peso para o app inteiro. Inline e não arquivo externo porque `<use href>` entre
documentos não funciona sem rede.

Dois testes cobrem isso: nenhum glifo de texto volta às views, e todo `<use>` resolve para um
`<symbol>` existente - um `<use>` órfão não dá erro, apenas renderiza vazio.

### Barra inferior

O indicador da aba ativa era um traço posicionado por margem negativa, fora de fluxo e sem relação
com o alvo que marcava. Virou uma **pastilha atrás do ícone**: fica no lugar certo, acompanha o item
e dá âncora para o contador de pendências, que agora inverte de cor sobre a pastilha preenchida.

### Gravidade: de colunas para escala

O segmented de quatro colunas iguais prendia cada opção a 80px numa tela de 375 e dava às quatro
**exatamente o mesmo peso visual** - o contrário do que uma escala precisa comunicar. Virou uma fila
de pastilhas dimensionadas pelo conteúdo, cada uma com um ponto colorido que sobe de intensidade: a
rampa se lê antes do texto. Cabe em uma linha (338px de 343) e quebra sozinha em telas menores.

"Crítica" continua **sem bloco vermelho preenchido**: vermelho cheio pertence à emergência. O ponto
de 8px em vermelho forte é a rampa; o preenchimento da pastilha marcada é o vermelho suave. O teste
foi refeito para exigir exatamente isso, em vez de proibir a cor por inteiro.

### Toasts

O modelo anterior era uma **faixa de largura total**, com fundo tingido, borda de 1px e texto
colorido - o padrão de *alert* de página, não de toast. Três consequências:

1. Gastava **74px** para dizer "PC-01 registrado.", porque o botão de fechar impunha seus 48px
   mesmo a um aviso que some sozinho em quatro segundos.
2. O fundo tingido carregava a semântica, então o texto precisava ser colorido também - dois canais
   gastos na mesma informação.
3. Esticado de borda a borda, competia com o conteúdo em vez de flutuar sobre ele.

Agora é uma superfície elevada neutra, **dimensionada pelo conteúdo** (191px para o aviso curto,
contra 288), com a cor concentrada no ícone e o texto de volta ao contraste máximo do tema. O botão
de fechar só existe no aviso que **persiste**; o que some sozinho não pede decisão nenhuma. Altura
do aviso de sucesso: **46px**, contra 74.

Contraste medido nos três temas: texto entre 6,75 e 18,4; ícone entre 5,86 e 10,1.

#### Dois defeitos que a medição encontrou

Com a pilha em `bottom: 0`, o toast ficava **por cima do dock**. Medido com `elementFromPoint`, as
quatro abas passavam a receber o toque do toast em vez do próprio - e um erro persistente, que por
definição não some sozinho, **travava a navegação inteira**.

Pior: um toast longo ocupa a largura toda e **cobria o botão de emergência**. Nada pode cobrir o
acionamento de socorro, nem por três segundos.

A pilha passou a ser ancorada ao dock (`bottom: calc(100% + var(--tap-row) + …)`), pelo mesmo
mecanismo do botão de emergência, com folga da altura dele. Em `boot` e `login` não há dock, então
há um segundo ponto de montagem flutuante - os dois em ramos mutuamente exclusivos, porque duas
regiões `aria-live` ativas fariam o leitor de tela anunciar cada aviso duas vezes. Há teste para as
três coisas.

### O botão de SOS

O relato era "desalinhado", e a medição achou a causa: `display: grid` com duas linhas automáticas
dentro de uma altura fixa **estica as linhas** para preencher o círculo e centra cada item na sua
faixa. O `gap: 2px` declarado aparecia como **15,4px** na tela, e ícone e rótulo flutuavam soltos em
vez de formarem um par. Virou `flex` com `justify-content: center`: vão real de 3px, conteúdo
centrado no círculo.

### O defeito que a medição encontrou

`.fieldset` tem `gap` e `.field + .field` tinha `margin-top`. **As duas regras se somavam**: o vão
projetado para 20px saía com 40, e um formulário de seis campos gastava 100px em espaço que ninguém
pediu. O espaçamento entre campos passou a ser responsabilidade só do contêiner, e há teste.

## Menos molduras

A primeira versão do redesenho usava cartão com borda para tudo: a tela inicial chegava a quatro
contêineres empilhados, e cada item de lista era uma caixa própria com 8px de respiro - seis
pontos de ronda viravam seis molduras.

Agora vale a regra oposta: **irmãos dividem um contêiner e se separam por um fio interno leve**
(`group`), e informação simples vira linha (`row`) em vez de cartão inteiro. O `card` ficou
reservado ao que é de fato um objeto destacado - o cartão do próximo ponto e os avisos
persistentes.

Campos de formulário são preenchidos e **sem borda**: já se distinguem do fundo pela superfície,
e um contorno em cada um devolveria as molduras que saíram. Erro de validação vira uma barra
lateral por `box-shadow`, não mais uma borda.

Os botões secundários também trocaram contorno por **preenchimento suave** - botão com borda é
mais uma moldura. O contorno some da interface quase por completo; só a ação principal recebe
elevação, que é o que a destaca sem desenhar nada em volta. O toque afunda o botão em vez de
encolhê-lo: com o dedo cobrindo o alvo, o deslocamento vertical é mais perceptível.

## Barra inferior

Modelo híbrido: **abas fixas para navegar, ação principal em destaque logo acima**. Navegar e agir
deixam de disputar o mesmo espaço.

Quatro abas - Início, Ronda, Ocorrência, Fila -, com a de Ronda habilitada só quando há ronda em
andamento e a de Fila mostrando o contador de pendências. A aba ativa se distingue por cor, peso e
um traço acima: cor sozinha não basta.

**Uma ação principal por tela**, de largura total: "Ler QR - PC-04", "Confirmar ponto", "Registrar
ocorrência", "Enviar agora". As secundárias - encerrar turno, encerrar ronda, sair - vivem no fim
do conteúdo, onde fazem sentido depois de percorrer a tela.

Antes eram até três blocos empilhados de largura total mais a emergência: cerca de **200px**, um
quarto da tela. Hoje a tela inicial usa **66px**, e a mais carregada, 146px.

**Nos subfluxos a barra some.** Leitura de QR e checklist não são destino, e sair de um checklist
pela metade tem de ser deliberado - nesses casos o retorno é a seta do cabeçalho, como manda o
padrão móvel. Trocar de aba com uma ocorrência já digitada pede confirmação.

**A emergência é um botão flutuante**, circular e vermelho, ancorado acima do dock. Fora da barra
de propósito: vizinho aos itens de navegação, o acionamento de socorro ficaria a um toque de
distância de "Início". É o único elemento circular e o único vermelho preenchido da interface, e
sobe junto quando existe faixa de ação.

## Casca

A grade é `auto 1fr auto` em `100dvh`, com o `<main>` como única região de rolagem.

**A coluna é `minmax(0, 1fr)`, não a coluna implícita.** Sem isso a coluna do grid fica `auto`, que
se dimensiona pelo **max-content**: um nome de posto longo somado à pastilha de sincronização
esticava a barra de topo para **552px num aparelho de 375**, e a página inteira rolava na horizontal.
O `max-width` do `.app` não impedia - quem mandava era a faixa de conteúdo do filho mais largo.
Defeito antigo, que só apareceu quando a barra ganhou um quarto item; hoje é teste.

Pela mesma razão a marca da barra nunca quebra linha: comprimida entre a pastilha e o ajuste de
aparência, ela empilhava em duas linhas e esticava a altura da barra de 48 para 71px.

## Decisões de interface

- **O próximo ponto sai da lista e vira cartão**, com o código em corpo grande e o nome completo
  quebrando em até três linhas. Quem caminha de madrugada, de luva, responde a uma pergunta só:
  para onde eu vou agora. Antes o nome era truncado com reticências - o dado que diz aonde ir era
  o primeiro a se perder.
- **A ação primária nomeia o alvo**: "Ler QR - PC-04". Sozinha, elimina boa parte da necessidade
  de consultar a lista.
- **O trilho continua listando todos os pontos**, porque ronda real não é sempre sequencial:
  forçar a ordem seria regressão funcional.
- **Sem diálogos nativos.** `confirm()` e `prompt()` somem sob o teclado em PWA instalada e não
  dizem contexto. Tudo passa por bottom sheet, que sobe da base porque é onde o polegar está.
- **A justificativa de ponto pulado** mostra qual ponto, tem campo rotulado, três motivos rápidos
  e botão travado enquanto estiver vazia. Cancelar é cancelar: gravar "pulado" sem justificativa
  seria furo de auditoria.
- **Toasts em vez de avisos no topo**: o aviso antigo empurrava o layout e, numa tela rolada, o
  vigilante não via. Sucesso some em 4s; erro persiste até ser fechado.
- **O botão voltar do Android navega** dentro do aplicativo (History API). Com turno aberto, o
  primeiro toque na raiz só avisa - sair sem querer no meio de um turno é caro.
- **Zoom liberado.** `maximum-scale=1` violava a WCAG 1.4.4. Todos os campos têm 17px, que é o que
  impede o iOS de dar zoom sozinho ao focar.

## Offline

O evento nasce no IndexedDB com uuid gerado no aparelho e só sai da fila quando o servidor
confirma aquele uuid. A sincronização roda a cada 30 s, ao voltar a rede e ao trazer o app para
frente.

O service worker cacheia **só a casca** - nunca respostas da API. Uma resposta velha de
`/bootstrap` faria o vigilante rondar com roteiro desatualizado, o que é pior que não abrir.

Se o bootstrap falhar mas houver cache, o aplicativo continua funcionando e mostra uma **faixa
persistente** dizendo há quanto tempo o roteiro foi carregado. Antes esse erro era engolido em
silêncio.

A tela de abertura tem recuperação própria: leitura do IndexedDB com prazo de 6 s e, se falhar,
as saídas "Tentar de novo" e "Entrar de novo". Antes o aplicativo abria em branco e ficava preso
para sempre.

## Acessibilidade

Foco visível em tudo, foco movido ao título a cada troca de tela, foco preso dentro de sheet e
pânico, `Escape` fecha. O checklist é um `radiogroup` de verdade, com o rótulo do item associado -
antes eram três botões soltos, e o leitor de tela anunciava "Conforme, botão" sem dizer de qual
item. Estado nunca é comunicado só por cor: o selecionado tem preenchimento **e** ícone.

## Limitações conhecidas

- **HTTPS é obrigatório.** Câmera, geolocalização e service worker não funcionam em HTTP, nem em
  `localhost` do celular. Em desenvolvimento, usar `herd secure` ou túnel.
- **NFC não está implementado.** Web NFC só existe em Chrome/Android; o QR é o mecanismo primário.
- **Notificação push exige o app instalado na tela inicial** no iOS (16.4+).
- **`light-dark()` exige Chrome 123+ / Safari 17.5+.** Em WebView anterior existe fallback via
  `@supports`, e ele é o tema **escuro** - o cenário degradado tem de ser o seguro para a ronda.
- A tela de fila lista os eventos, mas não as fotos individualmente - só o total pendente.

## Registro de ocorrência

Era a única tela que ainda parecia formulário web: `<select>` nativo com os **17 tipos achatados**
num rótulo só (`"Patrimônio › Furto ou tentativa"`), gravidade em outro `<select>` - apesar de o
checklist já ter um `segmented` para exatamente esse tipo de escolha - e a foto no fim da rolagem.
É a tela que se usa logo depois de encontrar um portão arrombado, ou seja, o pior lugar para pedir
precisão de dedo numa roda nativa.

O bootstrap passou a entregar `group` e `name` separados, além do `label` completo que o RDO usa. A
escolha virou **duas etapas** por sheet - grupo, depois tipo - com atalho para os mais registrados
naquela unidade nos últimos 90 dias (`frequent_incident_type_ids`). O atalho é **medido**: em
instalação nova a lista vem vazia e a seção some, em vez de sugerir o que ninguém usa. Cancelar na
segunda etapa volta aos grupos, não zera a escolha.

Gravidade virou `segmented`, o mesmo controle do checklist. **"Crítica" não recebe bloco vermelho
preenchido**, apesar de ser o extremo da escala: vermelho cheio pertence ao botão de emergência, e
um segundo bloco vermelho na tela rouba dele o significado. A distinção vem do contorno grosso e do
sinal `!`. Há teste para isso.

Nenhum `<select>` nativo sobrevive no app de campo - também virou invariante de teste.

## Fechar o ciclo

Uma passada de UX depois de tudo funcionar, olhando o app pelo que o vigilante **sente** e não só
pelo que ele consegue fazer.

**O fim da ronda é o pico do turno, e era um toast cinza de quatro segundos.** Virou um resumo:
pontos lidos, pulados e duração contra o previsto. Não é comemoração - é a última chance de conferir
o que vai para o RDO antes de o registro entrar na fila e sair de vista, quando qualquer correção
passa a ser conversa com a supervisão. Um resumo não oferece "Cancelar": não há o que desfazer.

**Tempo decorrido contra o previsto**, na tela de ronda. O roteiro sempre teve
`expected_duration_min` e o app nunca mostrava o tempo - "fora da janela" só aparecia no relatório
do dia seguinte, quando já não dá para corrigir o ritmo. É aviso, não erro: uma ronda pode
legitimamente demorar mais, o que não pode é o vigilante descobrir depois.

**Estados vazios com estrutura.** Eram linha de texto solta dentro de um agrupamento - lidos de
relance, não se distinguiam de um item da lista, e não dava para saber se a tela ainda carregava.
A fila vazia é caso à parte: ali vazio é a **boa** notícia, e o desenho tranquiliza em vez de
parecer falta.

### O defeito que essa passada encontrou

`remainingCount` contava os pontos **pulados** como faltando. Pular com justificativa é desfecho
registrado, não lacuna - e a tela se contradizia: o cartão dizia "Roteiro completo" (porque
`nextItem` já ignorava os pulados) enquanto o encerramento perguntava "Faltam 1 ponto?". Agora
`remainingCount` conta só o que não tem desfecho nenhum, o pulado tem estado próprio na barra de
progresso, e há teste para os dois lados.

## Retorno ao vigilante

Três informações que o servidor já tinha e o aparelho não mostrava.

**Reconhecimento do pânico.** Depois do acionamento, o app consulta `GET /api/v1/panic/{uuid}` a
cada 10 segundos, por até 15 minutos, e para assim que alguém reconhece. A faixa muda de
"Acionamento recebido às 02:11. Aguardando a supervisão reconhecer" para "Ana reconheceu às 02:14",
com vibração curta - padrão diferente do acionamento, porque é resposta, não alarme. A distinção é
o ponto: *o servidor gravou* nunca foi *alguém está indo*, e até aqui o aparelho só dizia a
primeira coisa. A rota só devolve alerta do próprio vigilante autenticado; acionamento ainda na
fila responde 404, que o app trata como "continua tentando".

**Aviso de desvio na hora da leitura.** Ao abrir um ponto, o app mede a distância até a coordenada
cadastrada (Haversine em `resources/js/field/geo.js`, mesma fórmula de `App\Support\Geo`) e, se
passar do `radius_m`, mostra faixa de aviso no checklist e pede confirmação antes de gravar. O
servidor continua marcando `out_of_radius` como sempre - a diferença é que agora o vigilante fica
sabendo enquanto ainda dá para andar até o ponto, em vez de o desvio aparecer só no RDO do dia
seguinte. **Nada é bloqueado:** recusar a confirmação apenas não grava, e a pessoa volta ao ponto.

**Distância até o próximo ponto.** O cartão AGORA mostra "a ~80 m · medido agora". Deliberadamente
não é tempo real: a distância vem da **última leitura de GPS que já teria acontecido de qualquer
forma** (uma leitura de ponto, um acionamento, o início do turno), e por isso o rótulo sempre diz
quando foi medida. Um botão ao lado faz uma medida nova a pedido.

## Privacidade

A localização é lida **pontualmente**, no momento de cada registro. Não há watch, nem polling, nem
coleta fora da ronda. O aviso na tela de login diz isso e deixa explícito que o app **não é
registro de ponto**.

Negar a permissão de localização não bloqueia nada: o registro entra com a marca `no_gps`.

A distância até o próximo ponto **não abre exceção a isso**: ela reaproveita a última medida já
feita, guardada só em memória e nunca transmitida sozinha. Mostrar distância em tempo real exigiria
`watchPosition` durante o turno inteiro, que é exatamente o rastreamento que este aplicativo
promete não fazer - e o preço seria pago em bateria e em confiança da equipe, não em código.

## Verificação

`tests/Feature/FieldAppTest.php` cobre as invariantes que ninguém percebe num diff: ausência de
`maximum-scale`, ausência de estilo inline, todo botão com classe de componente, cores literais só
em `tokens.css`, vermelho de emergência reservado, fonte auto-hospedada com `font-display: swap`,
e o service worker com poda de assets.

No navegador, via MCP: varredura de todos os interativos medindo `getBoundingClientRect()` contra
o mínimo exigido, e cálculo da razão WCAG a partir do que o navegador **efetivamente resolveu** -
pintando a cor num canvas, porque o Chrome devolve `oklch()` computado e uma leitura ingênua dos
números daria resultado errado.
