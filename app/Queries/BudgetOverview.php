<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use App\Queries\Results\BudgetRow;
use App\Queries\Results\BudgetTotals;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Linhas da tela de orçamento: quanto foi gasto contra o limite de cada categoria
 * que aceita despesa. Só entram categorias com gasto no mês ou com limite definido.
 *
 * O gasto e os limites vêm agregados do banco — duas consultas, não a tabela
 * inteira em memória.
 */
final class BudgetOverview
{
    /**
     * @param  string  $month  chave "Y-m"
     * @return Collection<int, BudgetRow>
     */
    public function handle(User $user, string $month): Collection
    {
        /** @var Collection<int, int> $limits */
        $limits = $user->budgets()->forMonth($month)->pluck('limit_cents', 'category_id');

        /** @var Collection<int, int> $spent */
        $spent = $user->transactions()
            ->where('type', TransactionType::Expense)
            ->inMonth($month)
            ->groupBy('category_id')
            ->selectRaw('category_id, sum(amount_cents) as total')
            ->pluck('total', 'category_id');

        // "Ambos" entra junto: a categoria aceita despesa, então o gasto dela é
        // gasto do mês. O protótipo só tinha categorias puras e não decidiu isto.
        return $user->categories()
            ->whereIn('type', [CategoryType::Expense, CategoryType::Both])
            ->inRegistryOrder()
            ->get()
            ->map(function (Category $category) use ($spent, $limits): BudgetRow {
                $categorySpent = Money::fromCents((int) $spent->get($category->id, 0));
                $limit = Money::fromCents((int) $limits->get($category->id, 0));

                return new BudgetRow(
                    category: $category,
                    spent: $categorySpent,
                    limit: $limit,
                    percent: $limit->isZero() ? null : $categorySpent->percentOf($limit),
                    over: ! $limit->isZero() && $categorySpent->cents > $limit->cents,
                );
            })
            ->reject(fn (BudgetRow $row): bool => $row->spent->isZero() && $row->limit->isZero())
            ->values();
    }

    /**
     * Totais do cabeçalho da tela.
     *
     * @param  Collection<int, BudgetRow>  $rows
     */
    public function totals(Collection $rows): BudgetTotals
    {
        return new BudgetTotals(
            budgeted: Money::fromCents((int) $rows->sum(fn (BudgetRow $row): int => $row->limit->cents)),
            spent: Money::fromCents((int) $rows->sum(fn (BudgetRow $row): int => $row->spent->cents)),
            available: Money::fromCents((int) $rows->sum(
                fn (BudgetRow $row): int => max(0, $row->limit->cents - $row->spent->cents)
            )),
        );
    }
}
