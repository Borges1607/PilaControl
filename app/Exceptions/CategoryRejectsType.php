<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\TransactionType;
use App\Models\Category;
use RuntimeException;

/**
 * Lançamento de receita numa categoria de despesa, ou o contrário.
 *
 * A interface não deixa chegar aqui — o seletor só lista categorias compatíveis
 * e a validação confere. Isto é a regra guardada onde ela mora, na Action.
 */
final class CategoryRejectsType extends RuntimeException
{
    public static function for(Category $category, TransactionType $type): self
    {
        return new self(
            "A categoria {$category->name} não aceita lançamento de {$type->label()}."
        );
    }
}
