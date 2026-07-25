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

## Pendências conhecidas

- O painel ainda não filtra por unidade. `unit_manager` hoje enxerga todas as unidades — falta
  o escopo por unidade antes de dar acesso a gestor de unidade.
- Não há autenticação Google Workspace ainda: login do painel é e-mail/senha local.
- **RDO ainda não existe** (Fase 3): a tabela `daily_reports` está criada, mas não há
  fechamento, PDF nem dashboard de aderência.
- **O vigilante não tem tela de fila.** Vê o contador de pendentes, mas não a lista nem os
  registros rejeitados por falha permanente.
- Botão de pânico, alerta de inatividade e controle de recursos seguem fora (Fase 5).
