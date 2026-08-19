<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Queries\Results\BudgetRow;
use App\Queries\Results\BudgetTotals;
use App\Support\Demo\Category;
use App\Support\Demo\Transaction;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Linhas da tela de orçamento: quanto foi gasto contra o limite de cada categoria
 * de despesa. Só entram categorias com gasto no mês ou com limite definido.
 */
final class BudgetOverview
{
    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<string, Category>  $categories
     * @param  array<string, int>  $limits  id da categoria => limite em centavos
     * @param  string  $month  chave "Y-m"
     * @return Collection<int, BudgetRow>
     */
    public function handle(Collection $transactions, Collection $categories, array $limits, string $month): Collection
    {
        $spentByCategory = $transactions
            ->filter(fn (Transaction $tx): bool => $tx->type === TransactionType::Expense
                && $tx->monthKey() === $month)
            ->groupBy(fn (Transaction $tx): string => $tx->category_id)
            ->map(fn (Collection $group): int => (int) $group->sum('amount_cents'));

        return $categories
            ->filter(fn (Category $category): bool => $category->type === CategoryType::Expense)
            ->map(function (Category $category) use ($spentByCategory, $limits): BudgetRow {
                $spent = Money::fromCents($spentByCategory->get($category->id, 0));
                $limit = Money::fromCents($limits[$category->id] ?? 0);

                return new BudgetRow(
                    category: $category,
                    spent: $spent,
                    limit: $limit,
                    percent: $limit->isZero() ? null : $spent->percentOf($limit),
                    over: ! $limit->isZero() && $spent->cents > $limit->cents,
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
