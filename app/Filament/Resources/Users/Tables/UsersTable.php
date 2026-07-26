<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('role')
                    ->label('Perfil')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => User::ROLES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        User::ROLE_ADMIN => 'danger',
                        User::ROLE_SUPERVISOR => 'warning',
                        User::ROLE_UNIT_MANAGER => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('unit_scope')
                    ->label('Unidade')
                    // Cada perfil guarda a unidade em lugar diferente: o gestor em
                    // users.unit_id, o vigilante no cadastro de vigilante, e admin
                    // e supervisão não têm restrição nenhuma.
                    ->state(fn (User $record) => match ($record->role) {
                        User::ROLE_UNIT_MANAGER => $record->unit?->name ?? 'sem unidade',
                        User::ROLE_GUARD => $record->securityGuard?->defaultUnit?->name ?? '—',
                        default => 'todas',
                    })
                    ->color(fn (User $record) => $record->role === User::ROLE_UNIT_MANAGER && ! $record->unit_id
                        ? 'danger'
                        : null),
                TextColumn::make('securityGuard.registration')
                    ->label('Matrícula')
                    ->placeholder('—')
                    ->description(fn (User $record) => $record->securityGuard ? null : 'sem cadastro de vigilante'),
                TextColumn::make('permissions')
                    ->label('Módulos')
                    ->badge()
                    ->state(fn (User $record) => $record->grantedPermissionLabels() ?: ['—'])
                    ->color(fn (User $record) => $record->grantedPermissionLabels() ? 'info' : 'gray'),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('role')
                    ->label('Perfil')
                    ->options(User::ROLES),
                Filter::make('keys')
                    ->label('Com controle de chaves')
                    ->query(fn (Builder $query) => $query
                        ->where('role', User::ROLE_ADMIN)
                        ->orWhereJsonContains('permissions', User::PERMISSION_KEYS)),
                TernaryFilter::make('active')->label('Ativo'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
