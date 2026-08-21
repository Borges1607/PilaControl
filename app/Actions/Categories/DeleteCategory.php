<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Exceptions\CategoryInUse;
use App\Models\Category;

/**
 * `deleteCategory` do protótipo, com a guarda que o protótipo não tinha.
 */
final class DeleteCategory
{
    /**
     * @throws CategoryInUse quando existe lançamento na categoria
     */
    public function handle(Category $category): void
    {
        if ($category->isInUse()) {
            throw CategoryInUse::for($category);
        }

        // Os orçamentos vão junto — a migration os apaga em cascata. Faz
        // sentido: limite de uma categoria que não existe mais não é dado.
        $category->delete();
    }
}
