<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificação')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                ]),

            Section::make('Acesso')
                ->columns(2)
                ->schema([
                    Select::make('role')
                        ->label('Perfil')
                        ->options(User::ROLES)
                        ->default(User::ROLE_GUARD)
                        ->required()
                        ->live()
                        ->helperText('Vigilante usa o aplicativo de campo e não entra no painel.'),

                    Select::make('unit_id')
                        ->label('Unidade')
                        ->relationship('unit', 'name')
                        ->searchable()
                        ->preload()
                        // Sem unidade, o gestor enxergaria a operação de todo o
                        // colégio — ver ScopedToUnit.
                        ->required(fn (Get $get) => $get('role') === User::ROLE_UNIT_MANAGER)
                        ->visible(fn (Get $get) => $get('role') === User::ROLE_UNIT_MANAGER)
                        ->helperText('O gestor só enxerga esta unidade.'),

                    TextInput::make('password')
                        ->label('Senha')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                        ->helperText(fn (string $operation) => $operation === 'edit'
                            ? 'Deixe em branco para manter a senha atual.'
                            : 'Mínimo de 8 caracteres.'),

                    Toggle::make('active')
                        ->label('Ativo')
                        ->default(true)
                        ->helperText('Usuário inativo não entra no painel nem no aplicativo.'),
                ]),

            Section::make('Permissões')
                ->description('O perfil diz o que a pessoa é na operação; a permissão diz o que ela pode operar.')
                ->schema([
                    CheckboxList::make('permissions')
                        ->label('Módulos liberados')
                        ->options(User::PERMISSIONS)
                        ->descriptions([
                            User::PERMISSION_KEYS => 'Libera o painel da portaria em /portaria: quadro de chaves, retiradas e solicitantes.',
                        ])
                        // O administrador tem tudo por definição; oferecer a
                        // caixa sugeriria que desmarcar faria diferença.
                        ->visible(fn (Get $get) => $get('role') !== User::ROLE_ADMIN)
                        ->helperText('Nenhuma permissão é concedida automaticamente.'),

                    Placeholder::make('admin_note')
                        ->label('Permissões')
                        ->content('Administrador tem acesso a todos os módulos por definição.')
                        ->visible(fn (Get $get) => $get('role') === User::ROLE_ADMIN),
                ]),
        ]);
    }
}
