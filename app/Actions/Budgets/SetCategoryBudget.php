<?php

declare(strict_types=1);

namespace App\Actions\Budgets;

use App\Models\Budget;
use App\Models\Category;
use App\Support\Money;
use InvalidArgumentException;

/**
 * `updateBudget` do protótipo: define o limite de uma categoria em um mês.
 *
 * É upsert sobre a chave única (`user_id`, `category_id`, `month`) — a mesma que
 * a migration declara. Como no `CreateTransaction`, a categoria é o ponto de
 * partida e o dono sai dela.
 */
final class SetCategoryBudget
{
    /**
     * @param  string  $month  chave "Y-m"
     */
    public function handle(Category $category, string $month, Money $limit): Budget
    {
        if ($limit->cents <= 0) {
            // Limite zero não é registro de limite: quem quer zerar remove.
            throw new InvalidArgumentException(
                'Limite tem de ser positivo; para tirar o limite use RemoveCategoryBudget.'
            );
        }

        $budget = $category->budgets()->firstOrNew(['month' => $month]);

        $budget->limit_cents = $limit->cents;
        $budget->user_id = $category->user_id;
        $budget->save();

        return $budget;
    }
}
