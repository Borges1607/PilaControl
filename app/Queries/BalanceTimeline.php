<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\Results\MonthPoint;
use App\Support\Money;
use App\Support\MonthLabel;
use Illuminate\Support\Collection;

/**
 * Série mensal de receitas e despesas, do mês mais antigo ao mais recente.
 *
 * Esta é a única Query que ainda agrupa em PHP, e é de propósito: extrair o mês
 * de uma data em SQL só existe em dialeto (`strftime` no SQLite, `date_format` no
 * MySQL), e o resto do projeto evita isso — ver o escopo `inMonth` do model. O que
 * sai do banco são três colunas do recorte pedido, não a tabela.
 */
final class BalanceTimeline
{
    /**
     * @param  string|null  $since  chave "Y-m" inclusiva; null traz a série inteira
     * @return Collection<int, MonthPoint>
     */
    public function handle(User $user, ?string $since = null): Collection
    {
        $query = Transaction::query()->whereBelongsTo($user);

        if ($since !== null) {
            $query->sinceMonth($since);
        }

        return $query
            ->get(['date', 'type', 'amount_cents'])
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
