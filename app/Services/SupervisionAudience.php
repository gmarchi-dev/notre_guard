<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Quem é avisado sobre o que acontece numa unidade.
 *
 * Regra única, usada por ocorrências e por alertas de segurança: administração e
 * supervisão recebem tudo; gestor de unidade recebe o da unidade dele; vigilante
 * nunca recebe, porque está em campo e não em posição de tratar.
 */
class SupervisionAudience
{
    /** @return Collection<int, User> */
    public function for(?int $unitId): Collection
    {
        return User::query()
            ->where('active', true)
            ->where(function ($query) use ($unitId) {
                $query->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR]);

                if ($unitId !== null) {
                    $query->orWhere(fn ($q) => $q
                        ->where('role', User::ROLE_UNIT_MANAGER)
                        ->where('unit_id', $unitId));
                }
            })
            ->get();
    }
}
