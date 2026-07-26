<?php

namespace App\Services\Sync;

use App\Models\Attachment;
use App\Models\Checkpoint;
use App\Models\ChecklistResponse;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\Patrol;
use App\Models\PatrolRoute;
use App\Models\PatrolScan;
use App\Models\Post;
use App\Models\SafetyAlert;
use App\Models\Shift;
use App\Services\IncidentNumberAllocator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Traduz um evento vindo do dispositivo em registros do banco.
 *
 * Todo handler é idempotente pelo uuid do evento: reenviar não duplica. Nenhum
 * handler edita registro de campo já existente — correção vem como evento novo.
 */
class EventProcessor
{
    public const TYPES = [
        'shift.start',
        'shift.end',
        'patrol.start',
        'patrol.scan',
        'patrol.end',
        'incident.report',
        // Caminho de contingência do botão de pânico: o app tenta o endpoint
        // dedicado primeiro e só cai aqui se estiver sem rede.
        'panic.alert',
    ];

    public function __construct(
        private readonly DeviationDetector $deviations,
        private readonly IncidentNumberAllocator $numbers,
    ) {}

    public function process(array $event, SyncContext $context): Model
    {
        $uuid = $event['uuid'];
        $payload = $event['payload'] ?? [];
        $occurredAt = Carbon::parse($event['occurred_at']);

        return match ($event['type']) {
            'shift.start' => $this->startShift($uuid, $payload, $occurredAt, $context),
            'shift.end' => $this->endShift($payload, $occurredAt, $context),
            'patrol.start' => $this->startPatrol($uuid, $payload, $occurredAt, $context),
            'patrol.scan' => $this->recordScan($uuid, $payload, $occurredAt, $context),
            'patrol.end' => $this->endPatrol($payload, $occurredAt, $context),
            'incident.report' => $this->reportIncident($uuid, $payload, $occurredAt, $context),
            'panic.alert' => $this->raisePanic($uuid, $payload, $occurredAt, $context),
            default => throw SyncEventException::permanent("Tipo de evento desconhecido: {$event['type']}", 'unknown_type'),
        };
    }

    private function startShift(string $uuid, array $payload, Carbon $occurredAt, SyncContext $context): Shift
    {
        $post = Post::find($payload['post_id'] ?? null);

        if (! $post) {
            throw SyncEventException::permanent('Posto não encontrado.', 'post_not_found');
        }

        return Shift::create([
            'uuid' => $uuid,
            'security_guard_id' => $context->guard->id,
            'post_id' => $post->id,
            'unit_id' => $post->unit_id,
            'started_at' => $occurredAt,
            'started_received_at' => $context->receivedAt,
            'start_latitude' => $payload['latitude'] ?? null,
            'start_longitude' => $payload['longitude'] ?? null,
            'start_accuracy_m' => $payload['accuracy_m'] ?? null,
            'status' => 'open',
            'device_id' => $context->deviceId(),
            'deviations' => $context->clockIsUntrustworthy()
                ? [PatrolScan::DEVIATION_CLOCK_SKEW]
                : null,
        ]);
    }

    private function endShift(array $payload, Carbon $occurredAt, SyncContext $context): Shift
    {
        $shift = $this->findShift($payload['shift_uuid'] ?? null, $context);

        // Reenvio de fechamento: o turno já está selado, devolve como está.
        if ($shift->status === 'closed') {
            return $shift;
        }

        $shift->update([
            'ended_at' => $occurredAt,
            'ended_received_at' => $context->receivedAt,
            'handover_notes' => $payload['handover_notes'] ?? null,
            'status' => 'closed',
        ]);

        $shift->update(['chain_hash' => $this->sealChain($shift)]);

        return $shift;
    }

    private function startPatrol(string $uuid, array $payload, Carbon $occurredAt, SyncContext $context): Patrol
    {
        $shift = $this->findShift($payload['shift_uuid'] ?? null, $context);
        $route = PatrolRoute::find($payload['patrol_route_id'] ?? null);

        if (! $route) {
            throw SyncEventException::permanent('Roteiro não encontrado.', 'route_not_found');
        }

        return Patrol::create([
            'uuid' => $uuid,
            'shift_id' => $shift->id,
            'patrol_route_id' => $route->id,
            'unit_id' => $shift->unit_id,
            'started_at' => $occurredAt,
            'started_received_at' => $context->receivedAt,
            'status' => 'in_progress',
            'expected_checkpoints' => $route->requiredCheckpointCount(),
            'scanned_checkpoints' => 0,
        ]);
    }

    private function recordScan(string $uuid, array $payload, Carbon $occurredAt, SyncContext $context): PatrolScan
    {
        $patrol = $this->findPatrol($payload['patrol_uuid'] ?? null);
        $checkpoint = Checkpoint::find($payload['checkpoint_id'] ?? null);

        if (! $checkpoint) {
            throw SyncEventException::permanent('Ponto de controle não encontrado.', 'checkpoint_not_found');
        }

        $outcome = $payload['outcome'] ?? 'scanned';

        if ($outcome === 'skipped' && blank($payload['justification'] ?? null)) {
            throw SyncEventException::permanent('Ponto pulado exige justificativa.', 'justification_required');
        }

        $latitude = $payload['latitude'] ?? null;
        $longitude = $payload['longitude'] ?? null;

        [$deviations, $distance] = $this->deviations->forScan(
            $checkpoint,
            $patrol,
            $occurredAt,
            $latitude === null ? null : (float) $latitude,
            $longitude === null ? null : (float) $longitude,
            $context->clockIsUntrustworthy(),
        );

        if ($outcome === 'skipped') {
            $deviations[] = PatrolScan::DEVIATION_SKIPPED;
        }

        $scan = PatrolScan::create([
            'uuid' => $uuid,
            'patrol_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'occurred_at' => $occurredAt,
            'received_at' => $context->receivedAt,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_m' => $payload['accuracy_m'] ?? null,
            'distance_m' => $distance,
            'method' => $payload['method'] ?? 'qr',
            'outcome' => $outcome,
            'justification' => $payload['justification'] ?? null,
            'deviations' => $deviations ?: null,
        ]);

        $this->storeChecklist($scan, $payload['checklist'] ?? []);
        $this->reserveAttachments($scan, $payload['attachments'] ?? []);

        if ($outcome === 'scanned') {
            $patrol->increment('scanned_checkpoints');
        }

        return $scan;
    }

    private function endPatrol(array $payload, Carbon $occurredAt, SyncContext $context): Patrol
    {
        $patrol = $this->findPatrol($payload['patrol_uuid'] ?? null);

        if ($patrol->status !== 'in_progress') {
            return $patrol;
        }

        $incomplete = $patrol->scanned_checkpoints < $patrol->expected_checkpoints;

        $patrol->update([
            'ended_at' => $occurredAt,
            'ended_received_at' => $context->receivedAt,
            'status' => 'completed',
            'deviations' => $incomplete ? ['incomplete'] : null,
        ]);

        return $patrol;
    }

    private function reportIncident(string $uuid, array $payload, Carbon $occurredAt, SyncContext $context): Incident
    {
        $type = IncidentType::find($payload['incident_type_id'] ?? null);

        if (! $type) {
            throw SyncEventException::permanent('Tipo de ocorrência não encontrado.', 'incident_type_not_found');
        }

        $shift = filled($payload['shift_uuid'] ?? null)
            ? $this->findShift($payload['shift_uuid'], $context)
            : null;

        $patrol = filled($payload['patrol_uuid'] ?? null)
            ? $this->findPatrol($payload['patrol_uuid'])
            : null;

        $unitId = $shift?->unit_id ?? $context->guard->default_unit_id;

        if (! $unitId) {
            throw SyncEventException::permanent('Ocorrência sem unidade definida.', 'unit_missing');
        }

        $unit = $shift?->unit ?? $context->guard->defaultUnit;
        $number = $this->numbers->allocate($unit, (int) $occurredAt->year);

        $incident = Incident::create([
            'uuid' => $uuid,
            'number' => $number['number'],
            'sequence' => $number['sequence'],
            'year' => $number['year'],
            'unit_id' => $unitId,
            'shift_id' => $shift?->id,
            'patrol_id' => $patrol?->id,
            'checkpoint_id' => $payload['checkpoint_id'] ?? null,
            'incident_type_id' => $type->id,
            'reported_by_id' => $context->guard->id,
            'occurred_at' => $occurredAt,
            'received_at' => $context->receivedAt,
            'location' => $payload['location'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'severity' => $payload['severity'] ?? $type->default_severity ?? 'low',
            'classification' => $payload['classification'] ?? $type->default_classification ?? 'prevention',
            'description' => $payload['description'] ?? '',
            'actions_taken' => $payload['actions_taken'] ?? null,
            'people_involved' => $payload['people_involved'] ?? null,
            'status' => 'registered',
        ]);

        $this->reserveAttachments($incident, $payload['attachments'] ?? []);

        return $incident;
    }

    /**
     * Pânico que chegou pela fila, e não pelo endpoint dedicado — o aparelho
     * estava sem rede na hora do acionamento. O alerta é criado com a hora
     * original, e a diferença até received_at mostra quanto tempo se passou.
     */
    private function raisePanic(string $uuid, array $payload, Carbon $occurredAt, SyncContext $context): SafetyAlert
    {
        $shift = filled($payload['shift_uuid'] ?? null)
            ? Shift::where('uuid', $payload['shift_uuid'])->first()
            : $context->guard->openShift();

        return SafetyAlert::create([
            'uuid' => $uuid,
            'kind' => SafetyAlert::KIND_PANIC,
            'security_guard_id' => $context->guard->id,
            'unit_id' => $shift?->unit_id ?? $context->guard->default_unit_id,
            'shift_id' => $shift?->id,
            'patrol_id' => $shift?->patrols()->where('status', 'in_progress')->value('id'),
            'occurred_at' => $occurredAt,
            'received_at' => $context->receivedAt,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'accuracy_m' => $payload['accuracy_m'] ?? null,
            'device_id' => $context->deviceId(),
        ]);
    }

    private function storeChecklist(PatrolScan $scan, array $responses): void
    {
        foreach ($responses as $response) {
            if (blank($response['uuid'] ?? null) || blank($response['checklist_item_id'] ?? null)) {
                continue;
            }

            ChecklistResponse::firstOrCreate(
                ['uuid' => $response['uuid']],
                [
                    'patrol_scan_id' => $scan->id,
                    'checklist_item_id' => $response['checklist_item_id'],
                    'answer' => $response['answer'] ?? 'not_applicable',
                    'note' => $response['note'] ?? null,
                ],
            );
        }
    }

    /**
     * A evidência é referenciada pelo evento antes de o binário subir: a foto vai
     * em requisição separada para não travar a fila de eventos numa rede ruim.
     */
    private function reserveAttachments(Model $owner, array $uuids): void
    {
        foreach ($uuids as $uuid) {
            if (blank($uuid)) {
                continue;
            }

            Attachment::updateOrCreate(
                ['uuid' => $uuid],
                [
                    'attachable_type' => $owner->getMorphClass(),
                    'attachable_id' => $owner->getKey(),
                ],
            );
        }
    }

    private function findShift(?string $uuid, SyncContext $context): Shift
    {
        if (blank($uuid)) {
            throw SyncEventException::permanent('Evento sem turno de referência.', 'shift_uuid_missing');
        }

        $shift = Shift::where('uuid', $uuid)->first();

        if (! $shift) {
            throw SyncEventException::retryable("Turno {$uuid} ainda não sincronizado.");
        }

        if ($shift->security_guard_id !== $context->guard->id) {
            throw SyncEventException::permanent('Turno pertence a outro vigilante.', 'shift_forbidden');
        }

        return $shift;
    }

    private function findPatrol(?string $uuid): Patrol
    {
        if (blank($uuid)) {
            throw SyncEventException::permanent('Evento sem ronda de referência.', 'patrol_uuid_missing');
        }

        $patrol = Patrol::where('uuid', $uuid)->first();

        if (! $patrol) {
            throw SyncEventException::retryable("Ronda {$uuid} ainda não sincronizada.");
        }

        return $patrol;
    }

    /**
     * Selo de integridade do turno: encadeia os uuids dos eventos na ordem em que
     * aconteceram. Alterar qualquer registro depois do fechamento quebra o hash.
     */
    private function sealChain(Shift $shift): string
    {
        $parts = [$shift->uuid, $shift->started_at?->toIso8601String(), $shift->ended_at?->toIso8601String()];

        $scans = PatrolScan::query()
            ->whereIn('patrol_id', $shift->patrols()->pluck('id'))
            ->orderBy('occurred_at')
            ->get(['uuid', 'occurred_at']);

        foreach ($scans as $scan) {
            $parts[] = $scan->uuid.'@'.$scan->occurred_at->toIso8601String();
        }

        foreach ($shift->incidents()->orderBy('occurred_at')->pluck('uuid') as $uuid) {
            $parts[] = $uuid;
        }

        return hash('sha256', implode('|', $parts));
    }
}
