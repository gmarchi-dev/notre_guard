# 04 — Decisões técnicas

Registro de decisões tomadas durante a implementação, com o motivo. Ordem cronológica.

## Estrutura organizacional (informada em 26/07/2026)

**A supervisão de segurança é central.** Existem duas unidades — matriz e filial — e a
supervisão/gerência é a mesma para ambas.

Isso tem consequências diretas no que faz e no que não faz sentido construir:

- **Não há necessidade de escopo de escrita por unidade.** O `ScopedToUnit` continua no código e
  testado, mas hoje é uma válvula de segurança adormecida: nenhum usuário em operação usa o
  perfil `unit_manager`. Não investir em restringir os selects dos formulários enquanto esse
  arranjo se mantiver.
- O filtro de unidade do painel é **ferramenta de comparação** entre matriz e filial, não
  fronteira de acesso.
- O RDO é por unidade e data, então a supervisão fecha **dois RDOs por dia**. Se isso virar
  atrito na rotina, a saída é um fechamento em lote ou uma visão consolidada — nenhuma das duas
  existe hoje.
- Notificação de ocorrência grave chega para a supervisão independentemente da unidade, que é o
  comportamento correto neste arranjo.

## 2026-07-25 — Filament 4 em vez de Filament 3

O plano previa Filament 3 para manter paridade com o Portal de Segurança Digital. Não é
possível: Filament 3 exige `illuminate/auth ^10|^11|^12` e `symfony/console ^6|^7`, enquanto
este projeto roda Laravel 13 (symfony/console 8). O Composer recusa a resolução.

**Consequência prática:** a API de resources do Filament 4 é diferente — formulário e tabela
ficam em classes separadas (`Schemas\XForm`, `Tables\XsTable`), `Filament\Schemas\Components`
substitui parte de `Filament\Forms\Components`, e ações vêm de `Filament\Actions`. Código de
tela não é copiável entre o Portal e o Notre Guard. O "design system compartilhado" previsto no
plano vale como especificação visual, não como biblioteca de componentes.

## 2026-07-25 — `Route` → `PatrolRoute`, `Guard` → `SecurityGuard`

Colisões reais, não preferência de estilo. Detalhe e motivo em
[03-modelo-de-dados.md](03-modelo-de-dados.md). A segunda só apareceu como erro fatal em
runtime (`Declaration of App\Models\Shift::guard() must be compatible with
Illuminate\Database\Eloquent\Model::guard(array $guarded)`), depois de as migrations já
estarem escritas.

## 2026-07-25 — Folhas de QR Code exigem sessão

As rotas `qr.*` ficam atrás do middleware `auth`. O token impresso é o que autentica a
passagem pelo ponto: se a folha fosse pública, qualquer pessoa poderia gerar as imagens e
"bater" a ronda sem sair do lugar.

Efeito colateral que virou correção: a aplicação não tem rota nomeada `login` (o login é o do
painel Filament), então visitante em rota `auth` estourava HTTP 500. Resolvido com
`redirectGuestsTo(fn () => route('filament.admin.auth.login'))` em `bootstrap/app.php`.

## 2026-07-25 — `User` com defaults no model

`protected $attributes = ['role' => ..., 'active' => true]`. Sem isso, um `User` criado em
código fica com `active` nulo até ser recarregado do banco e é barrado no painel por
`canAccessPanel()` — falha silenciosa e difícil de diagnosticar. O default do banco não
resolve, porque a instância em memória não o conhece.

## 2026-07-25 — Turnos e rondas não são criáveis pelo painel

`ShiftResource::canCreate()` e `PatrolResource::canCreate()` retornam `false`. Esses registros
nascem no dispositivo do vigilante; permitir criação manual no painel abriria caminho para
"consertar" aderência de ronda à mão, que é exatamente o que o sistema existe para impedir.

## 2026-07-25 — Aparelho corporativo, um por posto

Decisão do usuário. Simplifica o modelo: `devices` guarda o aparelho, o token Sanctum é por
aparelho, e revogar um celular perdido não afeta nenhuma conta. Se fosse BYOD seria preciso MDM,
política de uso assinada e baseline técnico por modelo.

## 2026-07-25 — Tudo que entra no IndexedDB passa por `plain()`

O Alpine entrega objetos embrulhados em `Proxy`, e o `structuredClone` do IndexedDB não
consegue cloná-los: `DataCloneError` ao salvar o turno. Em vez de lembrar disso em cada
chamador, `db.js` normaliza na entrada (`putCache`, `enqueue`, metadados de blob). O blob em si
vai cru — ele não é JSON.

Sintoma se voltar a acontecer: o botão "Assumir" não faz nada e o console fica silencioso,
porque a exceção morre dentro do handler assíncrono.

## 2026-07-25 — Divergência de relógio medida no envio

`clock_skew` compara `client_sent_at` com a hora do servidor, não `occurred_at`. Um evento com
horário de três dias atrás pode ser apenas uma fila que ficou sem rede — marcar isso como desvio
puniria justamente o caso que o sistema existe para suportar.

## 2026-07-25 — Fichas de campo são somente leitura no painel

`EditPatrol` e `EditShift` não têm ação de salvar nem de excluir, e os schemas usam `TextEntry`.
Na ocorrência, os fatos relatados são somente leitura e só a análise da supervisão (`status`,
`review_notes`) é editável. Um formulário editável ali transformaria aderência de ronda em número
negociável e destruiria o valor probatório do RDO.

## 2026-07-25 — Escopo por unidade via trait, com ponto de extensão

`App\Filament\Concerns\ScopedToUnit` filtra o resource pela unidade do gestor logado. Admin e
supervisão têm `unit_id` nulo e enxergam tudo; o gestor de unidade só vê a dele.

Resources com regra própria sobrescrevem **`applyUnitScope()`**, nunca `getEloquentQuery()`.
Motivo concreto: chamar `Resource::getEloquentQuery()` estaticamente perde o late static
binding e o Filament tenta instanciar a classe `App\Models\` (vazia), com erro 500 na listagem.

Casos especiais em uso: `UnitResource` filtra por `id` (a própria unidade),
`SecurityGuardResource` por `default_unit_id`, e `ChecklistTemplateResource` inclui os modelos
globais (`unit_id` nulo), que valem para todas as unidades.

## 2026-07-25 — Gestão de contas restrita a administrador

`UserResource::canAccess()` exige `isAdmin()`. Supervisão e gestor de unidade operam o sistema
mas não criam login nem trocam perfil de ninguém.

O cadastro de vigilante cria o login na própria tela (`createOptionForm` no select de usuário),
e o select só oferece usuários com perfil `guard` que ainda não têm cadastro — um login por
vigilante. Sem isso, cadastrar alguém dependia de `tinker`.

## 2026-07-25 — Tela de fila para o vigilante

A PWA mostra o que está no aparelho: pendentes, em retentativa e **recusados**, com o motivo
que o servidor devolveu. Os recusados têm ação de tentar de novo e de descartar.

Registro recusado por falha permanente não sobe sozinho e não pode sumir em silêncio: se o
vigilante não consegue ver o que não chegou, ele deixa de confiar no aplicativo e volta para o
papel — que é o risco número um do projeto.

## 2026-07-25 — RDO: rascunho é espelho, fechado é fotografia

Detalhes em [07-rdo.md](07-rdo.md). Três decisões que sustentam o valor do documento:

1. **Fechar exige turnos encerrados na data.** Do contrário o RDO nasceria desatualizado.
2. **Selo SHA-256 do conteúdo**, comparado depois para detectar registros que chegaram após o
   fechamento — o caso real do aparelho que passou dias sem rede.
3. **Reabertura é de administrador** e invalida selo e PDF. É o caminho honesto quando chega
   registro atrasado; editar o documento fechado não é.

Bug encontrado pelos testes e digno de nota: o cast `date` grava `'Y-m-d H:i:s'`, então buscar
por `where('report_date', '2026-07-25')` não casa e cria um segundo RDO da mesma data. Usar
`whereDate()`.

## 2026-07-25 — Painel operacional com filtros no servidor

Detalhes em [08-dashboard.md](08-dashboard.md). O que não pode ser afrouxado:

- **Ausência de dado é `null`, não zero** — em aderência e na série diária. Zero por cento e
  "não houve ronda" levam a decisões diferentes.
- **O filtro de unidade não é segurança.** `ReadsDashboardFilters` reforça a unidade do gestor
  no servidor; o que vem do Livewire é sugestão.
- Widgets registrados explicitamente no `AdminPanelProvider`, sem `discoverWidgets()`: a
  composição do painel é decisão, não varredura de diretório.

## 2026-07-25 — Notificações só para o que é grave

Detalhes em [09-notificacoes.md](09-notificacoes.md). O aviso sai na criação da ocorrência, por
observer, e só para gravidade alta/crítica ou tipo marcado como `notify_supervision`. Ocorrência
rotineira entra no RDO e não acorda ninguém: ruído constante faz a supervisão ignorar o sistema,
e aí o alerta que importa também se perde.

Duas armadilhas encontradas: `Illuminate\Bus\Queueable` já declara `$afterCommit` (redeclarar
com tipo é erro fatal de composição de trait), e a ação da notificação do Filament vem de
`Filament\Actions\Action` — `Filament\Notifications\Actions\Action` não existe na v4.

**Exige worker de fila rodando.** Sem `queue:work`, os avisos ficam parados na tabela `jobs`.

## 2026-07-26 — Retenção e expurgo automatizados

Detalhes em [10-lgpd-e-retencao.md](10-lgpd-e-retencao.md). As decisões que não podem ser
afrouxadas:

- **Evidência vencida perde o binário, não a linha.** O hash SHA-256 fica: prova o que existiu
  sem guardar o conteúdo.
- **Turno aberto nunca é expurgado**, mesmo antigo. É sintoma de problema, não dado vencido.
- **Cada execução é registrada em `retention_runs`**, inclusive simulação. A LGPD exige poder
  demonstrar a eliminação, e sem histórico isso é afirmação sem prova.
- Evidências são polimórficas e **não têm cascata**: precisam ser removidas antes do turno, ou
  sobram linhas apontando para ids inexistentes.

Interação sutil: o expurgo quebraria a verificação de selo do RDO, que recalcula o conteúdo e
compararia com vazio. Daí a coluna `daily_reports.data_purged_at`, respeitada por
`hasLateRecords()` e por `buildOrUpdate()`.

## 2026-07-26 — Google Workspace pronto, mas desligado

A pedido do usuário: implementado e verificado, `GOOGLE_AUTH_ENABLED=false`. Detalhes e roteiro
de ativação em [11-autenticacao-google.md](11-autenticacao-google.md).

- **Não provisiona conta automaticamente.** Sistema de segurança patrimonial: criar acesso para
  quem clicar no botão abriria a operação para a escola inteira.
- **Vínculo pelo `google_id`**, não só pelo e-mail — endereço institucional é reaproveitado, e
  sem isso o sucessor herdaria o acesso do antecessor.
- **Senha continua funcionando** com o Google ligado: adoção faseada, e caminho de entrada se o
  OAuth falhar.
- O botão entra por render hook, não por subclasse da página de login do Filament.
- O aplicativo de campo **não** usa Google: matrícula e senha no aparelho corporativo, para não
  deixar sessão Google aberta entre turnos.

## 2026-07-26 — Pânico e inatividade (Fase 5, parte 1)

Detalhes em [12-seguranca-do-vigilante.md](12-seguranca-do-vigilante.md). As decisões que
sustentam o funcionamento:

- **Pânico não passa pela fila de sincronização.** Endpoint dedicado, entrega imediata; a fila é
  só contingência, com o mesmo uuid para não duplicar.
- **A notificação de pânico não é `ShouldQueue`.** Depender do worker estar no ar tornaria o
  botão inútil justamente quando importa.
- **Falha no aviso não desfaz o alerta.** O acionamento fica gravado e visível na tela.
- **Inatividade vigia rondas, não turnos.** Portaria sem leitura por horas é normal; alertar
  nisso geraria ruído e mataria a credibilidade do alerta.
- **Um alerta de inatividade por ronda**, por índice único `(kind, patrol_id)` — senão o
  agendador criaria um a cada 5 minutos.
- Silêncio medido por `occurred_at`, não `received_at`: leitura atrasada por falta de rede não é
  inatividade.

## 2026-07-26 — Controle de chaves e painel da portaria

Detalhes em [13-controle-de-chaves.md](13-controle-de-chaves.md).

- **Painel separado em `/portaria`**, não um recorte do administrativo. O vigilante precisa
  entrar, e o administrativo tem a operação inteira das duas unidades.
- **Login por matrícula** nesse painel: mesma credencial do app de campo, para não criar uma
  segunda credencial por pessoa.
- **Situação da chave é derivada**, não é coluna. Status materializado precisaria ser mantido em
  sincronia com os empréstimos, e é aí que o livro começa a mentir.
- **Uma linha por cópia física** da chave, porque a portaria pendura cada uma no seu gancho.
- `lockForUpdate` na liberação: sem ele, dois registros simultâneos colocariam a mesma chave com
  duas pessoas.
- No RDO, "em aberto" é medido no fim do dia do relatório — um RDO de ontem não muda porque a
  chave voltou hoje.

Repetição de armadilha já vista: `KeyHolder` e `KeyItem` criados em código ficavam com `active`
nulo e a liberação era recusada. **Default de boolean vai no `$attributes` do model**, não só no
banco — mesma correção feita antes em `User`.

## Pendências conhecidas

- A autenticação Google está pronta mas **desligada** — falta criar as credenciais OAuth no
  domínio. Enquanto isso o painel usa e-mail e senha.
- **Não há push no celular** — só sino no painel e e-mail. Falta chave VAPID e assinatura de
  push na PWA.
- O RDO é gerado sob demanda. Não há job agendado criando o rascunho do dia anterior
  automaticamente.
- Não há worker de fila configurado como serviço; em produção isso precisa ser supervisionado.
- O expurgo depende do agendador do Laravel ativo (`schedule:run` no cron). Sem ele, os prazos
  de retenção não são cumpridos.
- Falta o **aceite registrado** do termo de transparência na primeira instalação do aplicativo
  (o aviso aparece, mas o aceite não é gravado).
- O dashboard não tem exportação. Levar os números para fora exige o PDF do RDO.
- ~~O escopo por unidade cobre só a leitura.~~ **Deixou de ser pendência em 26/07/2026:** a
  supervisão é central (ver a seção de estrutura organizacional abaixo), então não existe um
  gestor restrito a uma unidade para quem essa brecha importe.
- **Alertas de segurança sem escalonamento**: se ninguém reconhecer, não há segundo aviso nem
  contato alternativo. A lacuna mais séria da Fase 5.
- **Armamento está fora do escopo** e **rádio segue em avaliação** — reavaliar depois do piloto.
- Controle de chaves não tem autorização permanente: registra toda retirada, mas não valida se
  aquela pessoa poderia levar aquela chave.
- **A Fase 0 (levantamento com a equipe) continua pendente.** Unidades, postos, rotas e a
  taxonomia de ocorrências que estão no sistema são um ponto de partida, não o levantamento
  real.
