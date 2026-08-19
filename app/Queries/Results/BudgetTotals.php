<?php

declare(strict_types=1);

namespace App\Queries\Results;

use App\Support\Money;

final readonly class BudgetTotals
{
    public function __construct(
        public Money $budgeted,
        public Money $spent,
        public Money $available,
    ) {}
}
