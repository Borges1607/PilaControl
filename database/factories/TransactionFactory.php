<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = Category::factory();

        return [
            'category_id' => $category,
            // O dono sai da categoria, como na Action — assim a factory não
            // consegue produzir lançamento de uma conta na gaveta de outra.
            'user_id' => fn (array $attributes): int => (int) Category::query()
                ->where('id', $attributes['category_id'])
                ->value('user_id'),
            'date' => fake()->dateTimeBetween('-3 months'),
            'description' => fake()->sentence(3),
            'amount_cents' => fake()->numberBetween(500, 500_000),
            'type' => TransactionType::Expense,
            'notes' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(['type' => TransactionType::Income]);
    }

    public function expense(): static
    {
        return $this->state(['type' => TransactionType::Expense]);
    }

    /**
     * @param  numeric-string|float|int  $reais
     */
    public function worth(string|float|int $reais): static
    {
        return $this->state(['amount_cents' => (int) round((float) $reais * 100)]);
    }

    public function on(string $date): static
    {
        return $this->state(['date' => $date]);
    }
}
