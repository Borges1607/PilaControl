<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

/**
 * Mesma lógica da `CategoryPolicy`: as consultas já saem da relação do usuário,
 * e a policy é a segunda tranca para id que chega por parâmetro de ação.
 */
class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return $this->owns($user, $transaction);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $this->owns($user, $transaction);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->owns($user, $transaction);
    }

    private function owns(User $user, Transaction $transaction): bool
    {
        return $transaction->user_id === $user->id;
    }
}
