<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Category;
use RuntimeException;

/**
 * Tentativa de apagar categoria que tem lançamento.
 *
 * A mensagem é de interface: quem captura é o componente, que a mostra no toast.
 */
final class CategoryInUse extends RuntimeException
{
    public static function for(Category $category): self
    {
        return new self(
            "A categoria {$category->name} tem lançamentos e não pode ser removida."
        );
    }
}
