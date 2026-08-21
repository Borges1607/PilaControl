<?php

declare(strict_types=1);

namespace App\Actions\Goals;

use App\Models\Goal;

/**
 * `deleteGoal` do protótipo.
 */
final class DeleteGoal
{
    public function handle(Goal $goal): void
    {
        $goal->delete();
    }
}
