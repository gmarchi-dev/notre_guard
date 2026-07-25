<?php

namespace App\Services\Sync;

use App\Models\Attachment;
use App\Models\Incident;
use App\Models\Patrol;
use App\Models\PatrolScan;
use App\Models\Shift;
use App\Models\SyncBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Recebe um lote de eventos do dispositivo.
 *
 * Nunca "tudo ou nada": cada evento tem seu resultado, para o dispositivo saber
 * exatamente o que confirmar e o que manter na fila.
 */
class SyncService
{
    /** Tabelas onde o uuid de um evento já processado pode estar. */
    private const UUID_OWNERS = [
        'shift.start' => Shift::class,
        'patrol.start' => Patrol::class,
        'patrol.scan' => PatrolScan::class,
        'incident.report' => Incident::class,
    ];

    public function __construct(private readonly EventProcessor $processor) {}

    /**
     * @param  list<array{uuid: string, type: string, occurred_at: string, payload?: array}>  $events
     * @return array{results: list<array<string, mixed>>, batch: SyncBatch}
     */
    public function ingest(array $events, SyncContext $context): array
    {
        $results = [];
        $accepted = 0;
        $duplicated = 0;
        $failed = 0;
        $errors = [];

        foreach ($events as $event) {
            $uuid = $event['uuid'] ?? null;

            if (blank($uuid)) {
                $failed++;
                $results[] = ['uuid' => null, 'status' => 'failed', 'code' => 'uuid_missing', 'retryable' => false];

                continue;
            }

            if ($existing = $this->alreadyProcessed($event)) {
                $duplicated++;
                $results[] = ['uuid' => $uuid, 'status' => 'duplicate', 'id' => $existing];

                continue;
            }

            try {
                // Uma transação por evento: um evento inválido não derruba o lote.
                $record = DB::transaction(fn () => $this->processor->process($event, $context));

                $accepted++;
                $results[] = [
                    'uuid' => $uuid,
                    'status' => 'accepted',
                    'id' => $record->getKey(),
                    'number' => $record instanceof Incident ? $record->number : null,
                ];
            } catch (SyncEventException $e) {
                $failed++;
                $errors[] = ['uuid' => $uuid, 'type' => $event['type'] ?? null, 'code' => $e->errorCode, 'message' => $e->getMessage()];
                $results[] = [
                    'uuid' => $uuid,
                    'status' => 'failed',
                    'code' => $e->errorCode,
                    'message' => $e->getMessage(),
                    'retryable' => $e->retryable,
                ];
            } catch (Throwable $e) {
                // Falha inesperada é retentável: o problema é do servidor, e o
                // dispositivo não deve descartar o registro do vigilante.
                Log::error('Falha ao processar evento de sincronização', [
                    'uuid' => $uuid,
                    'type' => $event['type'] ?? null,
                    'exception' => $e,
                ]);

                $failed++;
                $errors[] = ['uuid' => $uuid, 'code' => 'server_error', 'message' => $e->getMessage()];
                $results[] = ['uuid' => $uuid, 'status' => 'failed', 'code' => 'server_error', 'retryable' => true];
            }
        }

        $batch = SyncBatch::create([
            'security_guard_id' => $context->guard->id,
            'device_id' => $context->deviceId(),
            'items_total' => count($events),
            'items_accepted' => $accepted,
            'items_duplicated' => $duplicated,
            'items_failed' => $failed,
            'errors' => $errors ?: null,
        ]);

        $context->device?->update([
            'last_sync_at' => $context->receivedAt,
            'last_security_guard_id' => $context->guard->id,
        ]);

        return ['results' => $results, 'batch' => $batch];
    }

    /**
     * Idempotência. Eventos de encerramento (shift.end, patrol.end) não têm
     * registro próprio — o próprio handler detecta o reenvio e devolve o estado
     * atual, então aqui só checamos os que criam registro.
     */
    private function alreadyProcessed(array $event): int|string|null
    {
        $model = self::UUID_OWNERS[$event['type'] ?? ''] ?? null;

        if (! $model) {
            return null;
        }

        return $model::where('uuid', $event['uuid'])->value('id');
    }

    /**
     * Anexa o binário a uma evidência já referenciada por um evento.
     */
    public function storeAttachment(Attachment $attachment, string $path, array $meta): Attachment
    {
        $attachment->update([
            'path' => $path,
            'original_name' => $meta['original_name'] ?? null,
            'mime' => $meta['mime'] ?? null,
            'size_bytes' => $meta['size_bytes'] ?? null,
            'sha256' => $meta['sha256'] ?? null,
            'captured_at' => $meta['captured_at'] ?? null,
            'latitude' => $meta['latitude'] ?? null,
            'longitude' => $meta['longitude'] ?? null,
            'status' => 'stored',
        ]);

        return $attachment;
    }
}
