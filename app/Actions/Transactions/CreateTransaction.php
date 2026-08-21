<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Enums\TransactionType;
use App\Exceptions\CategoryRejectsType;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\Money;
use Carbon\CarbonInterface;

/**
 * `addTransaction` do protótipo.
 *
 * A categoria é o ponto de partida, não o usuário: o lançamento é do mesmo dono
 * da gaveta em que entra, então não há como criar um para a conta errada.
 */
final class CreateTransaction
{
    /**
     * @throws CategoryRejectsType quando o tipo não cabe na categoria
     */
    public function handle(
        Category $category,
        TransactionType $type,
        string $description,
        Money $amount,
        CarbonInterface $date,
        ?string $notes = null,
    ): Transaction {
        if (! $category->accepts($type)) {
            throw CategoryRejectsType::for($category, $type);
        }

        $transaction = $category->transactions()->make([
            'date' => $date,
            'description' => trim($description),
            // Sempre positivo: o sinal na tela vem do tipo, não do valor.
            'amount_cents' => $amount->abs()->cents,
            'type' => $type,
            'notes' => $notes === null || trim($notes) === '' ? null : trim($notes),
        ]);

        $transaction->user_id = $category->user_id;
        $transaction->save();

        return $transaction;
    }
}
