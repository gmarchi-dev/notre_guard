<?php

namespace App\Models;

use App\Models\Concerns\HasQrToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checkpoint extends Model
{
    use HasQrToken;

    protected $fillable = [
        'unit_id', 'code', 'name', 'qr_token', 'nfc_uid', 'latitude', 'longitude',
        'radius_m', 'instruction', 'checklist_template_id', 'active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'active' => 'boolean',
        ];
    }

    public static function qrPrefix(): string
    {
        return 'CP';
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function checklistTemplate(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    public function patrolRoutes(): BelongsToMany
    {
        return $this->belongsToMany(PatrolRoute::class, 'patrol_route_checkpoints')
            ->withPivot(['position', 'required'])
            ->withTimestamps();
    }

    public function scans(): HasMany
    {
        return $this->hasMany(PatrolScan::class);
    }
}
