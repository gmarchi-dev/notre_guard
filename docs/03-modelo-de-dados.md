# 03 - Modelo de dados

Estado da Fase 1. As migrations vivem em `database/migrations/` com prefixo `2026_07_25_`.

## Cadastros

| Tabela | Model | Observações |
|---|---|---|
| `units` | `Unit` | Unidade/site. `code` entra na numeração do RDO e das ocorrências. |
| `posts` | `Post` | Posto de serviço. Tem `qr_token` próprio - é o QR da assunção de posto. |
| `checkpoints` | `Checkpoint` | Ponto de controle. `qr_token`, `nfc_uid` opcional, `radius_m` de tolerância. |
| `patrol_routes` | `PatrolRoute` | Roteiro de ronda. |
| `patrol_route_checkpoints` | - (pivot) | `position` e `required`. |
| `patrol_route_schedules` | `PatrolRouteSchedule` | Janelas de execução (`window_start`/`window_end` + `weekdays` json). |
| `security_guards` | `SecurityGuard` | Vigilante. `refresher_valid_until` cobre a reciclagem da Lei 14.967/2024. |
| `checklist_templates` / `checklist_items` | `ChecklistTemplate` / `ChecklistItem` | Checklist vinculável a checkpoint. |
| `incident_types` | `IncidentType` | Taxonomia hierárquica, com classificação e severidade padrão. |

## Operação (produzida em campo)

| Tabela | Model | Observações |
|---|---|---|
| `shifts` | `Shift` | Turno, da assunção ao fechamento. `chain_hash` sela a cadeia de integridade. |
| `patrols` | `Patrol` | Execução de um roteiro dentro de um turno. |
| `patrol_scans` | `PatrolScan` | Leitura de ponto. Registro imutável. |
| `checklist_responses` | `ChecklistResponse` | Resposta de item, por leitura. |
| `incidents` | `Incident` | Ocorrência, numerada `RO NNN/AAAA` por unidade e ano. |
| `attachments` | `Attachment` | Evidência polimórfica. Nasce `pending`, vira `stored` no upload do binário. |
| `daily_reports` | `DailyReport` | RDO por unidade e data. |
| `sync_batches` | `SyncBatch` | Auditoria de cada lote de sincronização recebido. |

## Duas renomeações que não são cosméticas

**`routes` → `patrol_routes` / `PatrolRoute`.** Um model `Route` colide com a facade
`Illuminate\Support\Facades\Route` em qualquer arquivo que importe os dois, e o alias
resultante seria uma armadilha permanente.

**`guards` → `security_guards` / `SecurityGuard`.** O Eloquent do Laravel 13 tem
`Model::guard(array $guarded)`, então uma relação chamada `guard()` gera erro fatal de
assinatura incompatível. Além disso "guard" já significa outra coisa no Laravel (auth guard).
As relações se chamam `securityGuard()`, e a chave estrangeira é `security_guard_id`.

## Convenções de sincronização

Toda tabela alimentada pelo campo tem:

- **`uuid` único**, gerado no dispositivo. É a chave de idempotência: reenviar o mesmo evento
  nunca duplica.
- **`occurred_at` e `received_at`** separados - relógio do aparelho e relógio do servidor.
  Relatórios usam `occurred_at`; a auditoria compara os dois e sinaliza divergência.
- **`deviations` (json)** com as marcas de desvio. Nada é recusado em campo: GPS fora do raio,
  horário fora da janela ou ordem trocada entram como desvio para o supervisor analisar.
  As constantes estão em `PatrolScan::DEVIATION_*`.

Registros de campo são **append-only**. Não há edição destrutiva - correção é um evento novo
vinculado ao original.

## Tokens de QR Code

`Post` e `Checkpoint` usam a trait `App\Models\Concerns\HasQrToken`: 24 caracteres aleatórios
gerados no evento `creating`, com prefixo de tipo no payload (`CP:` / `POST:`). O token é
opaco de propósito - um código sequencial permitiria "passar" por um ponto sem estar nele.

> Cuidado ao escrever seeders: `WithoutModelEvents` desliga o evento `creating` e os registros
> nascem sem token. Existe teste de regressão para isso em `tests/Feature/SeedDataTest.php`.
