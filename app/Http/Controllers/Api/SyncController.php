<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\SecurityGuard;
use App\Services\Sync\EventProcessor;
use App\Services\Sync\SyncContext;
use App\Services\Sync\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SyncController extends Controller
{
    public function __construct(private readonly SyncService $sync) {}

    public function events(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_sent_at' => ['nullable', 'date'],
            'events' => ['required', 'array', 'max:200'],
            'events.*.uuid' => ['required', 'uuid'],
            'events.*.type' => ['required', Rule::in(EventProcessor::TYPES)],
            'events.*.occurred_at' => ['required', 'date'],
            'events.*.payload' => ['nullable', 'array'],
        ]);

        $receivedAt = now();

        // Divergência de relógio medida no envio, não no evento: um evento antigo
        // pode ser só uma fila que ficou dias sem rede.
        $skew = isset($data['client_sent_at'])
            ? (int) Carbon::parse($data['client_sent_at'])->diffInSeconds($receivedAt, false)
            : 0;

        $context = new SyncContext(
            guard: $request->attributes->get('security_guard'),
            device: $request->attributes->get('device'),
            receivedAt: $receivedAt,
            clockSkewSeconds: $skew,
        );

        ['results' => $results, 'batch' => $batch] = $this->sync->ingest($data['events'], $context);

        return response()->json([
            'server_time' => $receivedAt->toIso8601String(),
            'clock_skew_seconds' => $skew,
            'batch_id' => $batch->id,
            'results' => $results,
        ]);
    }

    /**
     * Upload da evidência, separado do evento: numa rede ruim a foto não pode
     * bloquear a fila de registros.
     */
    public function attachment(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,mp4,m4a,aac,3gp'],
            'captured_at' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $attachment = Attachment::where('uuid', $uuid)->first();

        if (! $attachment) {
            // O evento que referencia esta evidência ainda não chegou. O
            // dispositivo deve reenviar depois de sincronizar os eventos.
            return response()->json([
                'message' => 'Evidência ainda não referenciada por nenhum evento.',
                'retryable' => true,
            ], 409);
        }

        if ($attachment->isStored()) {
            return response()->json(['status' => 'duplicate', 'uuid' => $uuid]);
        }

        /** @var SecurityGuard $guard */
        $guard = $request->attributes->get('security_guard');

        $file = $request->file('file');

        // Hash e metadados antes de mover: store() invalida o caminho temporário.
        $meta = [
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
        ];

        $path = $file->store("evidence/{$guard->id}/".now()->format('Y/m'));

        $this->sync->storeAttachment($attachment, $path, [
            ...$meta,
            'captured_at' => $request->input('captured_at'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
        ]);

        return response()->json(['status' => 'stored', 'uuid' => $uuid]);
    }
}
