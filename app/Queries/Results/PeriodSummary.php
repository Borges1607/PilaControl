<?php

declare(strict_types=1);

namespace App\Queries\Results;

use App\Support\Money;

final readonly class PeriodSummary
{
    public function __construct(
        public Money $income,
        public Money $expense,
        public Money $balance,
        public int $incomeCount,
        public int $expenseCount,
        public int $count,
    ) {}
}
