<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\Results\PeriodSummary;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Receitas, despesas e saldo de um período. Leitura pura — não é uma Action.
 *
 * Duas portas de entrada para a mesma soma, porque as duas telas chegam com
 * coisas diferentes na mão:
 *
 * - `handle()` soma no banco. É o caminho de quem só quer o total (Dashboard).
 * - `fromRows()` soma o que já está carregado. É o caminho da tela de Transações,
 *   que precisa das linhas para a tabela e cujo recorte vem de filtros quaisquer,
 *   não de um mês.
 */
final class MonthlySummary
{
    /**
     * @param  string|null  $month  chave "Y-m"; null soma tudo (saldo acumulado)
     */
    public function handle(User $user, ?string $month = null): PeriodSummary
    {
        $sums = $this->byType($user, $month)
            ->selectRaw('type, sum(amount_cents) as total')
            ->pluck('total', 'type');

        $counts = $this->byType($user, $month)
            ->selectRaw('type, count(*) as total')
            ->pluck('total', 'type');

        $income = Money::fromCents((int) $sums->get(TransactionType::Income->value, 0));
        $expense = Money::fromCents((int) $sums->get(TransactionType::Expense->value, 0));

        $incomeCount = (int) $counts->get(TransactionType::Income->value, 0);
        $expenseCount = (int) $counts->get(TransactionType::Expense->value, 0);

        return new PeriodSummary(
            income: $income,
            expense: $expense,
            balance: $income->minus($expense),
            incomeCount: $incomeCount,
            expenseCount: $expenseCount,
            count: $incomeCount + $expenseCount,
        );
    }

    /**
     * Mesma soma, sobre linhas já em mãos.
     *
     * @param  Collection<int, Transaction>  $transactions
     */
    public function fromRows(Collection $transactions): PeriodSummary
    {
        $income = $transactions->filter(
            fn (Transaction $tx): bool => $tx->type === TransactionType::Income
        );

        $expense = $transactions->filter(
            fn (Transaction $tx): bool => $tx->type === TransactionType::Expense
        );

        $incomeTotal = Money::fromCents((int) $income->sum('amount_cents'));
        $expenseTotal = Money::fromCents((int) $expense->sum('amount_cents'));

        return new PeriodSummary(
            income: $incomeTotal,
            expense: $expenseTotal,
            balance: $incomeTotal->minus($expenseTotal),
            incomeCount: $income->count(),
            expenseCount: $expense->count(),
            count: $transactions->count(),
        );
    }

    /**
     * Consulta nova, do recorte pedido, agrupada por tipo. Quem chama escolhe a
     * agregação — o resultado sai indexado pelo valor do enum.
     *
     * @return Builder<Transaction>
     */
    private function byType(User $user, ?string $month): Builder
    {
        $query = Transaction::query()->whereBelongsTo($user);

        if ($month !== null) {
            $query->inMonth($month);
        }

        return $query->groupBy('type');
    }
}
