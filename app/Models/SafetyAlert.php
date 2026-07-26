<?php

namespace App\Models;

use App\Observers\SafetyAlertObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(SafetyAlertObserver::class)]
class SafetyAlert extends Model
{
    public const KIND_PANIC = 'panic';
    public const KIND_INACTIVITY = 'inactivity';

    public const KINDS = [
        self::KIND_PANIC => 'Botão de pânico',
        self::KIND_INACTIVITY => 'Inatividade em ronda',
    ];

    public const STATUSES = [
        'open' => 'Aberto',
        'acknowledged' => 'Reconhecido',
        'resolved' => 'Encerrado',
        'false_alarm' => 'Falso alarme',
    ];

    protected $fillable = [
        'uuid', 'kind', 'security_guard_id', 'unit_id', 'shift_id', 'patrol_id',
        'occurred_at', 'received_at', 'latitude', 'longitude', 'accuracy_m',
        'silence_minutes', 'status', 'acknowledged_by_user_id', 'acknowledged_at',
        'resolved_at', 'notes', 'device_id',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(SecurityGuard::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function patrol(): BelongsTo
    {
        return $this->belongsTo(Patrol::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id');
    }

    public function isPanic(): bool
    {
        return $this->kind === self::KIND_PANIC;
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    /**
     * Quanto tempo o alerta ficou sem ninguém reconhecer. É o número que diz se
     * a supervisão está de fato atendendo — um botão de pânico que ninguém vê
     * não protege ninguém.
     */
    public function minutesToAcknowledge(): ?int
    {
        return $this->acknowledged_at
            ? (int) round($this->acknowledged_at->diffInMinutes($this->occurred_at, absolute: true))
            : null;
    }
}
