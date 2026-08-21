<?php

declare(strict_types=1);

namespace App\Actions\Goals;

use App\Models\Goal;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * `addGoal` do protótipo.
 */
final class CreateGoal
{
    public function handle(
        User $user,
        string $name,
        string $icon,
        Money $target,
        Money $current,
        CarbonInterface $deadline,
    ): Goal {
        if ($target->cents <= 0) {
            throw new InvalidArgumentException('Meta sem valor alvo não é meta.');
        }

        if ($current->cents > $target->cents) {
            throw new InvalidArgumentException('O valor já guardado não pode passar do alvo.');
        }

        return $user->goals()->create([
            'name' => trim($name),
            'icon' => $icon,
            'target_cents' => $target->cents,
            'current_cents' => max(0, $current->cents),
            'deadline' => $deadline->startOfDay(),
        ]);
    }
}
