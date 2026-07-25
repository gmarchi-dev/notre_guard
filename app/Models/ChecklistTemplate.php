<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplate extends Model
{
    protected $fillable = ['unit_id', 'name', 'description', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('position');
    }
}
