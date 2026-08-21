<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Categories\CreateDefaultCategories;
use App\Models\User;

/**
 * Conta nova nasce com as categorias padrão — ver `Support\DefaultCategories`.
 *
 * Fica no observer porque há mais de um caminho de criação (cadastro, Google,
 * factory) e nenhum deles deveria carregar essa responsabilidade.
 */
class UserObserver
{
    public function __construct(private readonly CreateDefaultCategories $createDefaultCategories) {}

    public function created(User $user): void
    {
        $this->createDefaultCategories->handle($user);
    }
}
