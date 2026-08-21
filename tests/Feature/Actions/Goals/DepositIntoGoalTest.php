<?php

declare(strict_types=1);

use App\Actions\Goals\DepositIntoGoal;
use App\Models\Goal;
use App\Support\Money;

it('soma o aporte ao que já estava guardado', function (): void {
    $goal = Goal::factory()->target(15_000)->saved(4_200)->create();

    app(DepositIntoGoal::class)->handle($goal, Money::fromReais(800));

    expect($goal->refresh()->current_cents)->toBe(500_000);
});

it('não passa do alvo', function (): void {
    $goal = Goal::factory()->target(6_000)->saved(3_800)->create();

    app(DepositIntoGoal::class)->handle($goal, Money::fromReais(999_999));

    expect($goal->refresh()->current_cents)->toBe(600_000)
        ->and($goal->isCompleted())->toBeTrue();
});

it('não muda nada em meta já alcançada', function (): void {
    $goal = Goal::factory()->target(6_000)->completed()->create();

    app(DepositIntoGoal::class)->handle($goal, Money::fromReais(100));

    expect($goal->refresh()->current_cents)->toBe(600_000);
});

it('recusa aporte não positivo', function (): void {
    $goal = Goal::factory()->target(6_000)->saved(100)->create();

    expect(fn (): Goal => app(DepositIntoGoal::class)->handle($goal, Money::zero()))
        ->toThrow(InvalidArgumentException::class);

    expect($goal->refresh()->current_cents)->toBe(10_000);
});
