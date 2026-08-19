<?php

declare(strict_types=1);

use App\Support\Money;

it('formata em reais no padrão brasileiro', function (): void {
    expect(Money::fromCents(123456)->format())->toBe('R$ 1.234,56')
        ->and(Money::fromCents(0)->format())->toBe('R$ 0,00')
        ->and(Money::fromCents(-50000)->format())->toBe('−R$ 500,00');
});

it('prefixa o sinal quando pedido', function (): void {
    expect(Money::fromCents(50000)->format(sign: true))->toBe('+R$ 500,00')
        ->and(Money::fromCents(-50000)->format(sign: true))->toBe('−R$ 500,00')
        ->and(Money::fromCents(0)->format(sign: true))->toBe('R$ 0,00');
});

it('converte reais em centavos sem erro de ponto flutuante', function (): void {
    expect(Money::fromReais(387.50)->cents)->toBe(38750)
        ->and(Money::fromReais('0.07')->cents)->toBe(7)
        ->and(Money::fromReais(1234.565)->cents)->toBe(123457);
});

it('encurta valores para o eixo do gráfico', function (): void {
    expect(Money::fromReais(8500)->short())->toBe('R$ 8,5k')
        ->and(Money::fromReais(999)->short())->toBe('R$ 999')
        ->and(Money::fromReais(1000)->short())->toBe('R$ 1,0k');
});

it('soma e subtrai preservando centavos', function (): void {
    $total = Money::fromReais(10.10)->plus(Money::fromReais(0.20))->minus(Money::fromReais(0.30));

    expect($total->cents)->toBe(1000);
});

it('calcula percentual limitado a cem', function (): void {
    expect(Money::fromReais(50)->percentOf(Money::fromReais(200)))->toBe(25.0)
        ->and(Money::fromReais(300)->percentOf(Money::fromReais(200)))->toBe(100.0)
        ->and(Money::fromReais(300)->percentOf(Money::fromReais(200), cap: null))->toBe(150.0)
        ->and(Money::fromReais(50)->percentOf(Money::zero()))->toBe(0.0);
});
