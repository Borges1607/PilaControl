<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\MonthlySummary;

/**
 * Datas fixas de propósito: o recorte por mês não pode depender de quando o
 * teste roda, senão vira em toda virada de mês.
 */
it('soma receitas, despesas e saldo do mês pedido', function (): void {
    $user = User::factory()->create();
    $salario = Category::factory()->for($user)->income()->create();
    $mercado = Category::factory()->for($user)->create();

    Transaction::factory()->for($salario)->income()->worth(5_000)->on('2026-03-05')->create();
    Transaction::factory()->for($mercado)->worth(1_200)->on('2026-03-10')->create();
    Transaction::factory()->for($mercado)->worth(300)->on('2026-03-28')->create();

    $summary = app(MonthlySummary::class)->handle($user, '2026-03');

    expect($summary->income->cents)->toBe(500_000)
        ->and($summary->expense->cents)->toBe(150_000)
        ->and($summary->balance->cents)->toBe(350_000)
        ->and($summary->incomeCount)->toBe(1)
        ->and($summary->expenseCount)->toBe(2)
        ->and($summary->count)->toBe(3);
});

it('não deixa lançamento de outro mês entrar na conta', function (): void {
    $user = User::factory()->create();
    $mercado = Category::factory()->for($user)->create();

    Transaction::factory()->for($mercado)->worth(100)->on('2026-03-31')->create();
    Transaction::factory()->for($mercado)->worth(999)->on('2026-04-01')->create();
    Transaction::factory()->for($mercado)->worth(999)->on('2026-02-28')->create();

    $summary = app(MonthlySummary::class)->handle($user, '2026-03');

    expect($summary->expense->cents)->toBe(10_000)
        ->and($summary->count)->toBe(1);
});

it('soma apenas o que é da conta pedida', function (): void {
    $user = User::factory()->create();
    $mercado = Category::factory()->for($user)->create();
    $deOutro = Category::factory()->create();

    Transaction::factory()->for($mercado)->worth(250)->on('2026-03-15')->create();
    Transaction::factory()->for($deOutro)->worth(9_999)->on('2026-03-15')->create();

    $summary = app(MonthlySummary::class)->handle($user, '2026-03');

    expect($summary->expense->cents)->toBe(25_000)
        ->and($summary->count)->toBe(1);
});

it('sem mês, soma tudo — é o saldo acumulado', function (): void {
    $user = User::factory()->create();
    $salario = Category::factory()->for($user)->income()->create();
    $mercado = Category::factory()->for($user)->create();

    Transaction::factory()->for($salario)->income()->worth(1_000)->on('2026-01-10')->create();
    Transaction::factory()->for($salario)->income()->worth(1_000)->on('2026-02-10')->create();
    Transaction::factory()->for($mercado)->worth(400)->on('2026-03-10')->create();

    $summary = app(MonthlySummary::class)->handle($user);

    expect($summary->income->cents)->toBe(200_000)
        ->and($summary->expense->cents)->toBe(40_000)
        ->and($summary->balance->cents)->toBe(160_000)
        ->and($summary->count)->toBe(3);
});

it('devolve zeros quando o mês está vazio', function (): void {
    $summary = app(MonthlySummary::class)->handle(User::factory()->create(), '2026-03');

    expect($summary->income->isZero())->toBeTrue()
        ->and($summary->expense->isZero())->toBeTrue()
        ->and($summary->balance->isZero())->toBeTrue()
        ->and($summary->count)->toBe(0);
});

it('saldo fica negativo quando se gasta mais do que entra', function (): void {
    $user = User::factory()->create();
    $salario = Category::factory()->for($user)->income()->create();
    $mercado = Category::factory()->for($user)->create();

    Transaction::factory()->for($salario)->income()->worth(800)->on('2026-03-05')->create();
    Transaction::factory()->for($mercado)->worth(1_250)->on('2026-03-06')->create();

    $summary = app(MonthlySummary::class)->handle($user, '2026-03');

    expect($summary->balance->isNegative())->toBeTrue()
        ->and($summary->balance->cents)->toBe(-45_000);
});

it('chega ao mesmo total somando as linhas já carregadas', function (): void {
    $user = User::factory()->create();
    $salario = Category::factory()->for($user)->income()->create();
    $mercado = Category::factory()->for($user)->create();

    Transaction::factory()->for($salario)->income()->worth(3_000)->on('2026-03-05')->create();
    Transaction::factory()->for($mercado)->worth(700)->on('2026-03-11')->create();
    Transaction::factory()->for($mercado)->worth(120)->on('2026-03-19')->create();

    $query = app(MonthlySummary::class);

    $noBanco = $query->handle($user, '2026-03');
    $emMemoria = $query->fromRows(
        Transaction::query()->whereBelongsTo($user)->inMonth('2026-03')->get()
    );

    expect($emMemoria->income->cents)->toBe($noBanco->income->cents)
        ->and($emMemoria->expense->cents)->toBe($noBanco->expense->cents)
        ->and($emMemoria->balance->cents)->toBe($noBanco->balance->cents)
        ->and($emMemoria->incomeCount)->toBe($noBanco->incomeCount)
        ->and($emMemoria->expenseCount)->toBe($noBanco->expenseCount)
        ->and($emMemoria->count)->toBe($noBanco->count);
});
