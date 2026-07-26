<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionRun extends Model
{
    protected $fillable = [
        'dry_run', 'started_at', 'finished_at', 'policy', 'summary', 'error',
    ];

    protected function casts(): array
    {
        return [
            'dry_run' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'policy' => 'array',
            'summary' => 'array',
        ];
    }

    public function totalRemoved(): int
    {
        return array_sum(array_map(
            fn ($value) => is_int($value) ? $value : 0,
            $this->summary ?? [],
        ));
    }
}
