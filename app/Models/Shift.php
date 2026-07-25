<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Turno de serviço: da assunção de posto ao fechamento.
 *
 * Registra PRESENÇA OPERACIONAL, não jornada de trabalho — o sistema não é
 * REP-P e não substitui registro de ponto (Portaria 671/2021).
 */
class Shift extends Model
{
    protected $fillable = [
        'uuid', 'security_guard_id', 'post_id', 'unit_id',
        'started_at', 'started_received_at', 'ended_at', 'ended_received_at',
        'start_latitude', 'start_longitude', 'start_accuracy_m',
        'status', 'handover_notes', 'deviations', 'chain_hash', 'device_id',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'started_received_at' => 'datetime',
            'ended_at' => 'datetime',
            'ended_received_at' => 'datetime',
            'start_latitude' => 'decimal:7',
            'start_longitude' => 'decimal:7',
            'deviations' => 'array',
        ];
    }

    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(SecurityGuard::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function patrols(): HasMany
    {
        return $this->hasMany(Patrol::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function durationMinutes(): ?int
    {
        return $this->ended_at?->diffInMinutes($this->started_at);
    }
}
