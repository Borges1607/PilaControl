<?php

declare(strict_types=1);

use App\Support\CategoryPresets;

it('não repete cor nem ícone', function (): void {
    expect(CategoryPresets::colors())->toBe(array_values(array_unique(CategoryPresets::colors())))
        ->and(CategoryPresets::icons())->toBe(array_values(array_unique(CategoryPresets::icons())));
});

it('entrega cores no formato que o cadastro valida', function (): void {
    foreach (CategoryPresets::colors() as $color) {
        expect($color)->toMatch('/^#[0-9a-f]{6}$/');
    }
});

it('fecha a grade de ícones em linhas de doze', function (): void {
    expect(CategoryPresets::icons())->not->toBeEmpty()
        ->and(count(CategoryPresets::icons()) % 12)->toBe(0);
});
