<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;

/**
 * `addCategory` do protótipo.
 */
final class CreateCategory
{
    public function handle(
        User $user,
        string $name,
        string $icon,
        string $color,
        CategoryType $type,
    ): Category {
        return $user->categories()->create([
            'name' => trim($name),
            'icon' => $icon,
            // A cor entra normalizada: o seletor nativo devolve maiúsculas e a
            // chave única não distingue caixa, mas a comparação na view sim.
            'color' => mb_strtolower($color),
            'type' => $type,
        ]);
    }
}
