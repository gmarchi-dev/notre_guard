<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    protected $fillable = [
        'checklist_template_id', 'label', 'position', 'photo_required_when_nonconforming',
    ];

    protected function casts(): array
    {
        return ['photo_required_when_nonconforming' => 'boolean'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }
}
