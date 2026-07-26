<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'active', 'unit_id', 'google_id', 'google_linked_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_UNIT_MANAGER = 'unit_manager';
    public const ROLE_GUARD = 'guard';

    public const ROLES = [
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_SUPERVISOR => 'Supervisão',
        self::ROLE_UNIT_MANAGER => 'Gestor da unidade',
        self::ROLE_GUARD => 'Vigilante',
    ];

    /**
     * Defaults no model, não só no banco: sem isto um User criado em código
     * fica com active nulo até ser recarregado e é barrado no painel.
     */
    protected $attributes = [
        'role' => self::ROLE_GUARD,
        'active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'google_linked_at' => 'datetime',
        ];
    }

    public function securityGuard(): HasOne
    {
        return $this->hasOne(SecurityGuard::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Gestor de unidade só enxerga a própria unidade. Admin e supervisão
     * enxergam todas — por isso o unit_id deles fica nulo.
     */
    public function isScopedToUnit(): bool
    {
        return $this->role === self::ROLE_UNIT_MANAGER && $this->unit_id !== null;
    }

    /**
     * O vigilante não entra no painel administrativo — lá está a operação
     * inteira das duas unidades. Ele entra apenas no painel da portaria, que
     * só tem controle de chaves.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->active) {
            return false;
        }

        $management = [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_UNIT_MANAGER];

        if ($panel->getId() === 'portaria') {
            return in_array($this->role, [...$management, self::ROLE_GUARD], true);
        }

        return in_array($this->role, $management, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
}
