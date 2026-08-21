<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;

/**
 * Como as outras: a consulta já sai da relação do usuário, e a policy é a segunda
 * tranca para id que chega por parâmetro de ação.
 */
class GoalPolicy
{
    public function view(User $user, Goal $goal): bool
    {
        return $this->owns($user, $goal);
    }

    public function update(User $user, Goal $goal): bool
    {
        return $this->owns($user, $goal);
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $this->owns($user, $goal);
    }

    private function owns(User $user, Goal $goal): bool
    {
        return $goal->user_id === $user->id;
    }
}
