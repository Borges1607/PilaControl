<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Receita',
            self::Expense => 'Despesa',
            self::Both => 'Ambos',
        };
    }

    /**
     * A categoria aceita lançamentos do tipo informado?
     */
    public function accepts(TransactionType $type): bool
    {
        return $this === self::Both || $this->value === $type->value;
    }
}
