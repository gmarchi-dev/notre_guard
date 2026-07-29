<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkpoint;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\PatrolRoute;
use App\Models\Post;
use App\Models\SecurityGuard;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pacote que o aparelho baixa no início do turno e guarda no IndexedDB.
 *
 * Tudo que a ronda precisa vem aqui de uma vez: depois disso o app funciona sem
 * rede até o fim do turno.
 */
class BootstrapController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var SecurityGuard $guard */
        $guard = $request->attributes->get('security_guard');

        $unit = $guard->defaultUnit;

        if (! $unit) {
            return response()->json([
                'message' => 'Vigilante sem unidade definida. Procure a supervisão.',
            ], 409);
        }

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'guard' => [
                'id' => $guard->id,
                'name' => $guard->user->name,
                'registration' => $guard->registration,
                'refresher_expired' => $guard->refresherExpired(),
            ],
            'unit' => [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
            ],
            'open_shift' => $this->openShift($guard),
            'posts' => $this->posts($unit->id),
            'checkpoints' => $this->checkpoints($unit->id),
            'routes' => $this->routes($unit->id),
            'incident_types' => $this->incidentTypes(),
            'frequent_incident_type_ids' => $this->frequentIncidentTypes($unit->id),
        ]);
    }

    private function openShift(SecurityGuard $guard): ?array
    {
        $shift = $guard->openShift();

        if (! $shift) {
            return null;
        }

        return [
            'uuid' => $shift->uuid,
            'post_id' => $shift->post_id,
            'started_at' => $shift->started_at->toIso8601String(),
            'open_patrol' => $shift->patrols()
                ->where('status', 'in_progress')
                ->latest('started_at')
                ->get(['uuid', 'patrol_route_id'])
                ->first()
                ?->only(['uuid', 'patrol_route_id']),
        ];
    }

    private function posts(int $unitId): array
    {
        return Post::query()
            ->where('unit_id', $unitId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'kind', 'qr_token'])
            ->all();
    }

    private function checkpoints(int $unitId): array
    {
        return Checkpoint::query()
            ->with('checklistTemplate.items')
            ->where('unit_id', $unitId)
            ->where('active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Checkpoint $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'qr_token' => $c->qr_token,
                'nfc_uid' => $c->nfc_uid,
                'latitude' => $c->latitude === null ? null : (float) $c->latitude,
                'longitude' => $c->longitude === null ? null : (float) $c->longitude,
                'radius_m' => $c->radius_m,
                'instruction' => $c->instruction,
                'checklist' => $c->checklistTemplate?->items->map(fn ($item) => [
                    'id' => $item->id,
                    'label' => $item->label,
                    'photo_required_when_nonconforming' => $item->photo_required_when_nonconforming,
                ])->all(),
            ])
            ->all();
    }

    private function routes(int $unitId): array
    {
        return PatrolRoute::query()
            ->with(['checkpoints:id', 'schedules'])
            ->where('unit_id', $unitId)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (PatrolRoute $route) => [
                'id' => $route->id,
                'name' => $route->name,
                'ordered' => $route->ordered,
                'expected_duration_min' => $route->expected_duration_min,
                'tolerance_min' => $route->tolerance_min,
                'checkpoints' => $route->checkpoints->map(fn ($c) => [
                    'checkpoint_id' => $c->id,
                    'position' => $c->pivot->position,
                    'required' => (bool) $c->pivot->required,
                ])->all(),
                'schedules' => $route->schedules->map(fn ($s) => [
                    'label' => $s->label,
                    'window_start' => substr((string) $s->window_start, 0, 5),
                    'window_end' => substr((string) $s->window_end, 0, 5),
                    'weekdays' => $s->weekdays,
                ])->all(),
            ])
            ->all();
    }

    /**
     * Árvore achatada: o app mostra "Pai › Filho" numa lista única, que é mais
     * rápido de operar com uma mão do que navegar dois níveis.
     */
    private function incidentTypes(): array
    {
        return IncidentType::query()
            ->with('parent')
            ->where('active', true)
            ->whereNotNull('parent_id')
            ->get()
            ->sortBy(fn (IncidentType $type) => $type->fullName())
            ->map(fn (IncidentType $type) => [
                'id' => $type->id,
                'label' => $type->fullName(),
                // Grupo e folha separados: o aparelho monta a escolha em duas
                // etapas. Achatado num rótulo só, viravam 17 linhas iguais numa
                // roda nativa, e a hierarquia existia apenas como um caractere.
                'group' => $type->parent?->name ?? 'Outros',
                'name' => $type->name,
                'default_severity' => $type->default_severity,
                'default_classification' => $type->default_classification,
            ])
            ->values()
            ->all();
    }

    /**
     * Tipos mais registrados nesta unidade nos últimos 90 dias.
     *
     * Atalho medido, não adivinhado: numa instalação nova a lista vem vazia e a
     * seção simplesmente não aparece, em vez de sugerir o que ninguém usa.
     *
     * @return list<int>
     */
    private function frequentIncidentTypes(int $unitId): array
    {
        return Incident::query()
            ->where('unit_id', $unitId)
            ->where('occurred_at', '>=', now()->subDays(90))
            ->selectRaw('incident_type_id, count(*) as total')
            ->groupBy('incident_type_id')
            ->orderByDesc('total')
            ->limit(4)
            ->pluck('incident_type_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
