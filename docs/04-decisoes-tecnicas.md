# 04 — Decisões técnicas

Registro de decisões tomadas durante a implementação, com o motivo. Ordem cronológica.

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

## Pendências conhecidas

- Não há autenticação Google Workspace ainda: login do painel é e-mail/senha local.
- **Não há push no celular** — só sino no painel e e-mail. Falta chave VAPID e assinatura de
  push na PWA.
- O RDO é gerado sob demanda. Não há job agendado criando o rascunho do dia anterior
  automaticamente.
- Não há worker de fila configurado como serviço; em produção isso precisa ser supervisionado.
- O dashboard não tem exportação. Levar os números para fora exige o PDF do RDO.
- O escopo por unidade cobre a **leitura**. Um gestor de unidade ainda consegue criar registros
  para outra unidade escolhendo-a no formulário — falta restringir as opções dos selects.
- Botão de pânico, alerta de inatividade e controle de recursos seguem fora (Fase 5).
- **A Fase 0 (levantamento com a equipe) continua pendente.** Unidades, postos, rotas e a
  taxonomia de ocorrências que estão no sistema são um ponto de partida, não o levantamento
  real.
