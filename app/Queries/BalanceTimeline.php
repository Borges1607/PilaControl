<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\TransactionType;
use App\Queries\Results\MonthPoint;
use App\Support\Demo\Transaction;
use App\Support\Money;
use App\Support\MonthLabel;
use Illuminate\Support\Collection;

/**
 * Série mensal de receitas e despesas, do mês mais antigo ao mais recente.
 */
final class BalanceTimeline
{
    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return Collection<int, MonthPoint>
     */
    public function handle(Collection $transactions): Collection
    {
        return $transactions
            ->groupBy(fn (Transaction $tx): string => $tx->monthKey())
            ->sortKeys()
            ->map(fn (Collection $group, string $month): MonthPoint => new MonthPoint(
                month: $month,
                label: MonthLabel::short($month),
                income: $this->total($group, TransactionType::Income),
                expense: $this->total($group, TransactionType::Expense),
            ))
            ->values();
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    private function total(Collection $transactions, TransactionType $type): Money
    {
        return Money::fromCents((int) $transactions
            ->filter(fn (Transaction $tx): bool => $tx->type === $type)
            ->sum('amount_cents'));
    }
}
