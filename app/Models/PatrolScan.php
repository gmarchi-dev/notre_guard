<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Leitura de um ponto de controle. Registro imutável: correções entram como
 * novo evento, nunca como edição.
 */
class PatrolScan extends Model
{
    public const DEVIATION_OUT_OF_RADIUS = 'out_of_radius';
    public const DEVIATION_NO_GPS = 'no_gps';
    public const DEVIATION_OUT_OF_WINDOW = 'out_of_window';
    public const DEVIATION_OUT_OF_ORDER = 'out_of_order';
    public const DEVIATION_CLOCK_SKEW = 'clock_skew';
    public const DEVIATION_SKIPPED = 'skipped';

    public const DEVIATION_LABELS = [
        self::DEVIATION_OUT_OF_RADIUS => 'Fora do raio do ponto',
        self::DEVIATION_NO_GPS => 'Sem GPS',
        self::DEVIATION_OUT_OF_WINDOW => 'Fora da janela prevista',
        self::DEVIATION_OUT_OF_ORDER => 'Fora da ordem do roteiro',
        self::DEVIATION_CLOCK_SKEW => 'Relógio do aparelho divergente',
        self::DEVIATION_SKIPPED => 'Ponto não realizado',
    ];

    protected $fillable = [
        'uuid', 'patrol_id', 'checkpoint_id', 'occurred_at', 'received_at',
        'latitude', 'longitude', 'accuracy_m', 'distance_m',
        'method', 'outcome', 'justification', 'deviations',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'deviations' => 'array',
        ];
    }

    public function patrol(): BelongsTo
    {
        return $this->belongsTo(Patrol::class);
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    public function checklistResponses(): HasMany
    {
        return $this->hasMany(ChecklistResponse::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function hasDeviations(): bool
    {
        return filled($this->deviations);
    }

    public function deviationLabels(): array
    {
        return array_map(
            fn (string $d) => self::DEVIATION_LABELS[$d] ?? $d,
            $this->deviations ?? [],
        );
    }
}
