<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Primeira senha de quem se cadastrou pelo Google.
 *
 * Não é o reset do Fortify: ali existe uma senha e um token de e-mail no meio.
 * Aqui a sessão já está aberta e o campo está nulo — só há o que preencher.
 */
final class CreatePasswordController extends Controller
{
    use PasswordValidationRules;

    public function create(): View|RedirectResponse
    {
        // Quem já tem senha não tem o que fazer nesta tela.
        if (Auth::user()?->password !== null) {
            return redirect()->intended(config('fortify.home'));
        }

        return view('livewire.auth.create-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user === null || $user->password !== null) {
            return redirect()->intended(config('fortify.home'));
        }

        $validated = $request->validate(
            ['password' => $this->passwordRules()],
            attributes: ['password' => 'senha'],
        );

        $user->forceFill(['password' => $validated['password']])->save();

        return redirect()->intended(config('fortify.home'))
            ->with('status', 'Senha definida. Agora você também pode entrar com e-mail e senha.');
    }
}
