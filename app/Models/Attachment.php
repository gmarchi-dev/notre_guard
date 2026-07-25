<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Evidência (foto, vídeo, áudio). Criada como "pending" quando o evento chega
 * do dispositivo e passa a "stored" quando o binário é recebido — o upload é
 * uma requisição separada, para não travar a sincronização dos eventos.
 */
class Attachment extends Model
{
    protected $fillable = [
        'uuid', 'attachable_type', 'attachable_id', 'path', 'original_name',
        'mime', 'size_bytes', 'sha256', 'captured_at', 'latitude', 'longitude', 'status',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isStored(): bool
    {
        return $this->status === 'stored';
    }
}
