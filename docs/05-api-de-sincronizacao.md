# 05 - API de sincronização

Base: `/api/v1`. Autenticação por token Sanctum, um por aparelho.
Todas as rotas autenticadas exigem o cabeçalho `X-Device-Id`.

## Autenticação

```
POST /api/v1/auth/login
{ "registration": "VIG-001", "password": "…", "device_id": "uuid", "device_name": "Moto G - Portaria" }
→ { "token": "…", "guard": { "id", "name", "registration", "refresher_expired" } }
```

A credencial é a **matrícula**, não o e-mail: é o que o vigilante sabe de cor e o que está no
crachá. Um novo login no mesmo aparelho revoga o token anterior daquele aparelho - os outros
seguem válidos, então trocar de vigilante num celular não derruba os demais postos.

Aparelho marcado como `revoked` é recusado no login e em qualquer requisição: é assim que a
supervisão neutraliza um celular perdido sem mexer na conta de ninguém.

## Pacote do turno

```
GET /api/v1/bootstrap
→ { server_time, guard, unit, open_shift, posts, checkpoints, routes, incident_types }
```

Baixado no início do turno e guardado no IndexedDB. Depois disso o app opera sem rede.

`open_shift` é o que permite trocar de aparelho no meio do turno: o servidor é a verdade sobre
o turno aberto, e o novo celular assume o turno em curso em vez de abrir um segundo.

`checkpoints[].checklist` já vem embutido, para não haver segunda chamada no meio da ronda.
`incident_types` vem achatado como `"Pai › Filho"`: uma lista única é mais rápida de operar
com uma mão do que navegar dois níveis.

## Envio de eventos

```
POST /api/v1/sync/events
{
  "client_sent_at": "2026-07-25T18:27:59-03:00",
  "events": [
    { "uuid": "…", "type": "patrol.scan", "occurred_at": "…", "payload": { … } }
  ]
}
→ {
  "server_time", "clock_skew_seconds", "batch_id",
  "results": [ { "uuid", "status": "accepted|duplicate|failed", "code?", "retryable?" } ]
}
```

**Nunca "tudo ou nada".** Cada evento tem seu resultado, em transação própria: um evento
inválido no meio do lote não impede os outros de entrarem. O dispositivo só remove da fila o
que voltou como `accepted` ou `duplicate`.

**Idempotência pelo uuid**, gerado no aparelho. Reenviar o mesmo lote devolve `duplicate` e não
duplica nada - inclusive não incrementa de novo o contador de pontos da ronda.

**`retryable` distingue o que fazer com a falha.** `parent_missing` (o evento chegou antes do
turno ou da ronda que ele referencia) e `server_error` são retentáveis: ficam na fila. Os
demais são permanentes: saem da fila marcados como rejeitados, com o motivo preservado.

### Tipos de evento

| Tipo | Payload | Cria |
|---|---|---|
| `shift.start` | `post_id`, `latitude`, `longitude`, `accuracy_m` | `shifts` |
| `shift.end` | `shift_uuid`, `handover_notes` | sela `chain_hash` |
| `patrol.start` | `shift_uuid`, `patrol_route_id` | `patrols` |
| `patrol.scan` | `patrol_uuid`, `checkpoint_id`, `method`, `outcome`, `justification`, coordenadas, `checklist[]`, `attachments[]` | `patrol_scans` + respostas |
| `patrol.end` | `patrol_uuid` | fecha a ronda, marca `incomplete` se faltou ponto |
| `incident.report` | `incident_type_id`, `description`, `severity`, `classification`, `shift_uuid?`, `patrol_uuid?`, `attachments[]` | `incidents` |

`outcome: "skipped"` exige `justification` - sem ela o evento é recusado como falha permanente.

A numeração `RO NNN/AAAA` é alocada **no servidor**, dentro de transação com `lockForUpdate`.
No dispositivo, dois aparelhos offline na mesma unidade chegariam ao mesmo número.

## Desvios

Nenhuma condição recusa um registro de campo. O servidor aceita e marca em `deviations`:

| Marca | Quando |
|---|---|
| `no_gps` | leitura sem coordenada |
| `out_of_radius` | distância maior que o `radius_m` do ponto (Haversine) |
| `out_of_window` | fora das janelas do roteiro, já com a tolerância aplicada |
| `out_of_order` | roteiro ordenado e posição anterior à maior já lida |
| `clock_skew` | `client_sent_at` divergindo mais de 5 min do relógio do servidor |
| `skipped` | ponto declarado como não realizado |

A divergência de relógio é medida no **envio**, não no evento: um evento com horário antigo
pode ser apenas uma fila que passou dias sem rede, e isso não é desvio.

## Evidências

```
POST /api/v1/sync/attachments/{uuid}   (multipart: file, captured_at, latitude, longitude)
```

O binário sobe **separado do evento**: numa rede ruim uma foto de 3 MB não pode bloquear a fila
de registros. O evento referencia o uuid da evidência, o servidor cria a linha como `pending`, e
o upload posterior a marca como `stored` com hash SHA-256.

HTTP 409 significa que o evento que referencia a foto ainda não subiu - o dispositivo tenta de
novo depois de sincronizar os eventos.

## Auditoria

Cada lote recebido vira uma linha em `sync_batches` com totais e erros. É por onde se investiga
um aparelho que está perdendo registros.
