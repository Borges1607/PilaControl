<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use App\Support\MonthLabel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory()->expense(),
            // O dono sai da categoria, como na Action.
            'user_id' => fn (array $attributes): int => (int) Category::query()
                ->where('id', $attributes['category_id'])
                ->value('user_id'),
            'month' => MonthLabel::currentKey(),
            'limit_cents' => fake()->numberBetween(10_000, 500_000),
        ];
    }

    /**
     * @param  numeric-string|float|int  $reais
     */
    public function worth(string|float|int $reais): static
    {
        return $this->state(['limit_cents' => (int) round((float) $reais * 100)]);
    }

    public function forMonth(string $month): static
    {
        return $this->state(['month' => $month]);
    }
}
