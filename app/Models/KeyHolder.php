<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quem pode retirar chave. Cadastro próprio: professores, funcionários e
 * prestadores não têm conta no Notre Guard, e digitar nome livre a cada
 * retirada produziria "João", "joao silva" e "J. Silva" como três pessoas.
 */
class KeyHolder extends Model
{
    public const KINDS = [
        'staff' => 'Funcionário',
        'teacher' => 'Professor',
        'contractor' => 'Prestador',
        'other' => 'Outro',
    ];

    /**
     * Defaults no model, não só no banco: sem isto um registro criado em código
     * fica com active nulo até ser recarregado, e a liberação de chave é
     * recusada com "solicitante inativo". Mesma armadilha do model User.
     */
    protected $attributes = [
        'kind' => 'staff',
        'active' => true,
    ];

    protected $fillable = [
        'name', 'kind', 'department', 'document', 'phone', 'notes', 'active',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(KeyLoan::class);
    }

    public function openLoans(): HasMany
    {
        return $this->loans()->whereNull('returned_at');
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function label(): string
    {
        return $this->department
            ? "{$this->name} ({$this->department})"
            : $this->name;
    }
}
