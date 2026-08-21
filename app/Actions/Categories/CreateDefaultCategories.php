<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Models\User;
use App\Support\DefaultCategories;

/**
 * Dá a uma conta nova o conjunto padrão de categorias.
 *
 * Roda no `UserObserver`, não no cadastro: assim vale para todo caminho de
 * entrada — formulário, Google, factory de teste, tinker — e nenhum deles
 * precisa lembrar de chamar.
 */
final class CreateDefaultCategories
{
    public function handle(User $user): void
    {
        // Idempotente de propósito: quem já tem categoria não recebe de novo.
        if ($user->categories()->exists()) {
            return;
        }

        $user->categories()->createMany(DefaultCategories::all());
    }
}
