<?php

declare(strict_types=1);

namespace App\Support\Demo;

use App\Enums\CategoryType;

/**
 * Substituto provisório do model Category. Expõe exatamente as colunas que a tabela
 * `categories` vai ter, para que as views não mudem quando o model existir.
 */
final readonly class Category
{
    public function __construct(
        public string $id,
        public string $name,
        public string $icon,
        public string $color,
        public CategoryType $type,
    ) {}
}
