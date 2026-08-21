<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Queries\Results\PeriodSummary;
use App\Support\Demo\Transaction as DemoTransaction;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Receitas, despesas e saldo de um período. Leitura pura — não é uma Action.
 *
 * A união no tipo é da transição: a tela de Transações já manda `Models\Transaction`,
 * Dashboard e Relatórios ainda mandam o stand-in do `DemoData`. As duas classes
 * expõem `type`, `amount_cents` e `monthKey()`, que é tudo que esta soma lê. Some
 * quando a última tela sair do `DemoData` — ver 3.2 do documento de estrutura.
 *
 * A união é de coleções, não dentro da coleção: o `TValue` do `Collection` é
 * invariante, então `Collection<Transaction>` não passa por `Collection<A|B>`.
 */
final class MonthlySummary
{
    /**
     * @param  Collection<int, Transaction>|Collection<int, DemoTransaction>  $transactions
     * @param  string|null  $month  chave "Y-m"; null soma tudo (saldo acumulado)
     */
    public function handle(Collection $transactions, ?string $month = null): PeriodSummary
    {
        $scope = $month === null
            ? $transactions
            : $transactions->filter(fn (Transaction|DemoTransaction $tx): bool => $tx->monthKey() === $month);

        $income = $scope->filter(fn (Transaction|DemoTransaction $tx): bool => $tx->type === TransactionType::Income);
        $expense = $scope->filter(fn (Transaction|DemoTransaction $tx): bool => $tx->type === TransactionType::Expense);

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
