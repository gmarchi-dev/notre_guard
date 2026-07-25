<?php

namespace App\Services\Sync;

use App\Models\Device;
use App\Models\SecurityGuard;
use Illuminate\Support\Carbon;

/**
 * Dados que valem para todo o lote de eventos: quem enviou, de qual aparelho e
 * quanto o relógio do aparelho está divergindo do servidor.
 */
class SyncContext
{
    public function __construct(
        public readonly SecurityGuard $guard,
        public readonly ?Device $device,
        public readonly Carbon $receivedAt,
        public readonly int $clockSkewSeconds = 0,
    ) {}

    /**
     * Acima de 5 minutos de divergência a hora do aparelho deixa de ser
     * confiável e todo evento do lote carrega a marca.
     */
    public function clockIsUntrustworthy(): bool
    {
        return abs($this->clockSkewSeconds) > 300;
    }

    public function deviceId(): ?string
    {
        return $this->device?->device_id;
    }
}
