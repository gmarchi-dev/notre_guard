<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Incident extends Model
{
    public const SEVERITIES = [
        'low' => 'Baixa',
        'medium' => 'Média',
        'high' => 'Alta',
        'critical' => 'Crítica',
    ];

    public const CLASSIFICATIONS = [
        'prevention' => 'Prevenção',
        'loss' => 'Perda',
    ];

    public const STATUSES = [
        'draft' => 'Rascunho',
        'registered' => 'Registrada',
        'under_review' => 'Em análise',
        'closed' => 'Encerrada',
    ];

    protected $fillable = [
        'uuid', 'number', 'sequence', 'year',
        'unit_id', 'shift_id', 'patrol_id', 'checkpoint_id',
        'incident_type_id', 'reported_by_id',
        'occurred_at', 'received_at', 'location', 'latitude', 'longitude',
        'severity', 'classification', 'description', 'actions_taken',
        'people_involved', 'status', 'closed_by_user_id', 'closed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'closed_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'people_involved' => 'array',
        ];
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

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(IncidentType::class, 'incident_type_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(SecurityGuard::class, 'reported_by_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
