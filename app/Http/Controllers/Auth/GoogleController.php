<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fluxo "Authorization Code" do Google, falado direto por HTTP.
 *
 * O laravel/socialite resolveria isto em três linhas, mas ele ainda exige
 * Guzzle 7 e o projeto está no 8 — instalá-lo rebaixaria a dependência para o
 * app inteiro. São dois endpoints e um userinfo; não vale o downgrade.
 *
 * Se um dia o Socialite suportar Guzzle 8, esta classe some e vira
 * `Socialite::driver('google')`. As colunas em `users` continuam as mesmas.
 */
final class GoogleController extends Controller
{
    private const AUTHORIZE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    /**
     * Chave do `state` na sessão — defesa de CSRF exigida pelo OAuth2.
     */
    private const STATE_KEY = 'google_oauth_state';

    /**
     * Manda o visitante para a tela de consentimento do Google.
     */
    public function redirect(Request $request): RedirectResponse
    {
        abort_unless(self::isConfigured(), 404);

        $state = Str::random(40);

        $request->session()->put(self::STATE_KEY, $state);

        return redirect()->away(self::AUTHORIZE_URL.'?'.http_build_query([
            'client_id' => (string) config('services.google.client_id'),
            'redirect_uri' => self::redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]));
    }

    /**
     * Recebe o retorno do Google, identifica o usuário e abre a sessão.
     */
    public function callback(Request $request): RedirectResponse
    {
        abort_unless(self::isConfigured(), 404);

        $expected = $request->session()->pull(self::STATE_KEY);
        $received = (string) $request->query('state', '');

        // Recusa cedo: o usuário negou o acesso, ou o `state` não confere.
        if ($request->filled('error') || ! is_string($expected) || ! hash_equals($expected, $received)) {
            return $this->failed();
        }

        $code = (string) $request->query('code', '');

        if ($code === '') {
            return $this->failed();
        }

        $token = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => (string) config('services.google.client_id'),
            'client_secret' => (string) config('services.google.client_secret'),
            'redirect_uri' => self::redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if ($token->failed() || ! is_string($token->json('access_token'))) {
            return $this->failed();
        }

        $profile = Http::withToken((string) $token->json('access_token'))->get(self::USERINFO_URL);

        if ($profile->failed()) {
            return $this->failed();
        }

        $googleId = $profile->json('sub');
        $email = $profile->json('email');

        // `email_verified` chega como booleano ou como a string "true".
        $verified = filter_var($profile->json('email_verified'), FILTER_VALIDATE_BOOLEAN);

        if (! is_string($googleId) || ! is_string($email) || ! $verified) {
            return $this->failed(__('Não foi possível confirmar seu e-mail no Google.'));
        }

        $user = $this->resolveUser(
            googleId: $googleId,
            email: $email,
            name: is_string($profile->json('name')) ? $profile->json('name') : Str::before($email, '@'),
            avatar: is_string($profile->json('picture')) ? $profile->json('picture') : null,
        );

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        // Conta nova pelo Google não tem senha; o middleware `password.set`
        // barraria o dashboard de qualquer forma, então já mandamos para lá.
        return $user->password === null
            ? redirect()->route('password.create')
            : redirect()->intended(config('fortify.home'));
    }

    /**
     * Acha o usuário pelo id do Google, senão pelo e-mail — e cria se não existir.
     *
     * Casar pelo e-mail é o que liga a conta Google a quem já se cadastrou com
     * senha. É seguro porque o Google só chega aqui com `email_verified`.
     */
    private function resolveUser(string $googleId, string $email, string $name, ?string $avatar): User
    {
        $user = User::query()->where('google_id', $googleId)->first()
            ?? User::query()->where('email', $email)->first()
            ?? new User;

        $user->forceFill(array_filter([
            'google_id' => $googleId,
            'avatar_url' => $avatar,
            'name' => $user->exists ? $user->name : $name,
            'email' => $email,
            // O Google já verificou o endereço; não faz sentido pedir de novo.
            'email_verified_at' => $user->email_verified_at ?? Date::now(),
        ], fn (mixed $value): bool => $value !== null))->save();

        return $user;
    }

    /**
     * Volta ao login com o aviso no topo do cartão.
     */
    private function failed(?string $message = null): RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'google' => $message ?? __('Não foi possível entrar com o Google. Tente novamente.'),
        ]);
    }

    /**
     * O botão só aparece, e as rotas só respondem, com as credenciais no `.env`.
     */
    public static function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    private static function redirectUri(): string
    {
        $configured = config('services.google.redirect');

        return is_string($configured) && $configured !== ''
            ? $configured
            : route('google.callback');
    }
}
