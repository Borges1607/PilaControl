<?php

declare(strict_types=1);

namespace App\Queries\Results;

use App\Support\Demo\Category;
use App\Support\Money;

final readonly class CategorySpending
{
    /**
     * @param  float  $share  fatia do total de despesas do período, em porcentagem
     */
    public function __construct(
        public Category $category,
        public Money $total,
        public float $share,
    ) {}
}
