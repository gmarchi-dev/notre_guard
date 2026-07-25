# Notre Guard — instruções do projeto

Sistema de gestão de segurança patrimonial (ronda eletrônica, RDO, ocorrências) do Colégio
Notre Dame Campinas. Ver [README.md](README.md) e
[docs/01-plano-de-implantacao.md](docs/01-plano-de-implantacao.md).

**Estado atual:** Fases 1 e 2 prontas. Laravel 13 + **Filament 4** (não 3 — ver
`docs/04-decisoes-tecnicas.md`), painel com cadastros e telas operacionais, API de
sincronização e PWA de campo offline-first. 34 testes passando. Falta o RDO e os dashboards
(Fase 3).

Antes de mexer no modelo de dados ou em resources, ler `docs/03-modelo-de-dados.md` e
`docs/04-decisoes-tecnicas.md` — há duas renomeações obrigatórias (`PatrolRoute`,
`SecurityGuard`) por colisão com o framework. Para mexer em campo ou sync, ler
`docs/05-api-de-sincronizacao.md` e `docs/06-pwa-de-campo.md`.

Depois de alterar `resources/js/field/**` ou `resources/css/field.css`, rodar `npm run build` —
a PWA é servida pelo build, não pelo dev server.

## Idioma

Documentação, comentários, nomes de menus e mensagens ao usuário em **português do Brasil**.
Nomes de tabelas, colunas, classes e rotas em **inglês** (padrão Laravel), como no Portal de
Segurança Digital.

## Ambiente

Windows 11 + Laravel Herd + MySQL standalone, em `C:\claude\notre_guard` (ao lado de
`C:\claude\seguranca_digital`). Detalhes em `docs/02-ambiente-desenvolvimento.md`. Em
PowerShell, o `^` de constraints do Composer é engolido: usar Bash ou o `composer.phar`
direto. Banco próprio (`notre_guard`), separado do Portal.

## Decisões arquiteturais firmadas

Não reabrir sem o usuário pedir:

- **PWA offline-first** para o app do vigilante, não app nativo. O Filament é só para o
  backoffice desktop.
- **QR Code é o mecanismo primário** de checkpoint. Web NFC só existe no Chrome/Android, e
  o NFC é opcional.
- **Eventos de campo são append-only e imutáveis**, com UUID gerado no dispositivo e
  endpoint de sync idempotente pelo UUID. Correção é evento de retificação, nunca edição.
- **Nenhuma validação bloqueia o registro em campo.** GPS fora do raio ou horário fora da
  janela viram *desvio marcado*, analisado pelo supervisor — o app nunca recusa.
- **Registro de ponto eletrônico legal está fora de escopo** (Portaria 671/2021). A assunção
  de posto é presença operacional, e a UI precisa dizer isso.
- **Localização só é coletada durante ronda ativa.** Nunca rastreamento contínuo.
- Banco e repositório separados do Portal; auth Google e linguagem visual compartilhados —
  mas **não componentes de código**: o Portal é Filament 3, este projeto é Filament 4.
- **Turnos e rondas não são criáveis nem editáveis pelo painel.** Nascem no dispositivo e são
  somente leitura. Permitir edição transformaria aderência de ronda em número negociável.
- **Aparelho corporativo**, um por posto/turno. Token Sanctum por aparelho; revogar um celular
  perdido não afeta contas.
- Tudo que entra no IndexedDB passa por `plain()` em `db.js`: o Alpine entrega `Proxy` e o
  IndexedDB não consegue cloná-los.
- Escopo por unidade: usar o trait `ScopedToUnit` e sobrescrever `applyUnitScope()`. **Nunca**
  sobrescrever `getEloquentQuery()` chamando `Resource::getEloquentQuery()` — perde o late
  static binding e quebra a listagem com erro 500.
- Criar login e trocar perfil é só de administrador (`UserResource::canAccess()`).
