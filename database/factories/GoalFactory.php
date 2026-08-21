<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'icon' => '🎯',
            'target_cents' => 1_000_000,
            'current_cents' => 0,
            'deadline' => now()->addYear()->startOfDay(),
        ];
    }

    /**
     * @param  numeric-string|float|int  $reais
     */
    public function target(string|float|int $reais): static
    {
        return $this->state(['target_cents' => (int) round((float) $reais * 100)]);
    }

    /**
     * @param  numeric-string|float|int  $reais
     */
    public function saved(string|float|int $reais): static
    {
        return $this->state(['current_cents' => (int) round((float) $reais * 100)]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_cents' => $attributes['target_cents'],
        ]);
    }

    public function dueOn(string $date): static
    {
        return $this->state(['deadline' => $date]);
    }
}
