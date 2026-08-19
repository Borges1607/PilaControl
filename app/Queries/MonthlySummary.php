<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\TransactionType;
use App\Queries\Results\PeriodSummary;
use App\Support\Demo\Transaction;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Receitas, despesas e saldo de um período. Leitura pura — não é uma Action.
 */
final class MonthlySummary
{
    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  string|null  $month  chave "Y-m"; null soma tudo (saldo acumulado)
     */
    public function handle(Collection $transactions, ?string $month = null): PeriodSummary
    {
        $scope = $month === null
            ? $transactions
            : $transactions->filter(fn (Transaction $tx): bool => $tx->monthKey() === $month);

        $income = $scope->filter(fn (Transaction $tx): bool => $tx->type === TransactionType::Income);
        $expense = $scope->filter(fn (Transaction $tx): bool => $tx->type === TransactionType::Expense);

        $incomeTotal = Money::fromCents((int) $income->sum('amount_cents'));
        $expenseTotal = Money::fromCents((int) $expense->sum('amount_cents'));

        return new PeriodSummary(
            income: $incomeTotal,
            expense: $expenseTotal,
            balance: $incomeTotal->minus($expenseTotal),
            incomeCount: $income->count(),
            expenseCount: $expense->count(),
            count: $scope->count(),
        );
    }
}
