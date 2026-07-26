<?php

namespace App\Filament\Portaria\Pages\Auth;

use App\Models\SecurityGuard;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

/**
 * Login da portaria por **matrícula**, não por e-mail.
 *
 * É a mesma credencial do aplicativo de campo: o que o vigilante sabe de cor e
 * o que está no crachá. Pedir e-mail aqui criaria uma segunda credencial para a
 * mesma pessoa.
 */
class Login extends BaseLogin
{
    public function getHeading(): string
    {
        return 'Portaria';
    }

    public function getSubheading(): ?string
    {
        return 'Entre com sua matrícula.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('registration')
            ->label('Matrícula')
            ->required()
            ->autocomplete('username')
            ->autocapitalize('characters')
            ->autofocus();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        // A matrícula vira o e-mail do usuário vinculado. Matrícula inexistente
        // devolve um e-mail impossível de propósito: a mensagem de erro fica a
        // mesma de senha errada, sem revelar quais matrículas existem.
        $email = SecurityGuard::query()
            ->where('registration', $data['registration'])
            ->where('active', true)
            ->with('user')
            ->first()
            ?->user
            ?->email;

        return [
            'email' => $email ?? '__matricula_inexistente__',
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'data.registration' => 'Matrícula ou senha inválida.',
        ]);
    }
}
