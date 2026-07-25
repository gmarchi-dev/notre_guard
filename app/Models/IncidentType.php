<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentType extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'default_classification', 'default_severity',
        'notify_supervision', 'active',
    ];

    protected function casts(): array
    {
        return [
            'notify_supervision' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(IncidentType::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(IncidentType::class, 'parent_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function fullName(): string
    {
        return $this->parent ? "{$this->parent->name} › {$this->name}" : $this->name;
    }
}
