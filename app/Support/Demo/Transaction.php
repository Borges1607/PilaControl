<?php

declare(strict_types=1);

namespace App\Support\Demo;

use App\Enums\TransactionType;
use App\Support\Money;
use Carbon\CarbonInterface;

/**
 * Substituto provisório do model Transaction. Mesmas colunas da tabela `transactions`.
 */
final readonly class Transaction
{
    public function __construct(
        public string $id,
        public CarbonInterface $date,
        public string $description,
        public int $amount_cents,
        public TransactionType $type,
        public string $category_id,
        public Category $category,
        public ?string $notes = null,
    ) {}

    public function amount(): Money
    {
        return Money::fromCents($this->amount_cents);
    }

    /**
     * Chave "Y-m" usada em filtros e agrupamentos.
     */
    public function monthKey(): string
    {
        return $this->date->format('Y-m');
    }

    /**
     * Chave de ordenação estável: data e, em empate, o id.
     */
    public function sortKey(): string
    {
        return $this->date->format('Y-m-d').'|'.$this->id;
    }
}
