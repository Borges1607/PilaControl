<?php

declare(strict_types=1);

namespace App\Actions\Goals;

use App\Models\Goal;
use App\Support\Money;
use InvalidArgumentException;

/**
 * `updateGoal` do protótipo, com o nome da operação que ele de fato faz: o único
 * campo que a tela muda depois de criada é o quanto já foi guardado.
 *
 * O aporte não passa do alvo — quem deposita mais do que falta completa a meta e
 * o resto é ignorado, como no protótipo.
 */
final class DepositIntoGoal
{
    public function handle(Goal $goal, Money $amount): Goal
    {
        if ($amount->cents <= 0) {
            throw new InvalidArgumentException('Aporte tem de ser positivo.');
        }

        $room = max(0, $goal->target_cents - $goal->current_cents);

        $goal->current_cents += min($room, $amount->cents);
        $goal->save();

        return $goal;
    }
}
