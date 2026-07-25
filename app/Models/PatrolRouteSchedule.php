<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolRouteSchedule extends Model
{
    public const WEEKDAYS = [
        0 => 'Domingo',
        1 => 'Segunda',
        2 => 'Terça',
        3 => 'Quarta',
        4 => 'Quinta',
        5 => 'Sexta',
        6 => 'Sábado',
    ];

    protected $fillable = [
        'patrol_route_id', 'label', 'window_start', 'window_end', 'weekdays', 'active',
    ];

    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            'active' => 'boolean',
        ];
    }

    public function patrolRoute(): BelongsTo
    {
        return $this->belongsTo(PatrolRoute::class);
    }
}
