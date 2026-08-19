<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\TransactionType;
use App\Queries\Results\CategorySpending;
use App\Support\Demo\Transaction;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Gasto por categoria num mês, da maior para a menor.
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
        $expenses = $transactions->filter(
            fn (Transaction $tx): bool => $tx->type === TransactionType::Expense
                && $tx->monthKey() === $month
        );

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
