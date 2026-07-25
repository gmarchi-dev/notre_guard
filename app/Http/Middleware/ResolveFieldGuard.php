<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Models\SecurityGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve o vigilante e o aparelho da requisição, e barra aparelho revogado.
 *
 * Deixa em request->attributes: security_guard e device.
 */
class ResolveFieldGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = SecurityGuard::with('defaultUnit')
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $guard || ! $guard->active) {
            return response()->json([
                'message' => 'Usuário sem cadastro ativo de vigilante.',
            ], Response::HTTP_FORBIDDEN);
        }

        $device = null;
        $deviceId = $request->header('X-Device-Id') ?? $request->input('device_id');

        if (filled($deviceId)) {
            $device = Device::where('device_id', $deviceId)->first();

            if ($device?->revoked) {
                return response()->json([
                    'message' => 'Aparelho bloqueado.',
                ], Response::HTTP_FORBIDDEN);
            }

            $device?->update(['last_seen_at' => now()]);
        }

        $request->attributes->set('security_guard', $guard);
        $request->attributes->set('device', $device);

        return $next($request);
    }
}
