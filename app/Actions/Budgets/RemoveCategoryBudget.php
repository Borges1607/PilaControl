<?php

declare(strict_types=1);

namespace App\Actions\Budgets;

use App\Models\Category;

/**
 * Tira o limite de uma categoria em um mês.
 *
 * Não tem par no protótipo, que guardava zero no lugar. Aqui "sem limite" é a
 * ausência do registro — a tela mostra "Definir limite" exatamente nesse caso.
 */
final class RemoveCategoryBudget
{
    /**
     * @param  string  $month  chave "Y-m"
     */
    public function handle(Category $category, string $month): void
    {
        $category->budgets()->forMonth($month)->delete();
    }
}
