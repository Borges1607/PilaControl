<?php

declare(strict_types=1);

use App\Actions\Categories\DeleteCategory;
use App\Exceptions\CategoryInUse;
use App\Models\Category;

it('apaga categoria sem lançamento', function (): void {
    $category = Category::factory()->create();

    app(DeleteCategory::class)->handle($category);

    expect(Category::query()->find($category->id))->toBeNull();
});

it('recusa categoria com lançamento', function (): void {
    $category = Category::factory()->create();

    $category->user->transactions()->create([
        'category_id' => $category->id,
        'date' => now(),
        'description' => 'Mercado',
        'amount_cents' => 5000,
        'type' => 'expense',
    ]);

    expect(fn (): mixed => app(DeleteCategory::class)->handle($category))
        ->toThrow(CategoryInUse::class);

    expect(Category::query()->find($category->id))->not->toBeNull();
});
