<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Receita',
            self::Expense => 'Despesa',
        };
    }

    /**
     * Rótulo no plural, usado nos filtros da tela de transações.
     */
    public function pluralLabel(): string
    {
        return match ($this) {
            self::Income => 'Receitas',
            self::Expense => 'Despesas',
        };
    }

    /**
     * Sinal que precede o valor na listagem.
     */
    public function sign(): string
    {
        return match ($this) {
            self::Income => '+',
            self::Expense => '−',
        };
    }

    /**
     * Token de cor da paleta correspondente ao tipo.
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::Income => 'text-income',
            self::Expense => 'text-expense',
        };
    }

    public function isIncome(): bool
    {
        return $this === self::Income;
    }
}
