<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use App\Support\CategoryPresets;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // `unique()` porque a tabela tem chave única em (user_id, type, name).
            'name' => fake()->unique()->word(),
            'icon' => fake()->randomElement(CategoryPresets::icons()),
            'color' => fake()->randomElement(CategoryPresets::colors()),
            'type' => CategoryType::Expense,
        ];
    }

    public function income(): static
    {
        return $this->state(['type' => CategoryType::Income]);
    }

    public function expense(): static
    {
        return $this->state(['type' => CategoryType::Expense]);
    }

    public function both(): static
    {
        return $this->state(['type' => CategoryType::Both]);
    }
}
