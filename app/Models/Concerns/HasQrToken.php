<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Token opaco impresso no QR Code. Não sequencial de propósito: um código
 * previsível permitiria "passar" por um ponto sem estar nele.
 */
trait HasQrToken
{
    protected static function bootHasQrToken(): void
    {
        static::creating(function (self $model) {
            if (blank($model->qr_token)) {
                $model->qr_token = static::generateQrToken();
            }
        });
    }

    public static function generateQrToken(): string
    {
        do {
            $token = Str::lower(Str::random(24));
        } while (static::query()->where('qr_token', $token)->exists());

        return $token;
    }

    public function qrPayload(): string
    {
        return static::qrPrefix().':'.$this->qr_token;
    }

    abstract public static function qrPrefix(): string;
}
