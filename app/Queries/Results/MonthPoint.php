<?php

declare(strict_types=1);

namespace App\Queries\Results;

use App\Support\Money;

final readonly class MonthPoint
{
    /**
     * @param  string  $month  chave "Y-m"
     * @param  string  $label  rótulo do eixo, ex. "Ago/26"
     */
    public function __construct(
        public string $month,
        public string $label,
        public Money $income,
        public Money $expense,
    ) {}
}
