<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('services.google.client_id', 'client-id');
    config()->set('services.google.client_secret', 'client-secret');
    config()->set('services.google.redirect', null);
});

/**
 * @param  array<string, mixed>  $profile
 */
function fakeGoogle(array $profile = []): void
{
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'token-do-google']),
        'www.googleapis.com/oauth2/v3/userinfo' => Http::response(array_merge([
            'sub' => '1234567890',
            'email' => 'ana@gmail.com',
            'email_verified' => true,
            'name' => 'Ana Souza',
            'picture' => 'https://lh3.googleusercontent.com/foto',
        ], $profile)),
    ]);
}

it('esconde as rotas quando não há credenciais no .env', function (): void {
    config()->set('services.google.client_id', null);
    config()->set('services.google.client_secret', null);

    $this->get(route('google.redirect'))->assertNotFound();
    $this->get(route('google.callback'))->assertNotFound();
});

it('manda o visitante ao Google guardando o state na sessão', function (): void {
    $response = $this->get(route('google.redirect'));

    $state = session('google_oauth_state');

    expect($state)->toBeString()->toHaveLength(40);

    $response->assertRedirectContains('accounts.google.com/o/oauth2/v2/auth')
        ->assertRedirectContains('client_id=client-id')
        ->assertRedirectContains('state='.$state);
});

it('recusa o retorno com state diferente do guardado', function (): void {
    $this->withSession(['google_oauth_state' => 'o-state-certo'])
        ->get(route('google.callback', ['code' => 'abc', 'state' => 'outro-state']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    $this->assertGuest();
});

it('recusa o retorno quando o usuário nega o acesso', function (): void {
    $this->withSession(['google_oauth_state' => 'estado'])
        ->get(route('google.callback', ['error' => 'access_denied', 'state' => 'estado']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    $this->assertGuest();
});

it('cria a conta e abre a sessão no primeiro acesso', function (): void {
    fakeGoogle();

    // Conta nova pelo Google não tem senha: cai na tela de definir uma.
    $this->withSession(['google_oauth_state' => 'estado'])
        ->get(route('google.callback', ['code' => 'abc', 'state' => 'estado']))
        ->assertRedirect(route('password.create'));

    $user = User::query()->where('email', 'ana@gmail.com')->sole();

    expect($user->google_id)->toBe('1234567890')
        ->and($user->name)->toBe('Ana Souza')
        ->and($user->avatar_url)->toBe('https://lh3.googleusercontent.com/foto')
        ->and($user->password)->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

it('liga a conta Google a quem já se cadastrou com o mesmo e-mail', function (): void {
    $existing = User::factory()->create(['email' => 'ana@gmail.com', 'name' => 'Ana']);

    fakeGoogle();

    $this->withSession(['google_oauth_state' => 'estado'])
        ->get(route('google.callback', ['code' => 'abc', 'state' => 'estado']))
        ->assertRedirect(config('fortify.home'));

    $existing->refresh();

    expect(User::query()->count())->toBe(1)
        ->and($existing->google_id)->toBe('1234567890')
        // O nome de quem já era cadastrado não é sobrescrito pelo do Google.
        ->and($existing->name)->toBe('Ana');

    $this->assertAuthenticatedAs($existing);
});

it('recusa conta do Google com e-mail não verificado', function (): void {
    fakeGoogle(['email_verified' => false]);

    $this->withSession(['google_oauth_state' => 'estado'])
        ->get(route('google.callback', ['code' => 'abc', 'state' => 'estado']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    expect(User::query()->count())->toBe(0);
    $this->assertGuest();
});

it('recusa quando a troca do code por token falha', function (): void {
    Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);

    $this->withSession(['google_oauth_state' => 'estado'])
        ->get(route('google.callback', ['code' => 'abc', 'state' => 'estado']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    $this->assertGuest();
});
