<?php

declare(strict_types=1);

use App\Actions\Budgets\SetCategoryBudget;
use App\Models\Budget;
use App\Models\Category;
use App\Support\Money;

it('cria o limite do mês', function (): void {
    $category = Category::factory()->create();

    $budget = app(SetCategoryBudget::class)->handle($category, '2026-08', Money::fromReais(800));

    expect($budget->exists)->toBeTrue()
        ->and($budget->category_id)->toBe($category->id)
        // O dono sai da categoria, como no CreateTransaction.
        ->and($budget->user_id)->toBe($category->user_id)
        ->and($budget->month)->toBe('2026-08')
        ->and($budget->limit_cents)->toBe(80_000);
});

it('substitui o limite do mesmo mês em vez de duplicar', function (): void {
    $category = Category::factory()->create();
    $action = app(SetCategoryBudget::class);

    $action->handle($category, '2026-08', Money::fromReais(800));
    $action->handle($category, '2026-08', Money::fromReais(950));

    expect($category->budgets()->count())->toBe(1)
        ->and($category->budgets()->value('limit_cents'))->toBe(95_000);
});

it('guarda um limite por mês', function (): void {
    $category = Category::factory()->create();
    $action = app(SetCategoryBudget::class);

    $action->handle($category, '2026-08', Money::fromReais(800));
    $action->handle($category, '2026-09', Money::fromReais(900));

    expect($category->budgets()->count())->toBe(2)
        ->and($category->budgets()->forMonth('2026-09')->value('limit_cents'))->toBe(90_000);
});

it('recusa limite zero ou negativo', function (): void {
    $category = Category::factory()->create();

    expect(fn (): Budget => app(SetCategoryBudget::class)->handle($category, '2026-08', Money::zero()))
        ->toThrow(InvalidArgumentException::class);

    expect($category->budgets()->count())->toBe(0);
});
