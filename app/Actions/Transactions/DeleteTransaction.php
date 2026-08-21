<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Models\Transaction;

/**
 * `deleteTransaction` do protótipo.
 *
 * Uma linha hoje. Existe como Action porque é aqui que entra o que vier depois —
 * ajuste de meta, log, o que for — e o componente não precisa saber.
 */
final class DeleteTransaction
{
    public function handle(Transaction $transaction): void
    {
        $transaction->delete();
    }
}
