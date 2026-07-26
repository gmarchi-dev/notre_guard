<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

/**
 * Regras de aceite do login Google.
 *
 * Autenticar no Google não é o mesmo que ter acesso ao Notre Guard: o Google diz
 * quem a pessoa é, e este serviço diz se ela entra.
 */
class GoogleWorkspaceAuth
{
    public function enabled(): bool
    {
        return (bool) config('google.enabled');
    }

    public function hostedDomain(): string
    {
        return (string) config('google.hosted_domain');
    }

    /**
     * @throws RuntimeException com mensagem já pronta para exibir ao usuário
     */
    public function resolve(SocialiteUser $googleUser): User
    {
        $email = Str::lower((string) $googleUser->getEmail());

        if (blank($email)) {
            throw new RuntimeException('A conta Google não retornou um e-mail.');
        }

        if (! $this->belongsToDomain($email)) {
            throw new RuntimeException(
                'Use sua conta @'.$this->hostedDomain().' para entrar.',
            );
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            if (! config('google.allow_provisioning')) {
                // Recusa deliberada: conta é criada pelo administrador, não por
                // quem clica no botão. Ver config/google.php.
                throw new RuntimeException(
                    'Este e-mail não tem acesso ao Notre Guard. Procure a administração.',
                );
            }

            $user = User::create([
                'name' => $googleUser->getName() ?: $email,
                'email' => $email,
                'password' => Str::password(32),
                'role' => User::ROLE_GUARD,
            ]);
        }

        if (! $user->active) {
            throw new RuntimeException('Seu acesso está inativo. Procure a administração.');
        }

        $this->bind($user, $googleUser);

        return $user;
    }

    /**
     * Um google_id diferente do já vinculado significa outra conta com o mesmo
     * e-mail — o caso do endereço reaproveitado depois de um desligamento.
     * Recusar é mais seguro que assumir que é a mesma pessoa.
     */
    private function bind(User $user, SocialiteUser $googleUser): void
    {
        $googleId = (string) $googleUser->getId();

        if (filled($user->google_id) && $user->google_id !== $googleId) {
            throw new RuntimeException(
                'Este e-mail já está vinculado a outra conta Google. Procure a administração.',
            );
        }

        if (blank($user->google_id)) {
            $user->update([
                'google_id' => $googleId,
                'google_linked_at' => now(),
            ]);
        }
    }

    private function belongsToDomain(string $email): bool
    {
        return Str::endsWith($email, '@'.Str::lower($this->hostedDomain()));
    }
}
