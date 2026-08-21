<?php

declare(strict_types=1);

use App\Actions\Budgets\RemoveCategoryBudget;
use App\Models\Budget;
use App\Models\Category;

it('tira o limite do mês', function (): void {
    $category = Category::factory()->create();

    Budget::factory()->for($category)->create([
        'user_id' => $category->user_id,
        'month' => '2026-08',
    ]);

    app(RemoveCategoryBudget::class)->handle($category, '2026-08');

    expect($category->budgets()->count())->toBe(0);
});

it('não encosta nos outros meses', function (): void {
    $category = Category::factory()->create();

    foreach (['2026-07', '2026-08'] as $month) {
        Budget::factory()->for($category)->create([
            'user_id' => $category->user_id,
            'month' => $month,
        ]);
    }

    app(RemoveCategoryBudget::class)->handle($category, '2026-08');

    expect($category->budgets()->pluck('month')->all())->toBe(['2026-07']);
});

it('não reclama quando não havia limite', function (): void {
    $category = Category::factory()->create();

    app(RemoveCategoryBudget::class)->handle($category, '2026-08');

    expect($category->budgets()->count())->toBe(0);
});
