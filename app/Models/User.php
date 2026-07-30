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

#[Fillable(['name', 'email', 'password', 'role', 'active', 'unit_id', 'permissions', 'google_id', 'google_linked_at'])]
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
     * Permissões concedidas individualmente, independentes do perfil.
     *
     * O perfil diz o que a pessoa é na operação; a permissão diz o que ela pode
     * operar. Não é todo vigilante que fica na portaria mexendo no quadro de
     * chaves, e não é todo supervisor que precisa disso.
     */
    public const PERMISSION_KEYS = 'keys.manage';

    public const PERMISSIONS = [
        self::PERMISSION_KEYS => 'Controle de chaves (portaria)',
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
            'permissions' => 'array',
            'google_linked_at' => 'datetime',
        ];
    }

    /**
     * O administrador tem tudo por definição - do contrário seria possível
     * revogar a própria capacidade de conceder permissões e travar o sistema.
     */
    public function hasPermission(string $permission): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissions ?? [], true);
    }

    /** @return list<string> */
    public function grantedPermissionLabels(): array
    {
        if ($this->isAdmin()) {
            return ['todas (administrador)'];
        }

        return array_values(array_map(
            fn (string $p) => self::PERMISSIONS[$p] ?? $p,
            array_intersect($this->permissions ?? [], array_keys(self::PERMISSIONS)),
        ));
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
     * enxergam todas - por isso o unit_id deles fica nulo.
     */
    public function isScopedToUnit(): bool
    {
        return $this->role === self::ROLE_UNIT_MANAGER && $this->unit_id !== null;
    }

    /**
     * O vigilante não entra no painel administrativo - lá está a operação
     * inteira das duas unidades.
     *
     * O painel da portaria não é liberado por perfil, e sim pela permissão de
     * controle de chaves: quem não opera o quadro não entra, seja vigilante ou
     * supervisor.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($panel->getId() === 'portaria') {
            return $this->hasPermission(self::PERMISSION_KEYS);
        }

        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_SUPERVISOR,
            self::ROLE_UNIT_MANAGER,
        ], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
}
