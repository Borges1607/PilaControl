<?php

declare(strict_types=1);

namespace App\Queries\Results;

use App\Support\Demo\Category;
use App\Support\Money;

final readonly class BudgetRow
{
    /**
     * @param  float|null  $percent  null quando a categoria não tem limite definido
     */
    public function __construct(
        public Category $category,
        public Money $spent,
        public Money $limit,
        public ?float $percent,
        public bool $over,
    ) {}
}
