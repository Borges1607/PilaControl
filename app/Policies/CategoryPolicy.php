<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * Todo dado do app é do usuário logado. As consultas já saem da relação
 * (`$user->categories()`), então a policy é a segunda tranca: pega o caso de um
 * id chegar por parâmetro de ação, que é público e não confia na view.
 */
class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        return $this->owns($user, $category);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->owns($user, $category);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->owns($user, $category);
    }

    private function owns(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }
}
