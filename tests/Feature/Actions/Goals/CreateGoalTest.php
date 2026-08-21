<?php

declare(strict_types=1);

use App\Actions\Goals\CreateGoal;
use App\Models\Goal;
use App\Models\User;
use App\Support\Money;

it('cria a meta do usuário', function (): void {
    $user = User::factory()->create();

    $goal = app(CreateGoal::class)->handle(
        user: $user,
        name: '  Viagem Europa  ',
        icon: '✈️',
        target: Money::fromReais(15_000),
        current: Money::fromReais(4_200),
        deadline: now()->addYear()->setTime(13, 45),
    );

    expect($goal->exists)->toBeTrue()
        ->and($goal->user_id)->toBe($user->id)
        ->and($goal->name)->toBe('Viagem Europa')
        ->and($goal->target_cents)->toBe(1_500_000)
        ->and($goal->current_cents)->toBe(420_000)
        // Prazo é dia, não instante.
        ->and($goal->deadline->format('H:i:s'))->toBe('00:00:00');
});

it('recusa alvo zerado', function (): void {
    expect(fn (): Goal => app(CreateGoal::class)->handle(
        user: User::factory()->create(),
        name: 'Meta vazia',
        icon: '🎯',
        target: Money::zero(),
        current: Money::zero(),
        deadline: now()->addMonth(),
    ))->toThrow(InvalidArgumentException::class);
});

it('recusa já guardado maior que o alvo', function (): void {
    expect(fn (): Goal => app(CreateGoal::class)->handle(
        user: User::factory()->create(),
        name: 'Meta torta',
        icon: '🎯',
        target: Money::fromReais(100),
        current: Money::fromReais(500),
        deadline: now()->addMonth(),
    ))->toThrow(InvalidArgumentException::class);
});
