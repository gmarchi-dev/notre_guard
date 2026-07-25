<?php

namespace App\Filament\Resources\SecurityGuards\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class SecurityGuardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pessoa')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->label('Login')
                        ->relationship(
                            'user',
                            'name',
                            // Um login por vigilante: esconde quem já tem cadastro.
                            fn (Builder $query, ?int $currentUserId = null) => $query
                                ->where('role', User::ROLE_GUARD)
                                ->whereDoesntHave('securityGuard'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (User $record) => "{$record->name} ({$record->email})")
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Nome')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label('E-mail')
                                ->email()
                                ->required()
                                ->unique('users', 'email'),
                            TextInput::make('password')
                                ->label('Senha')
                                ->password()
                                ->revealable()
                                ->minLength(8)
                                ->required()
                                ->helperText('O vigilante entra no aplicativo com a matrícula e esta senha.'),
                        ])
                        ->createOptionUsing(fn (array $data) => User::create([
                            ...$data,
                            'password' => Hash::make($data['password']),
                            'role' => User::ROLE_GUARD,
                        ])->getKey())
                        ->createOptionModalHeading('Novo login de vigilante')
                        ->columnSpanFull(),

                    TextInput::make('registration')
                        ->label('Matrícula')
                        ->helperText('É a credencial de acesso ao aplicativo de campo.')
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true),

                    TextInput::make('phone')
                        ->label('Telefone')
                        ->tel()
                        ->maxLength(20),
                ]),

            Section::make('Habilitação e lotação')
                ->columns(3)
                ->schema([
                    Select::make('default_unit_id')
                        ->label('Unidade')
                        ->relationship('defaultUnit', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Define o que o aparelho baixa no início do turno.'),

                    TextInput::make('professional_id')
                        ->label('Registro profissional')
                        ->maxLength(30),

                    TextInput::make('refresher_valid_until')
                        ->label('Reciclagem válida até')
                        ->type('date')
                        ->helperText('Exigência da Lei 14.967/2024. Vencida, não bloqueia o app — apenas alerta.'),

                    Toggle::make('active')
                        ->label('Ativo')
                        ->default(true),
                ]),
        ]);
    }
}
