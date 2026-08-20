<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\TransactionType;
use App\Queries\Results\CategorySpending;
use App\Support\Demo\Transaction;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Gasto por categoria, da maior para a menor.
 *
 * Dois recortes: um mês só (dashboard) ou um período aberto (relatórios).
 */
final class SpendingByCategory
{
    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  string  $month  chave "Y-m"
     * @return Collection<int, CategorySpending>
     */
    public function handle(Collection $transactions, string $month, ?int $limit = null): Collection
    {
        return $this->rank(
            $transactions->filter(fn (Transaction $tx): bool => $tx->monthKey() === $month),
            $limit,
        );
    }

    /**
     * Mesmo ranking, para todos os meses a partir de `$since`.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @param  string  $since  chave "Y-m" inclusiva
     * @return Collection<int, CategorySpending>
     */
    public function since(Collection $transactions, string $since, ?int $limit = null): Collection
    {
        return $this->rank(
            $transactions->filter(fn (Transaction $tx): bool => $tx->monthKey() >= $since),
            $limit,
        );
    }

    /**
     * @param  Collection<int, Transaction>  $scope  já recortado no período
     * @return Collection<int, CategorySpending>
     */
    private function rank(Collection $scope, ?int $limit): Collection
    {
        $expenses = $scope->filter(fn (Transaction $tx): bool => $tx->type === TransactionType::Expense);

        $total = Money::fromCents((int) $expenses->sum('amount_cents'));

        $rows = $expenses
            ->groupBy(fn (Transaction $tx): string => $tx->category_id)
            ->map(function (Collection $group) use ($total): CategorySpending {
                $subtotal = Money::fromCents((int) $group->sum('amount_cents'));

                return new CategorySpending(
                    category: $group->first()->category,
                    total: $subtotal,
                    share: $subtotal->percentOf($total),
                );
            })
            ->sortByDesc(fn (CategorySpending $row): int => $row->total->cents)
            ->values();

        return $limit === null ? $rows : $rows->take($limit);
    }
}
