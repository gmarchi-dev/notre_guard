<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncBatch extends Model
{
    protected $fillable = [
        'security_guard_id', 'device_id', 'items_total', 'items_accepted',
        'items_duplicated', 'items_failed', 'errors',
    ];

    protected function casts(): array
    {
        return ['errors' => 'array'];
    }

    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(SecurityGuard::class);
    }
}
