<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Enums\TransactionType;
use App\Exceptions\CategoryRejectsType;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CreateInstallmentTransactions
{
    public function __construct(private readonly CreateTransaction $createTransaction) {}

    /**
     * @param  list<Money>  $amounts  o valor de cada parcela, na ordem dos meses
     * @return Collection<int, Transaction>
     *
     * @throws CategoryRejectsType quando o tipo não cabe na categoria
     */
    public function handle(
        Category $category,
        TransactionType $type,
        string $description,
        array $amounts,
        CarbonInterface $firstDate,
        ?string $notes = null,
    ): Collection {
        $total = count($amounts);

        if ($total < 2) {
            throw new InvalidArgumentException('Um parcelamento tem pelo menos duas parcelas.');
        }

        $label = trim($description);
        $start = $firstDate->copy();

        return DB::transaction(fn (): Collection => Collection::make($amounts)
            ->values()
            ->map(fn (Money $amount, int $index): Transaction => $this->createTransaction->handle(
                category: $category,
                type: $type,
                description: sprintf('%s (%d/%d)', $label, $index + 1, $total),
                amount: $amount,
                date: $start->copy()->addMonthsNoOverflow($index),
                notes: $notes,
            )));
    }
}
