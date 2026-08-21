<?php

declare(strict_types=1);

use App\Actions\Transactions\CreateTransaction;
use App\Enums\TransactionType;
use App\Exceptions\CategoryRejectsType;
use App\Models\Category;
use App\Support\Money;

it('grava o lançamento na categoria e na conta dela', function (): void {
    $category = Category::factory()->create();

    $transaction = app(CreateTransaction::class)->handle(
        category: $category,
        type: TransactionType::Expense,
        description: '  Supermercado  ',
        amount: Money::fromReais('387.50'),
        date: now()->startOfMonth(),
        notes: 'compra do mês',
    );

    expect($transaction->exists)->toBeTrue()
        ->and($transaction->category_id)->toBe($category->id)
        // O dono sai da categoria: não há como lançar na conta errada.
        ->and($transaction->user_id)->toBe($category->user_id)
        ->and($transaction->description)->toBe('Supermercado')
        ->and($transaction->amount_cents)->toBe(38_750)
        ->and($transaction->notes)->toBe('compra do mês');
});

it('guarda valor positivo — o sinal vem do tipo', function (): void {
    $transaction = app(CreateTransaction::class)->handle(
        category: Category::factory()->create(),
        type: TransactionType::Expense,
        description: 'Aluguel',
        amount: Money::fromReais(-1800),
        date: now(),
    );

    expect($transaction->amount_cents)->toBe(180_000);
});

it('trata observação vazia como ausente', function (): void {
    $transaction = app(CreateTransaction::class)->handle(
        category: Category::factory()->create(),
        type: TransactionType::Expense,
        description: 'Uber',
        amount: Money::fromReais('42.90'),
        date: now(),
        notes: '   ',
    );

    expect($transaction->notes)->toBeNull();
});

it('recusa tipo que a categoria não aceita', function (): void {
    $category = Category::factory()->income()->create();

    expect(fn (): mixed => app(CreateTransaction::class)->handle(
        category: $category,
        type: TransactionType::Expense,
        description: 'Bônus',
        amount: Money::fromReais(100),
        date: now(),
    ))->toThrow(CategoryRejectsType::class);

    expect($category->transactions()->count())->toBe(0);
});

it('aceita os dois tipos na categoria "ambos"', function (): void {
    $category = Category::factory()->both()->create();

    foreach ([TransactionType::Income, TransactionType::Expense] as $type) {
        app(CreateTransaction::class)->handle(
            category: $category,
            type: $type,
            description: 'Pix',
            amount: Money::fromReais(50),
            date: now(),
        );
    }

    expect($category->transactions()->count())->toBe(2);
});
