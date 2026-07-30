<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Restringe o resource à unidade do gestor logado.
 *
 * Admin e supervisão enxergam todas as unidades. O gestor de unidade só vê a
 * dele - sem isso, dar acesso a um gestor significaria expor a operação de todas
 * as unidades do colégio.
 *
 * O resource declara em qual coluna (ou caminho de relacionamento) está a
 * unidade, via $unitScopeColumn ou $unitScopeRelation.
 */
trait ScopedToUnit
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user?->isScopedToUnit()) {
            return $query;
        }

        return static::applyUnitScope($query, $user->unit_id);
    }

    /**
     * Ponto de extensão para resources com regra própria - sobrescrever isto, e
     * nunca getEloquentQuery(), porque chamar Resource::getEloquentQuery()
     * estaticamente perde o late static binding e o model vem vazio.
     */
    protected static function applyUnitScope(Builder $query, int $unitId): Builder
    {
        $relation = static::unitScopeRelation();

        if ($relation !== null) {
            return $query->whereHas($relation, fn (Builder $q) => $q->whereKey($unitId));
        }

        return $query->where(static::unitScopeColumn(), $unitId);
    }

    protected static function unitScopeColumn(): string
    {
        return 'unit_id';
    }

    /**
     * Caminho de relacionamento até a unidade, para models que não têm unit_id.
     */
    protected static function unitScopeRelation(): ?string
    {
        return null;
    }
}
