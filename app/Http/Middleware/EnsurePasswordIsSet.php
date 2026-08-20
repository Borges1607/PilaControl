<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Segura quem entrou pelo Google e ainda não escolheu uma senha.
 *
 * A conta nasce sem senha (o Google não passa uma), e sem senha o usuário fica
 * preso: não consegue entrar por e-mail nem trocar a senha em Configurações, que
 * pede a atual. Este middleware força a escolha uma única vez, no primeiro acesso.
 */
final class EnsurePasswordIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user !== null && $user->password === null) {
            return $request->expectsJson()
                ? abort(403, 'Defina uma senha para continuar.')
                : redirect()->route('password.create');
        }

        return $next($request);
    }
}
