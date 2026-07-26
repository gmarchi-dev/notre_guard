<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma retirada de chave. Registro do livro da portaria: quem levou, quando,
 * com que prazo, e se voltou.
 */
class KeyLoan extends Model
{
    protected $fillable = [
        'key_item_id', 'key_holder_id', 'unit_id',
        'released_by_user_id', 'received_by_user_id', 'shift_id',
        'released_at', 'due_at', 'returned_at', 'purpose', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function keyItem(): BelongsTo
    {
        return $this->belongsTo(KeyItem::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(KeyHolder::class, 'key_holder_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_user_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function isOpen(): bool
    {
        return $this->returned_at === null;
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at->isPast();
    }

    /** Atraso em minutos; para chave já devolvida, o atraso que houve. */
    public function overdueMinutes(): int
    {
        $reference = $this->returned_at ?? now();

        return $reference->gt($this->due_at)
            ? (int) round($this->due_at->diffInMinutes($reference, absolute: true))
            : 0;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('returned_at')->where('due_at', '<', now());
    }
}
