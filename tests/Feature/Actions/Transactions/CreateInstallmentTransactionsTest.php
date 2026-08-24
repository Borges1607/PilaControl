<?php

declare(strict_types=1);

use App\Actions\Transactions\CreateInstallmentTransactions;
use App\Enums\TransactionType;
use App\Exceptions\CategoryRejectsType;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\Money;

it('cria um lançamento por parcela, um em cada mês', function (): void {
    $category = Category::factory()->create();

    $parcelas = app(CreateInstallmentTransactions::class)->handle(
        category: $category,
        type: TransactionType::Expense,
        description: '  Geladeira  ',
        amounts: Money::fromReais(1200)->split(3),
        firstDate: Carbon\Carbon::parse('2026-01-15'),
        notes: 'cartão',
    );

    expect($parcelas)->toHaveCount(3)
        ->and($parcelas->pluck('description')->all())
        ->toBe(['Geladeira (1/3)', 'Geladeira (2/3)', 'Geladeira (3/3)'])
        ->and($parcelas->pluck('amount_cents')->all())->toBe([40000, 40000, 40000])
        ->and($parcelas->map(fn (Transaction $tx): string => $tx->date->format('Y-m-d'))->all())
        ->toBe(['2026-01-15', '2026-02-15', '2026-03-15'])
        ->and($parcelas->pluck('user_id')->unique()->all())->toBe([$category->user_id])
        ->and($parcelas->pluck('notes')->unique()->all())->toBe(['cartão']);
});

it('aceita parcelas de valores diferentes', function (): void {
    $parcelas = app(CreateInstallmentTransactions::class)->handle(
        category: Category::factory()->create(),
        type: TransactionType::Expense,
        description: 'Notebook',
        amounts: [Money::fromReais(700), Money::fromReais(300)],
        firstDate: now(),
    );

    expect($parcelas->pluck('amount_cents')->all())->toBe([70000, 30000]);
});

it('não transborda o mês curto', function (): void {
    $parcelas = app(CreateInstallmentTransactions::class)->handle(
        category: Category::factory()->create(),
        type: TransactionType::Expense,
        description: 'Sofá',
        amounts: Money::fromReais(600)->split(3),
        firstDate: Carbon\Carbon::parse('2026-01-31'),
    );

    expect($parcelas->map(fn (Transaction $tx): string => $tx->date->format('Y-m-d'))->all())
        ->toBe(['2026-01-31', '2026-02-28', '2026-03-31']);
});

it('recusa parcelamento de uma parcela só', function (): void {
    $category = Category::factory()->create();

    expect(fn (): mixed => app(CreateInstallmentTransactions::class)->handle(
        category: $category,
        type: TransactionType::Expense,
        description: 'Café',
        amounts: [Money::fromReais(10)],
        firstDate: now(),
    ))->toThrow(InvalidArgumentException::class);

    expect($category->transactions()->count())->toBe(0);
});

it('não deixa meia compra lançada quando a categoria recusa o tipo', function (): void {
    $category = Category::factory()->income()->create();

    expect(fn (): mixed => app(CreateInstallmentTransactions::class)->handle(
        category: $category,
        type: TransactionType::Expense,
        description: 'Geladeira',
        amounts: Money::fromReais(1200)->split(3),
        firstDate: now(),
    ))->toThrow(CategoryRejectsType::class);

    expect($category->transactions()->count())->toBe(0);
});
