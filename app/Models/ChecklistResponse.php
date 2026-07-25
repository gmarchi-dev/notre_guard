<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ChecklistResponse extends Model
{
    public const ANSWERS = [
        'conforming' => 'Conforme',
        'nonconforming' => 'Não conforme',
        'not_applicable' => 'Não se aplica',
    ];

    protected $fillable = [
        'uuid', 'patrol_scan_id', 'checklist_item_id', 'answer', 'note',
    ];

    public function patrolScan(): BelongsTo
    {
        return $this->belongsTo(PatrolScan::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class, 'checklist_item_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
