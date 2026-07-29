<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SafetyAlert;
use App\Models\SecurityGuard;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint dedicado ao botão de pânico.
 *
 * Não passa pela fila de sincronização de propósito: a fila roda a cada 30
 * segundos e em lote, o que é irrelevante para uma leitura de ponto e
 * inaceitável para um pedido de socorro. Aqui o aparelho tenta entregar na hora.
 *
 * Se não houver rede, o app enfileira o mesmo evento com o mesmo uuid — e a
 * idempotência garante que a entrega tardia não crie um segundo alerta.
 */
class PanicController extends Controller
{
    /**
     * Situação de um acionamento.
     *
     * Existe para devolver ao vigilante a informação que faltava: saber que o
     * servidor gravou não é saber que alguém está indo. Num momento de medo, a
     * diferença entre "recebido" e "a supervisão reconheceu às 02:14" é o que
     * dá para fazer.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var SecurityGuard $guard */
        $guard = $request->attributes->get('security_guard');

        $alert = SafetyAlert::query()
            ->where('uuid', $uuid)
            // Cada um só consulta o próprio acionamento.
            ->where('security_guard_id', $guard->id)
            ->with('acknowledgedBy')
            ->first();

        if (! $alert) {
            return response()->json(['message' => 'Acionamento não encontrado.'], 404);
        }

        return response()->json([
            'status' => $alert->status,
            'acknowledged_at' => $alert->acknowledged_at?->toIso8601String(),
            'acknowledged_by' => $alert->acknowledgedBy?->name,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uuid' => ['required', 'uuid'],
            'occurred_at' => ['required', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'integer', 'min:0'],
        ]);

        /** @var SecurityGuard $guard */
        $guard = $request->attributes->get('security_guard');

        $existing = SafetyAlert::where('uuid', $data['uuid'])->first();

        if ($existing) {
            return response()->json([
                'status' => 'duplicate',
                'alert_id' => $existing->id,
            ]);
        }

        $shift = $guard->openShift();

        $alert = SafetyAlert::create([
            'uuid' => $data['uuid'],
            'kind' => SafetyAlert::KIND_PANIC,
            'security_guard_id' => $guard->id,
            'unit_id' => $shift?->unit_id ?? $guard->default_unit_id,
            'shift_id' => $shift?->id,
            'patrol_id' => $shift?->patrols()->where('status', 'in_progress')->value('id'),
            'occurred_at' => $data['occurred_at'],
            'received_at' => now(),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'accuracy_m' => $data['accuracy_m'] ?? null,
            'device_id' => $request->attributes->get('device')?->device_id,
        ]);

        return response()->json([
            'status' => 'received',
            'alert_id' => $alert->id,
            'received_at' => $alert->received_at->toIso8601String(),
        ], 201);
    }
}
