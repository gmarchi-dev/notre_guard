<?php

namespace App\Models;

use App\Models\Concerns\HasQrToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Posto de serviço. O vigilante assume o posto lendo o QR Code afixado nele.
 */
class Post extends Model
{
    use HasQrToken;

    public const KINDS = [
        'fixed' => 'Fixo',
        'mobile' => 'Móvel',
        'reception' => 'Portaria/Recepção',
    ];

    protected $fillable = [
        'unit_id', 'name', 'kind', 'qr_token', 'latitude', 'longitude', 'active',
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
        return 'POST';
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }
}
