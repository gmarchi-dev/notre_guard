<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'device_id', 'name', 'unit_id', 'last_security_guard_id',
        'user_agent', 'last_seen_at', 'last_sync_at', 'revoked',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'last_sync_at' => 'datetime',
            'revoked' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function lastSecurityGuard(): BelongsTo
    {
        return $this->belongsTo(SecurityGuard::class, 'last_security_guard_id');
    }
}
