<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Autenticar no Google não é o mesmo que ter acesso ao Notre Guard. O Google
 * diz quem a pessoa é; estes testes cobrem quem entra.
 */
class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private const DOMAIN = 'notredamecampinas.net.br';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'google.enabled' => true,
            'google.hosted_domain' => self::DOMAIN,
            'google.allow_provisioning' => false,
        ]);
    }

    private function fakeGoogleUser(string $email, string $id = 'google-123', string $name = 'Fulano'): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getName')->andReturn($name);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    // ------------------------------------------------------------- desligado

    public function test_routes_do_not_exist_while_the_feature_is_off(): void
    {
        // 404 e não uma tela de erro: com o recurso desligado, o caminho não
        // deve nem revelar que existe.
        config(['google.enabled' => false]);

        $this->get(route('auth.google.redirect'))->assertNotFound();
        $this->get(route('auth.google.callback'))->assertNotFound();
    }

    public function test_login_page_hides_the_button_while_the_feature_is_off(): void
    {
        config(['google.enabled' => false]);

        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee('Entrar com Google');
    }

    public function test_login_page_shows_the_button_when_enabled(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Entrar com Google');
    }

    // ---------------------------------------------------------------- aceite

    public function test_existing_active_user_gets_in_and_is_bound_to_the_account(): void
    {
        $user = User::factory()->create([
            'email' => 'supervisao@'.self::DOMAIN,
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $this->fakeGoogleUser('supervisao@'.self::DOMAIN, 'google-abc');

        $this->get(route('auth.google.callback'))->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-abc', $user->refresh()->google_id);
        $this->assertNotNull($user->google_linked_at);
    }

    public function test_email_comparison_ignores_case(): void
    {
        $user = User::factory()->create(['email' => 'gestor@'.self::DOMAIN]);

        $this->fakeGoogleUser('Gestor@'.strtoupper(self::DOMAIN));

        $this->get(route('auth.google.callback'))->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    // ---------------------------------------------------------------- recusa

    public function test_account_outside_the_domain_is_rejected(): void
    {
        User::factory()->create(['email' => 'alguem@gmail.com']);

        $this->fakeGoogleUser('alguem@gmail.com');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('filament.admin.auth.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unknown_email_is_rejected_instead_of_provisioned(): void
    {
        // Regra deliberada: conta é criada pelo administrador. Provisionar
        // qualquer pessoa do domínio abriria a operação para a escola inteira.
        $this->fakeGoogleUser('desconhecido@'.self::DOMAIN);

        $this->get(route('auth.google.callback'))->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_inactive_user_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'desligado@'.self::DOMAIN,
            'active' => false,
        ]);

        $this->fakeGoogleUser('desligado@'.self::DOMAIN);

        $this->get(route('auth.google.callback'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_reused_email_with_a_different_google_account_is_rejected(): void
    {
        // Endereço reaproveitado depois de um desligamento: assumir que é a
        // mesma pessoa entregaria o acesso do antecessor ao sucessor.
        User::factory()->create([
            'email' => 'coordenacao@'.self::DOMAIN,
            'google_id' => 'google-antigo',
            'google_linked_at' => now()->subYear(),
        ]);

        $this->fakeGoogleUser('coordenacao@'.self::DOMAIN, 'google-novo');

        $this->get(route('auth.google.callback'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // -------------------------------------------------- provisionamento opt-in

    public function test_provisioning_creates_a_guard_without_panel_access_when_enabled(): void
    {
        // Se algum dia for ligado, o perfil criado é o de menor privilégio -
        // vigilante não entra no painel.
        config(['google.allow_provisioning' => true]);

        $this->fakeGoogleUser('novo@'.self::DOMAIN, 'google-novo', 'Pessoa Nova');

        $this->get(route('auth.google.callback'))->assertRedirect();

        $created = User::firstOrFail();

        $this->assertSame('novo@'.self::DOMAIN, $created->email);
        $this->assertSame(User::ROLE_GUARD, $created->role);
        $this->assertFalse($created->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')));
    }

    // ------------------------------------------------------------- convivência

    public function test_password_login_keeps_working_with_google_enabled(): void
    {
        // A adoção é faseada: enquanto o Google não estiver em operação plena,
        // senha local continua sendo o caminho.
        $user = User::factory()->create([
            'email' => 'admin@'.self::DOMAIN,
            'password' => bcrypt('segredo123'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertTrue(Auth::attempt(['email' => $user->email, 'password' => 'segredo123']));
    }
}
