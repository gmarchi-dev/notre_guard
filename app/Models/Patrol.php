<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patrol extends Model
{
    protected $fillable = [
        'uuid', 'shift_id', 'patrol_route_id', 'unit_id',
        'started_at', 'started_received_at', 'ended_at', 'ended_received_at',
        'status', 'expected_checkpoints', 'scanned_checkpoints', 'deviations',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'started_received_at' => 'datetime',
            'ended_at' => 'datetime',
            'ended_received_at' => 'datetime',
            'deviations' => 'array',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function patrolRoute(): BelongsTo
    {
        return $this->belongsTo(PatrolRoute::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(PatrolScan::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function safetyAlerts(): HasMany
    {
        return $this->hasMany(SafetyAlert::class);
    }

    public function completionRate(): float
    {
        if ($this->expected_checkpoints === 0) {
            return 0.0;
        }

        return round($this->scanned_checkpoints / $this->expected_checkpoints * 100, 1);
    }
}
