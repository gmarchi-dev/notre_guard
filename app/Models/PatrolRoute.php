<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Roteiro de ronda: sequência de checkpoints com janelas de execução.
 */
class PatrolRoute extends Model
{
    protected $fillable = [
        'unit_id', 'name', 'description', 'ordered',
        'expected_duration_min', 'tolerance_min', 'active',
    ];

    protected function casts(): array
    {
        return [
            'ordered' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function checkpoints(): BelongsToMany
    {
        return $this->belongsToMany(Checkpoint::class, 'patrol_route_checkpoints')
            ->withPivot(['position', 'required'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PatrolRouteSchedule::class);
    }

    public function patrols(): HasMany
    {
        return $this->hasMany(Patrol::class);
    }

    public function requiredCheckpointCount(): int
    {
        return $this->checkpoints()->wherePivot('required', true)->count();
    }
}
