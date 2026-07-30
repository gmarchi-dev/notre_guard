<?php

namespace App\Services\Sync;

use RuntimeException;

class SyncEventException extends RuntimeException
{
    // "errorCode" e não "code": Exception::$code já existe e não é readonly.
    private function __construct(
        string $message,
        public readonly bool $retryable,
        public readonly string $errorCode,
    ) {
        parent::__construct($message);
    }

    /**
     * Evento chegou antes do seu pai (turno ou ronda ainda não sincronizados).
     * O dispositivo deve manter na fila e tentar de novo.
     */
    public static function retryable(string $message, string $errorCode = 'parent_missing'): self
    {
        return new self($message, true, $errorCode);
    }

    /**
     * Evento inválido - retentar não resolve. O dispositivo descarta e o erro
     * fica registrado no lote para investigação.
     */
    public static function permanent(string $message, string $errorCode = 'invalid_event'): self
    {
        return new self($message, false, $errorCode);
    }
}
