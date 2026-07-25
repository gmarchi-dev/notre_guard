<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vigilante. Perfil operacional vinculado a um User (login).
 *
 * Não se chama "Guard" porque Model::guard() já existe no Eloquent e o termo
 * colidiria com os auth guards do Laravel.
 */
class SecurityGuard extends Model
{
    protected $fillable = [
        'user_id', 'default_unit_id', 'registration', 'professional_id',
        'refresher_valid_until', 'phone', 'active',
    ];

    protected function casts(): array
    {
        return [
            'refresher_valid_until' => 'date',
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function defaultUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'default_unit_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function openShift(): ?Shift
    {
        return $this->shifts()->where('status', 'open')->latest('started_at')->first();
    }

    /**
     * Reciclagem vencida não bloqueia o uso do app — apenas sinaliza ao gestor,
     * que é quem trata a pendência junto ao RH.
     */
    public function refresherExpired(): bool
    {
        return $this->refresher_valid_until !== null
            && $this->refresher_valid_until->isPast();
    }
}
