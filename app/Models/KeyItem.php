<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Uma chave física sob guarda da portaria.
 *
 * A situação (no quadro / emprestada) não é uma coluna: é derivada do
 * empréstimo em aberto. Coluna de status precisaria ser mantida em sincronia
 * com os empréstimos, e é exatamente aí que esse tipo de sistema começa a
 * mentir.
 */
class KeyItem extends Model
{
    /** Ver KeyHolder: default no model, não só no banco. */
    protected $attributes = ['active' => true];

    protected $fillable = [
        'unit_id', 'code', 'name', 'storage_location', 'notes', 'active',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(KeyLoan::class);
    }

    public function currentLoan(): HasOne
    {
        return $this->hasOne(KeyLoan::class)->whereNull('returned_at')->latestOfMany('released_at');
    }

    public function isOut(): bool
    {
        return $this->currentLoan()->exists();
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('active', true)
            ->whereDoesntHave('loans', fn (Builder $q) => $q->whereNull('returned_at'));
    }

    public function scopeOut(Builder $query): Builder
    {
        return $query->whereHas('loans', fn (Builder $q) => $q->whereNull('returned_at'));
    }

    public function label(): string
    {
        return "{$this->code} — {$this->name}";
    }
}
