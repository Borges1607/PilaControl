<?php

declare(strict_types=1);

use App\Actions\Transactions\DeleteTransaction;
use App\Models\Transaction;

it('apaga o lançamento', function (): void {
    $transaction = Transaction::factory()->create();

    app(DeleteTransaction::class)->handle($transaction);

    expect(Transaction::query()->find($transaction->id))->toBeNull();
});

it('deixa a categoria de pé', function (): void {
    $transaction = Transaction::factory()->create();
    $category = $transaction->category;

    app(DeleteTransaction::class)->handle($transaction);

    // Removido o último lançamento, a categoria volta a poder ser apagada.
    expect($category->fresh())->not->toBeNull()
        ->and($category->isInUse())->toBeFalse();
});
