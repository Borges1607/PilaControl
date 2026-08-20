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
     * Token de cor da paleta correspondente ao tipo. "Ambos" usa o azul de estado,
     * que é o que o protótipo faz — não é receita nem despesa.
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::Income => 'text-income',
            self::Expense => 'text-expense',
            self::Both => 'text-info',
        };
    }

    /**
     * Fundo da pílula: a mesma cor a ~7%, como o `{cor}11` do protótipo.
     */
    public function tintClass(): string
    {
        return match ($this) {
            self::Income => 'bg-income/7',
            self::Expense => 'bg-expense/7',
            self::Both => 'bg-info/7',
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
