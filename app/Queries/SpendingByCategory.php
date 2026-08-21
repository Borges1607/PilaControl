<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\Results\CategorySpending;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Gasto por categoria, da maior para a menor.
 *
 * Dois recortes: um mês só (dashboard) ou um período aberto (relatórios). A soma
 * sai agrupada do banco; só as categorias que aparecem no resultado são carregadas.
 */
final class SpendingByCategory
{
    /**
     * @param  string  $month  chave "Y-m"
     * @return Collection<int, CategorySpending>
     */
    public function handle(User $user, string $month, ?int $limit = null): Collection
    {
        return $this->rank(
            $this->expenses($user)->inMonth($month),
            $user,
            $limit,
        );
    }

    /**
     * Mesmo ranking, para todos os meses a partir de `$since`.
     *
     * @param  string  $since  chave "Y-m" inclusiva
     * @return Collection<int, CategorySpending>
     */
    public function since(User $user, string $since, ?int $limit = null): Collection
    {
        return $this->rank(
            $this->expenses($user)->sinceMonth($since),
            $user,
            $limit,
        );
    }

    /**
     * @return Builder<Transaction>
     */
    private function expenses(User $user): Builder
    {
        return Transaction::query()
            ->whereBelongsTo($user)
            ->where('type', TransactionType::Expense);
    }

    /**
     * @param  Builder<Transaction>  $scope  já recortado no período
     * @return Collection<int, CategorySpending>
     */
    private function rank(Builder $scope, User $user, ?int $limit): Collection
    {
        /** @var Collection<int, int|string> $totals */
        $totals = $scope
            ->groupBy('category_id')
            ->selectRaw('category_id, sum(amount_cents) as total')
            ->orderByRaw('total desc')
            ->pluck('total', 'category_id');

        // A fatia é sobre a despesa inteira do período, não sobre o recorte do
        // limite: o total sai antes do `take`.
        $total = Money::fromCents((int) $totals->sum());

        if ($limit !== null) {
            $totals = $totals->take($limit);
        }

        $categories = $user->categories()
            ->whereKey($totals->keys()->all())
            ->get()
            ->keyBy('id');

        return $totals
            ->map(function (int|string $cents, int $categoryId) use ($categories, $total): ?CategorySpending {
                $category = $categories->get($categoryId);

                if (! $category instanceof Category) {
                    return null;
                }

                $subtotal = Money::fromCents((int) $cents);

                return new CategorySpending(
                    category: $category,
                    total: $subtotal,
                    share: $subtotal->percentOf($total),
                );
            })
            ->filter()
            ->values();
    }
}
