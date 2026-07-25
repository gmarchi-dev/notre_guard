<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SecurityGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Login do vigilante no aparelho corporativo.
 *
 * A matrícula é a credencial, não o e-mail: é o que o vigilante sabe de cor e o
 * que está no crachá.
 */
class DeviceAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'registration' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
            'device_id' => ['required', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $guard = SecurityGuard::with('user', 'defaultUnit')
            ->where('registration', $data['registration'])
            ->first();

        if (! $guard || ! Hash::check($data['password'], $guard->user->password)) {
            throw ValidationException::withMessages([
                'registration' => 'Matrícula ou senha inválida.',
            ]);
        }

        if (! $guard->active || ! $guard->user->active) {
            throw ValidationException::withMessages([
                'registration' => 'Cadastro inativo. Procure a supervisão.',
            ]);
        }

        $device = Device::firstOrNew(['device_id' => $data['device_id']]);

        if ($device->exists && $device->revoked) {
            throw ValidationException::withMessages([
                'device_id' => 'Este aparelho foi bloqueado. Procure a supervisão.',
            ]);
        }

        $device->fill([
            'name' => $data['device_name'] ?? $device->name,
            'unit_id' => $guard->default_unit_id,
            'last_security_guard_id' => $guard->id,
            'user_agent' => $request->userAgent(),
            'last_seen_at' => now(),
        ])->save();

        // Um token por aparelho: novo login no mesmo aparelho substitui o anterior.
        $guard->user->tokens()->where('name', $this->tokenName($device))->delete();

        $token = $guard->user->createToken($this->tokenName($device), ['field'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'guard' => [
                'id' => $guard->id,
                'name' => $guard->user->name,
                'registration' => $guard->registration,
                'refresher_expired' => $guard->refresherExpired(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['status' => 'ok']);
    }

    private function tokenName(Device $device): string
    {
        return 'field:'.$device->device_id;
    }
}
