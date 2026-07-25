<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'name', 'code', 'address', 'latitude', 'longitude', 'radius_m', 'active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'active' => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(Checkpoint::class);
    }

    public function patrolRoutes(): HasMany
    {
        return $this->hasMany(PatrolRoute::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }
}
